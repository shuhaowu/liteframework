<?php
/**
 * The Model class
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;

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
	protected static $properties = array();
	protected static $locked = false;
	
	/**
	 * The tablename in the SQL, or other identifier.
	 * @var string
	 */
	public static $tablename;
	
	const UUID_CHARACTER_SET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	const UUID_LENGTH = 64;
	
	/**
	 * Generates a key for the primary key of the database.
	 * The primary key of the database should be the length of 64 VARCHAR.
	 * This should generate an unique key. The underlying algorithm is choose random characters
	 * 54 random characters from [a-zA-Z0-9] and add the last 10 digit of the timestamp in integer
	 * to the beginning of the string (prefix). This also should allow the time of generation.
	 * @return string A random key that has a length of 64. *Should* be unique.
	 */
	public static function generateKey(){
		$uuid = '';
		for ($i=0; $i<self::UUID_LENGTH-10; $i++) {
			$choice = rand(0, strlen(self::UUID_CHARACTER_SET)-1);
			$uuid .= substr(self::UUID_CHARACTER_SET, $choice, 1);
		}
		$uuid = substr(intval(time()), -10) . $uuid;
		return $uuid;
	}
	
	/**
	 * Validates a key. The criteria is <= 64 characters and only contains [a-zA-Z0-9] and not in the tracked objects
	 * @param string $key The key you need to validate.
	 * @return boolean
	 */
	public static function validateKey($key){
		return (strlen($key) <= 64) && (preg_match('/[a-zA-Z0-9]+/', $key)) && (!array_key_exists($key, static::$objects));
	}
	
	/**
	 * Add a property to the class if the class is not locked.
	 * @param string $name Name of the attribute.
	 * @param \lite\orm\BasePropertyType $type The type of the value to be stored in the database.
	 */
	public static function addProperty($name, $type){
		if (!static::$locked) throw new \Exception ('The model "' . get_class() . '" has been locked.');
		static::$properties[$name] = $type;
	}
	
	/**
	 * Lock the model from changes. Must lock in order to start initializing.
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
	 * Creates a new instance of the model.
	 * @param string $key A key that can pass the validateKey method. The criteria is <= 64 characters and only contains [a-zA-Z0-9] and not in the tracked objects
	 */
	public final function __construct($key=null){
		if (!static::$locked) throw new \Exception('The model "' . get_class() . '" is not locked!');
		if (!$key){
			$key = self::generateKey();
		} else {
			if (!self::validateKey($key)) throw new InvalidKeyError("$key is not a valid key.");
		}
		
		$this->key = $key;
		
		static::$objects[$key] = $this;
		$this->init();
	}
	
	/**
	 * A function called immediately after __construct. You must implement this function.
	 */
	public function init(){
		throw new \Exception("NotImplemented: This class must be subclassed!");
	}
	
	/**
	 * Saves a model into the database.
	 */
	public function put(){
		
	}
	
	/**
	 * Saves all untracked objects.
	 */
	public static function putAll(){
		
	}
	
	/**
	 * Gets an attribute.
	 * @param string $name Accessed via $obj->attribute
	 */
	public function __get($name){
		
	}
	
	/**
	 * Sets an attribute for an object. This doesn't mean it gets put() into the
	 * database. You must call put()/putAll() to save the change into the 
	 * database. This, however, does mean it will get tracked in an array of 
	 * unsaved objects.
	 * @param string $name The name of the attribute.
	 * @param mixed $value The value's type must be the type of the declared property.
	 */
	public function __set($name, $value){
		
	}
	/**
	 * Gets the model instance given keys.
	 * @param array $keys A list of keys.
	 * @return \lite\orm\Model
	 */
	public static function get(array $keys){
		
	}
	
	/**
	 * Gets an iterator that iterates through all the objects.
	 * @param boolean $keyonly If this is set to true, it returns an iterator of
	 * keys instead of all the objects, which is much more efficient.
	 * @return Iterator
	 */
	public static function all($keyonly=false){
		
	}
	
	
}


?>