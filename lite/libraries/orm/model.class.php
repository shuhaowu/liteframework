<?php
/**
 * The Model class
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;
use Exception;
use lite\orm\driver\DatabaseError;

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
		if (!static::$locked){
			throw new LockError('The model "' . get_class() . 
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
	
	/**
	* Gets the model instance given keys. 
	* @param mixed $keys A list of keys or a single key.
	* @return \lite\orm\Model
	*/
	public static function get($keys){
		global $liteDBDriver;
		if (gettype($keys) == 'string') $keys = array($keys);
		$resultArray = array();
		$columns = array_keys(static::$properties);
		$args = array();
		foreach ($keys as $key){
			
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
	 * @throws LockError You cannot initialize unless the class has been locked.
	 * @throws InvalidKeyError When the key specified is not a valid key.
	 */
	public final function __construct($key=null){
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
		
		if (!static::$locked) throw new LockError('The model "' . get_class() . 
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
			throw new DataError("$name doesn't exist in " . get_class());
		}
	}
	
	/**
	 * Sets an attribute for an object. This doesn't mean it gets put() into the
	 * database. You must call put()/putAll() to save the change into the 
	 * database. This, however, does mean it will get tracked in an array of 
	 * unsaved objects.
	 * @param string $name The name of the attribute.
	 * @param mixed $value The value's type must be the type of the declared
	 * @throw DataError Thrown when the $value doesn't pass the $validation.
	 */
	public function __set($name, $value){
		if (array_key_exists(static::$properties)){
			if (static::$properties[$name]->validate($value)){
				$this->data[$name] = $value;
			} else {
				throw new DataError("$value does not pass validation ($name).");
			}
		}
	}
	
	/**
	* Saves a model into the database. If successful, it will delete the object
	* from the tracked objects list.
	* @return boolean True if the save was successful.
	*/
	public function put(){
		global $liteDBDriver;
		$values = array();
		foreach (static::$properties as $name => $type){
			$values[$name] = array($this->data[$name], $type);
		}
		$success = ((bool) $liteDBDriver->replace(static::tablename,
												  $values,
												  $this->key));
		if ($success) unset(static::$objects[$key]);
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
		
	}
	
}


?>
