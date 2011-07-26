<?php
/**
 * The Model class
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;
use Exception;
use lite\orm\drivers\DatabaseError;
	use lite\orm\drivers\DatabaseLulz;

class LockError extends Exception {}
class DataError extends Exception {}

/**
 * The model class that each model needs to extend from in order to function
 * correctly. The class itself also keeps track of ALL the object initialized
 * and it will serve as a local cache.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
abstract class Model{
	protected static $objects = array();
	protected $key;
	protected $data = array();
	protected static $properties = array();
	protected static $locked = false;
	
	/**
	 * The tablename in the SQL, or other identifier. Let's not change this
	 * half way through a session. I'm not responsible for what happens.
	 * @var string
	 */
	public static $tablename;
	
	/**
	 * The character set that keys will be generator from.
	 * @var string
	 */
	// For those who will criticize the fact that this is above the 80 character
	// line: Meh.
	const KEY_CHARACTER_SET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	
	/**
	 * Length of the key.
	 * @var unknown_type
	 */
	const KEY_LENGTH = 64;
	
	/**
	 * Generates a key for the primary key of the database.
	 * The primary key of the database should be the length of 64 VARCHAR.
	 * This should generate an unique key. The underlying algorithm is choose 
	 * random characters 54 random characters from [a-zA-Z0-9] and add the last
	 * 10 digit of the timestamp in integer to the beginning of the string 
	 * (prefix). This also should allow the time of generation.
	 * @return string A random key that has a length of 64. *Should* be unique.
	 */
	public static function generateKey(){
		$uuid = '';
		for ($i=0; $i<self::KEY_LENGTH-10; $i++) {
			$choice = rand(0, strlen(self::KEY_CHARACTER_SET)-1);
			$uuid .= substr(self::KEY_CHARACTER_SET, $choice, 1);
		}
		$uuid = substr(intval(time()), -10) . $uuid;
		return $uuid;
	}
	
	/**
	 * Validates a key. The criteria is <= 64 characters and only contains 
	 * [a-zA-Z0-9] and not in the tracked objects
	 * @param string $key The key you need to validate.
	 * @return boolean
	 */
	public static function validateKey($key){
		return  (strlen($key) <= 64) && 
				(preg_match('/[a-zA-Z0-9]+/', $key)) && 
				(!array_key_exists($key, static::$objects));
	}
	
	/**
	 * Add a property to the class if the class is not locked.
	 * @param string $name Name of the attribute.
	 * @param \lite\orm\BasePropertyType $type The type of the value to be 
	 * stored in the database.
	 */
	public static function addProperty($name, $type){
		if (static::$locked){
			throw new LockError('The model "' . static::$tablename . 
									'" has been locked.');
		}
		static::$properties[$name] = $type;
	}
	
	/**
	 * Lock the model from changes. Must lock in order to start initializing.
	 * This is implemented so that there's no sudden changes during the middle
	 * of the session.
	 */
	public static function lock(){
		static::$locked = true;
	}
	
	/**
	 * Returns an array of all the properties.
	 * @return array 
	 */
	public static function properties(){
		return static::$properties;
	}
	
	/**
	* Gets an iterator that iterates through all the objects.
	* @param boolean $keyonly If this is set to true, it returns an iterator of
	* keys instead of all the objects, which is much more efficient.
	* @return Iterator
	*/
	public static function all($keyonly=false){
	
	}

	protected static function getRow($key){
		global $liteDBDriver;
		$columns = array_keys(static::$properties);
		return $liteDBDriver->get(static::$tablename, $columns, $key);
	}


	public static function get($key){
		if (array_key_exists($key, static::$objects)){
			return static::$objects[$key];
		} else {
			$rows = static::getRow($key);
			foreach ($rows as $row){
				$key = $row['key'];
				unset($row['key']);
				$obj = new static($key, $row);
				unset(static::$objects[$key]); 
				return $obj;
			}
		}
	}
	
	/**
	* Saves all untracked objects. Not really recommended.
	*/
	public static function putAll(){
		foreach (static::$objects as $key => $obj){
			$obj->put();
		}
	}
	
	/**
	 * Construct a new instance of your model. This class must be subclassed.
	 * @param string $key A key that will pass Model::validateKey.
	 * @param array $data The data to initialize the model with. Assosiative.
	 * @throws LockError You cannot initialize unless the class has been locked.
	 * @throws InvalidKeyError When the key specified is not a valid key.
	 */
	public final function __construct($key=null, $data=null){
		global $liteDBDriver;
		
		if (!isset($liteDBDriver)){
			throw new DatabaseError('There is no database set!');
		} else {
			$cls = get_class($liteDBDriver);
			if ($cls::IDENTITY != 'sqlite'){
				throw new DatabaseError('A ' . $cls::IDENTITY .
								'database is set, instead of a sqlite one.');
			}
		}
		
		if (!static::$locked) throw new LockError('The model "' .
												  get_class($this) . 
												  '" is not locked!');
		
		// Key generation.
		if (!$key){
			$key = self::generateKey();
		} else {
			if (!self::validateKey($key)){
				throw new InvalidKeyError("$key is not a valid key.");
			}
		}
		$this->key = $key;
		
		static::$objects[$key] = $this;
		
		// Initialize the values. This should initialize all the values as 
		// the default for $type->default is null.
		foreach (static::$properties as $property => $type){
			$this->data[$property] = $type->default;
		}

		if ($data) array_merge($this->data, $data);
		
		$this->init();
	}
	
	/**
	 * A function called immediately after __construct. You must implement this 
	 * function.
	 */
	abstract public function init();
	
	/**
	 * Gets an attribute that's cached locally. Use $object->update
	 * @param string $name Accessed via $obj->attribute
	 * @throw DataError when the $name doesn't exist.
	 * @return mixed The current cached value.
	 */
	public function __get($name){
		if (array_key_exists(static::$properties)){
			return $this->data[$name];
		} else {
			throw new DataError("$name doesn't exist in " . get_class($this));
		}
	}
	
	/**
	 * Sets an attribute for an object. This doesn't mean it gets put() into the
	 * database. You must call put()/putAll() to save the change into the 
	 * database. This, however, does mean it will get tracked in an array of 
	 * unsaved objects.
	 * @param string $name The name of the attribute.
	 * @param mixed $value The value's type must be the type of the declared
	 * @throw DataError Thrown when the $value doesn't pass the $validation or
	 * if the property is not registered.
	 */
	public function __set($name, $value){
		if (array_key_exists($name, static::$properties)){
			if (static::$properties[$name]->validate($value)){
				$this->data[$name] = $value;
				if (!array_key_exists($this->key, static::$objects)){
					static::$objects[$this->key] = $this;
				}
			} else {
				throw new DataError("$value does not pass validation ($name).");
			}
		} else {
			throw new DataError("$name is not a valid property for " .
								get_class($this));
		}
	}
	
	/**
	* Saves a model into the database. If successful, it will delete the object
	* from the tracked objects list.
	* @throw DataError if a required property is not filled.
	* @return boolean True if the save was successful.
	*/
	public function put(){
		global $liteDBDriver;
		if (!$liteDBDriver->connected()) $liteDBDriver->connect();
		$values = array();
		foreach (static::$properties as $name => $type){
			if ($type->required && is_null($this->data[$name])){
				throw new DataError("$name is not set for " .
									get_class($this) .
									" {$this->key}");
			}
			array_push($values, new DatabaseLulz($name,
												$this->data[$name],
												static::$properties[$name]));
		}
		$success = ((bool) $liteDBDriver->replace(static::$tablename,
												  $values,
												  $this->key));
		if ($success) unset(static::$objects[$this->key]);
		return $success;
	}
	
	/**
	 * Checks if this object instance is saved in the database. Different from
	 * if it is changed. (You have to keep track of that.)
	 * @return boolean True if there's this record for this in the database.
	 */	
	public function saved(){
		global $liteDBDriver;
		$result = static::get($this->key);
		
	}
	
	/**
	 * Updates the current object from the database.
	 */
	public function update(){
		$rows = static::getRow($this->key);
		unset($rows['key']);
		foreach ($rows as $property => $value) $this->data[$property] = $value;
	}
	
}


?>
