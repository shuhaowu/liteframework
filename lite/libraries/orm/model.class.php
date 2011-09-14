<?php
/**
 * The Model class. All model classes need to extend from this class.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;

class DataError extends \Exception {}
/**
 * The model class that each model needs to extend from in order to function
 * correctly.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
class Model{
	protected $key;
	protected $data = array();
	private $saved = false;
	private $deleted = false;
	private $manager;
	public static $tablename;

	const KEY_CHARACTER_SET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	const KEY_LENGTH = 32;

	public static function generateKey(){
		$key = '';
		for ($i=0;$i<self::KEY_LENGTH;$i++){
			$choice = rand(0, strlen(self::KEY_CHARACTER_SET)-1);
			$key .= substr(self::KEY_CHARACTER_SET, $choice, 1);
		}
		return $key;
	}

	public static function validateKey($key){
		return  (strlen($key) <= self::KEY_LENGTH) &&
				(preg_match('/[a-zA-Z0-9]+/', $key)) &&
				(!static::getManager()->hasModel($key));
	}

	private static function sqlValueToRealValue($name, $value){
		$type = static::getManager()->getType($name);
		return $type->realValue($value);
	}

	public static function getClassname(){
		return get_called_class();
	}

	public static function getManager(){
		return ModelManager::getInstance(static::$tablename, get_called_class());
	}

	public function __construct($key=null, $data=null){
		$this->manager = ModelManager::getInstance(static::$tablename, get_called_class());
		if (!$this->manager->locked()) throw new LockError('The model "' . get_class($this) . '" is not locked!');

		if (!$key){
			$key = self::generateKey();
		} else {
			if (!self::validateKey($key))
				throw new InvalidKeyError("$key is not a valid key.");
		}

		$this->key = $key;

		$properties = $this->manager->getAllProperties();
		foreach ($properties as $name){
			$type = $this->manager->getType($name);
			$this->data[$name] = $type->default;
		}
		if ($data){
			$this->updateWithRow($data);
			$this->saved = true;
		} else {
			$this->manager->trackModel($this);
		}

		$this->init();
	}

	public function init(){
	}

	public function updateWithRow($row){
		unset($row['key']);
		foreach ($row as $name => $value){
			$this->data[$name] = self::sqlValueToRealValue($name, $value);
		}
	}


	public function getKey(){
		return $this->key;
	}

	public function __set($name, $value){
		$this->manager->checkDeleted($this);
		if (array_key_exists($name, $this->data)){
			$type = $this->manager->getType($name);
			if ($type->validate($value)){
				$this->data[$name] = $value;
				$this->manager->trackModel($this);
			} else {
				throw new DataError("$value does not pass validation ($name).");
			}
		} else {
			throw new DataError("$name is not a valid property for " . get_class($this));
		}
	}

	public function __get($name){
		$this->manager->checkDeleted($this);
		if (array_key_exists($name, $this->data)){
			return $this->data[$name];
		} else {
			throw new DataError("$name doesn't exist in " . get_class($this));
		}
	}

	public function put(){
		$successes = $this->manager->put($this);
		if ($successes['default']){
			$this->saved = true;
			return true;
		} else {
			return false;
		}
	}

	public function update(){
		$this->manager->update($this);
		$this->saved = true;
	}

	public function delete(){
		$this->manager->delete($this);
		$this->deleted = true;
		$this->saved = false;
	}

	public function is_saved(){
		return $this->saved;
	}

	public function is_deleted(){
		return $this->deleted;
	}
}
?>
