<?php
/**
 * Properties class
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm\types;

class Types{
	const STRING = 0;
	const INTEGER = 1;
	const FLOAT = 2;
	const BLOB = 3;
	const STRING_LIST = 4;
	const BOOLEAN = 5;
	const REFERENCE = 6;
	const REFERENCES_COLLECTION = 7;
	const DATETIME = 8;
}

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

class StringProperty extends BasePropertyType{
	public $type = Types::STRING;
}

class IntegerProperty extends BasePropertyType{
	public $type = Types::INTEGER;
}

class FloatProperty extends BasePropertyType{
	public $type = Types::FLOAT;
}

class BlobProperty extends BasePropertyType{
	public $type = Types::BLOB;
}

class StringListProperty extends BasePropertyType{
	public $type = Types::STRING_LIST;
}

class BooleanProperty extends BasePropertyType{
	public $type = Types::BOOLEAN;
}

class ReferenceProperty extends BasePropertyType{
	public $type = Types::REFERENCE;
}

class ReferencesCollectionProperty extends BasePropertyType{
	public $type = Types::REFERENCES_COLLECTION;
}

class DateTimeProperty extends BasePropertyType{
	public $type = Types::DATETIME;
}
?>
