<?php
/**
 * Properties class
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;

/**
 * The base property type that everything else extended from.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
class BasePropertyType{
	/**
	 * The type of the property.
	 * @var string 
	 */
	public $type;
	public $default = null;
	public $required = false;
	public $validator = false;
	
	private static $things = array('default', 'required', 'validator');
	
	/**
	 * Creates a new property object.
	 * @param array $param Parameters to be passed in. 
	 * It's a key=>value dictionary with the key of 'default', '
	 * required', and 'validator'. 'validator' should point to an anonymous
	 * function. 'required' should point to a boolean. 'default' points to the
	 * default value.
	 */
	public function __construct(array $param=array()){		
		foreach (self::$things as $var){
			if (array_key_exists($var, $param)) $this->$var = $param[$var];
		}
	}
	
	/**
	 * Validates the value given a validator.
	 * @param mixed $value A value to validate.
	 */
	public function validate($value){
		return $this->validator($value);
	}
}

?>
