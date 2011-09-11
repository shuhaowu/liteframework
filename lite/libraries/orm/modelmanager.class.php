<?php
/**
 * The model manager class.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;
use lite\orm\drivers\DatabaseLulz;
use Exception;
class NotSavedError extends Exception {}
class LockError extends Exception {}

/**
 * The ModelManager class keeps track of Models and interact with the database
 * on their behaves.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
class ModelManager{
	private static $drivers = array();
	private static $defaultdriver;
	private $objects = array();
	private $properties = array();
	private $locked = false;
	private $tablename;
	private $modelClass;

	private static $instances = array();

	/**
	 * Creates a new instance of a ModelManager.
	 * This must be done for every single Model subclass.
	 */
	private function __construct($tablename, $modelClass){
		$this->tablename = $tablename;
		$this->modelClass = $modelClass;
		self::$instances[$modelClass] = $this;
	}

	public static function getInstance($tablename, $modelClass){
		if (array_key_exists($modelClass, self::$instances))
			return self::$instances[$modelClass];
		else
			return new ModelManager($tablename, $modelClass);
	}

	/**
	 * Adds a driver to the collection of drivers to be used for put() calls.
	 * @param object $dbDriver Driver instances.
	 */
	public static function addDriver($dbDriver){
		if (!array_key_exists($dbDriver->name, self::$drivers)){
			self::$drivers[$dbDriver->name] = $dbDriver;
			return true;
		}
		return false;
	}

	/**
	 * Removes a driver.
	 * @param string/object $dbDriver Could be the actual driver object or the
	 * $name for it.
	 * @return boolean false if the driver $name is not found in the collection
	 * of drivers.
	 */
	public static function delDriver($dbDriver){
		switch (gettype($dbDriver)){
			case 'object':
				$name = $dbDriver->name;
			break;
			case 'string':
				$name = $dbDriver;
			break;
			default:
				return false;
		}

		if (array_key_exists($name, self::$drivers)){
			unset(self::$drivers[$name]);
			return true;
		}

		return false;
	}


	/**
	 * Sets the default driver to "SELECT" from.
	 * @param object $dbDriver The driver instance. This must have been added
	 * already to the collection of drivers.
	 * @return boolean false if the driver didn't get added before.
	 */
	public static function setDefaultDriver($dbDriver){
		if (array_key_exists($dbDriver->name, self::$drivers)){
			self::$defaultdriver = $dbDriver;
			return true;
		}
		return false;
	}

	/**
	 * Gets the default driver instance
	 */
	public static function getDefaultDriver(){
		return self::$defaultdriver;
	}

	public function lock(){
		if (self::$defaultdriver)
			$this->locked = true;
		else
			throw new Exception('Default driver not set yet!');
	}

	public function unsavedObjectsCount(){
		return count($this->objects);
	}

	private function checkNotLocked(){
		if ($this->locked)
			throw new LockError("ModelManager {$this->modelClass} is locked!");
	}

	private function checkLocked(){
		if (!$this->locked)
			throw new LockError("$modelClass manager not locked yet!");
	}

	/**
	 * Add a property to the class if the class is not locked.
	 * @param string $name Name of the attribute.
	 * @param \lite\orm\BasePropertyType $type The type of the value to be
	 * stored in the database.
	 */
	public function addProperty($name, $type){
		$this->checkNotLocked();
		$this->properties[$name] = $type;
	}

	/**
	 * Gets the type of a property
	 * @return \lite\orm\types\BasePropertyType
	 */
	public function getType($name){
		$this->checkLocked();
		if (array_key_exists($name, $this->properties))
			return $this->properties[$name];
		else
			return null;
	}

	/**
	 * Gets Query object.
	 * @param boolean $keyonly If this is set to true, it returns an iterator of
	 * keys instead of all the objects, which is much more efficient.
	 * @return \lite\orm\Query
	 */
	public function all($keyonly=false){
		return new Query($this, $keyonly);
	}

	/**
	 * Gets an object from the database or from the collection of unsaved
	 * objects. Getting an object will not put it to the collection of
	 * unsaved objects.
	 * @param string $key The key for the object in question.
	 * @return \lite\orm\Model A new instance of the object initialized using
	 * its subclass from \lite\orm\Model
	 * @throw \lite\orm\NotSavedError if the $key is not found.
	 */
	public function get($key){
		$this->checkLocked();
		if (array_key_exists($key, $this->objects)){
			return $this->objects[$key];
		} else {
			$row = $this->getModelRow($key);
			if (!$row) throw new NotSavedError("$key is not found!");
			$obj = new $this->modelClass($row['key'], $row);
			return $obj;
		}
	}

	private function getModelRow($key){
		$columns = array_keys($this->properties);
		$rows = self::$defaultdriver->get($this->tablename, $columns, $key);
		$row = false;
		foreach ($rows as $row) break;
		return $row;
	}

	public function checkDeleted($model){
		if ($model->is_deleted()) throw new NotSavedError('Model ' . $model->getKey() . ' has already been deleted.');
	}

	public function put($model){
		$this->checkLocked();
		$this->checkDeleted($model);
		$values = array();
		foreach ($this->properties as $name => $type){
			if ($type->required && is_null($model->rawData($name))){
				throw new DataError("$name is not set for " .
									get_class($model) .
									' ' . $model->getKey());
			}
			array_push($values, new DatabaseLulz($name,
									$type->sqlValue($model->$name),
									$this->properties[$name]));
		}

		$successes = array();
		foreach (self::$drivers as $name => $driver){
			if (!$driver->connected()) $driver->connect();
			$success = (bool) $driver->replace($this->tablename,
												$values,
												$model->getKey());
			$successes[$name] = $success;
			if ($driver == self::$defaultdriver){
				$successes['default'] = $success;
				if ($success) unset($this->objects[$model->getKey()]);
			}
		}
		return $successes;
	}

	public function trackModel($model){
		$this->objects[$model->getKey()] = $model;
	}

	public function hasModel($key){
		return array_key_exists($key, $this->objects);
	}

	/**
	 * Deletes this model from the database.
	 * @param \lite\orm\Model $model
	 * @throw \lite\orm\NotSavedError If the model is not saved.
	 * @throw AlreadyDeletedError if the model is already deleted.
	 */
	public function delete($model){
		$this->checkLocked();
		$this->checkDeleted($model);
		if (!$model->is_saved()) throw new NotSavedError($model->getKey() . ' is not saved!');
		foreach (self::$drivers as $driver){
			$driver->delete($this->tablename, $model->getKey());
			unset($this->objects[$model->getKey()]);
		}
	}

	public function update($model){
		$this->checkLocked();
		$this->checkDeleted($model);
		$model->updateWithRow($this->getModelRow($model->getKey()));
	}

	public function getAllProperties(){
		$this->checkLocked();
		return array_keys($this->properties);
	}

	public function getTablename(){
		return $this->tablename;
	}

	public function getModelclass(){
		return $this->modelClass;
	}

	public function locked(){
		return $this->locked;
	}
}
?>
