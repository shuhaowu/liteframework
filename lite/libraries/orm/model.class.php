<?php
/**
 * The Model class. All model classes need to extend from this class.
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
class NotSavedError extends Exception {}
class AlreadyDeletedError extends Exception {}

/**
 * The model class that each model needs to extend from in order to function
 * correctly. The class itself also keeps track of ALL the object initialized
 * and it will serve as a local cache.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
class Model{
	protected static $objects = array();
	protected $key;
	protected $data = array();
	protected $saved = false;
	protected static $properties = array();
	protected static $locked = false;
	private static $drivers = array();
	private static $defaultdriver;
	protected $deleted = false;
	
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
	 * Adds a driver to the collection of drivers to be used for put() calls.
	 * Must be done before the lock() call.
	 * @param object $dbDriver Driver instances.
	 */
	public static function addDriver($dbDriver){
		if (!static::$locked &&
			!array_key_exists($dbDriver->name, self::$drivers)){
			
			self::$drivers[$dbDriver->name] = $dbDriver;
			return true;
		}
		return false;
	}

	/**
	 * Removes a driver. 
	 * @param string/object $dbDriver Could be the actual driver object or the
	 * $name for it.
	 * @return boolean. false if the driver $name is not found in the collection
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
		if (!static::$locked &&
			array_key_exists($dbDriver->name, self::$drivers)){

			self::$defaultdriver = $dbDriver;
			return true;
		}
		return false;
	}

	/**
	 * 
	 */
	public static function getDefaultDriver(){
		return self::$defaultdriver;
	}

	public static function unsavedObjectsCount(){
		return count(static::$objects);
	}
	
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
	 * Gets the type of a property
	 * @return \lite\orm\types\BasePropertyType
	 */
	public static function getType($property){
		if (array_key_exists($property, static::$properties)){
			return static::$properties[$property];
		} else {
			return null;
		}
	}
	
	/**
	 * Gets an iterator that iterates through all the objects.
	 * @param boolean $keyonly If this is set to true, it returns an iterator of
	 * keys instead of all the objects, which is much more efficient.
	 * @return Iterator
	 */
	public static function all($keyonly=false){
		// ==================================== //
		//  IMPLEMENT THIS FUNCTION YOU DUMMY!  //
		// ==================================== //
	}

	protected static function getOneModelRow($key){
		$columns = array_keys(static::$properties);
		$rows = self::$defaultdriver->get(static::$tablename, $columns, $key);
		$row = false;
		foreach ($rows as $row) break;
		return $row;
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
	public static function get($key){
		if (array_key_exists($key, static::$objects)){
			return static::$objects[$key];
		} else {
			$row = static::getOneModelRow($key);
			if (!$row) throw new NotSavedError("$key is not found!");
			$obj = new static($row['key'], $row);
			unset(static::$objects[$key]);
			return $obj;
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

	protected static function sqlValueToRealValue($name, $value){
		$type = static::$properties[$name];
		return $type->realValue($value);
	}
	
	
	/**
	 * Construct a new instance of your model. This class must be subclassed.
	 * @param string $key A key that will pass Model::validateKey.
	 * @param array $data The data to initialize the model with. Assosiative.
	 * You should never use this as it is used by the framework to initialize
	 * a new object fom the database. It will also set a flag that tells the
	 * model system that the object has been saved at one point. (is_saved
	 * returns true). Using this wrong may cause unexpected failures. Leave it
	 * to its default if unsure how to use this feature.
	 * @throw LockError You cannot initialize unless the class has been locked.
	 * @throw InvalidKeyError When the key specified is not a valid key.
	 */
	public final function __construct($key=null, $data=null){		
		if (!self::$defaultdriver){
			throw new DatabaseError('There is no default database driver set!');
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
		if ($data){
			$this->updateWithRow($data);
			$this->saved = true;
		}
		
		$this->init();
	}
	
	/**
	 * A function called immediately after __construct.
	 */
	public function init(){
	}

	protected function checkDeleted(){
		if ($this->deleted){
			throw new AlreadyDeletedError("Model {$this->key} has been deleted.");
		}
	}
	
	/**
	 * Gets an attribute that's cached locally. Use $object->update
	 * @param string $name Accessed via $obj->attribute
	 * @throw DataError when the $name doesn't exist.
	 * @throw AlreadyDeletedError when the model has been deleted.
	 * @return mixed The current cached value.
	 */
	public function __get($name){
		$this->checkDeleted();
		if (array_key_exists($name, static::$properties)){
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
	 * @throw
	 */
	public function __set($name, $value){
		$this->checkDeleted();
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
	 * @throw AlreadyDeletedError if it's already deleted.
	 * @return boolean True if the save was successful.
	 */
	public function put(){
		$this->checkDeleted();
		// Constructs the value to put into the database.
		$values = array();
		foreach (static::$properties as $name => $type){
			if ($type->required && is_null($this->data[$name])){
				throw new DataError("$name is not set for " .
									get_class($this) .
									" {$this->key}");
			}
			array_push($values, new DatabaseLulz($name,
									$type->sqlValue($this->data[$name]),
									static::$properties[$name]));
		}

		// Loops through all the drivers and updates it.
		$successes = array();
		foreach (self::$drivers as $name => $driver){
			if (!$driver->connected()) $driver->connect();
			$success = ((bool) $driver->replace(static::$tablename,
												$values,
												$this->key));
			$successes[$name] = $success;
			if ($driver == self::$defaultdriver){
				$successes['default'] = $success;
				if ($success){
					unset(static::$objects[$this->key]);
					$this->saved = true;
				}
			}
		}
		return $successes;
	}
	
	/**
	 * Checks if this object instance is saved in the database. Different from
	 * if it is changed. (You have to keep track of that.)
	 * @return boolean True if there's this record for this in the database.
	 */	
	public function is_saved(){
		return $this->saved;
	}

	/**
	 * Deletes this model from the database.
	 * @throw \lite\orm\NotSavedError If the model is not saved.
	 * @throw AlreadyDeletedError if the model is already deleted.
	 */
	public function delete(){
		$this->checkDeleted();
		if (!$this->saved) throw new NotSavedError("{$this->key} is not saved!");
		foreach (self::$drivers as $driver){
			$driver->delete(static::$tablename, $this->key);
			unset(static::$objects[$this->key]);
			$this->deleted = true;
			$this->saved = false;
		}
	}
	
	/**
	 * Updates with row. (Associative array)
	 */
	public function updateWithRow($row){
		unset($row['key']);
		foreach ($row as $name => $value){
			$this->data[$name] = self::sqlValueToRealValue($name, $value);
		}
	}
	
	/**
	 * Updates the current object from the database.
	 */
	public function update(){
		$this->checkDeleted();
		$this->updateWithRow(static::getOneModelRow($this->key));
	}

	/**
	 * Get the key for the model.
	 * @return string
	 */
	public function getKey(){
		return $this->key;
	}
}



?>
