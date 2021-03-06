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

	protected static $things = array('default', 'required', 'validator');

	/**
	 * Creates a new property object.
	 * @param array $param Parameters to be passed in.
	 * It's a key=>value dictionary with the key of 'default', '
	 * required', and 'validator'. 'validator' should point to an anonymous
	 * function. 'required' should point to a boolean. 'default' points to the
	 * default value.
	 */
	public function __construct(array $param=array()){
		foreach (static::$things as $var){
			if (array_key_exists($var, $param)) $this->$var = $param[$var];
		}
	}

	protected function classvalidate($value){
		return true;
	}

	/**
	 * Validates the value given a validator.
	 * @param mixed $value A value to validate.
	 */
	public function validate($value){
		if (!$this->validator) $customvalidate = true;
		else $customvalidate = $this->validator($value);
		return $customvalidate && $this->classvalidate($value);
	}

	/**
	 * Construct a SQL friendly value.
	 * @param mixed $value A value to construct.
	 * @return string the SQL friendly string will be returned.
	 */
	public function sqlValue($value){
		return $value;
	}

	/**
	 * Converts from SQL friendly value back to a real value.
	 */
	public function realValue($value){
		return $value;
	}
}

class StringProperty extends BasePropertyType{
	public $type = Types::STRING;
	protected function classvalidate($value){
		return is_string($value);
	}
}

class IntegerProperty extends BasePropertyType{
	public $type = Types::INTEGER;
	protected function classvalidate($value){
		return is_int($value);
	}
}

class FloatProperty extends BasePropertyType{
	public $type = Types::FLOAT;
	protected function classvalidate($value){
		return is_float($value);
	}
}

class BlobProperty extends BasePropertyType{
	public $type = Types::BLOB;

}

class StringListProperty extends BasePropertyType{
	public $type = Types::STRING_LIST;
	public function sqlValue($value){
		return implode(';', $value);
	}

	protected function classvalidate($value){
		return is_array($value);
	}

	public function realValue($value){
		return explode(';', $value);
	}
}

class BooleanProperty extends BasePropertyType{
	public $type = Types::BOOLEAN;
	protected function classvalidate($value){
		return is_bool($value);
	}
	public function realValue($value){
		return (bool) $value;
	}
}

class ReferenceProperty extends BasePropertyType{
	public $type = Types::REFERENCE;
	public $reference_class = null;
	protected static $things = array('default', 'required', 'validator', 'reference_class');
	public function classvalidate($value){
		return is_a($value, $this->reference_class);
	}

	public function realValue($value){
		$manager = call_user_func(array($this->reference_class, 'getManager'));
		$model = $manager->get($value);
		// $model->update(); // Required??
		return $model;
	}

	public function sqlValue($value){
		return $value->getKey();
	}
}

class ReferencesCollectionProperty extends ReferenceProperty{
	public $type = Types::REFERENCES_COLLECTION;

	public function classvalidate($value){
	}

	public function realValue($value){
	}

	public function sqlValue($value){
	}
}

class DateTimeProperty extends BasePropertyType{
	public $type = Types::DATETIME;
	public function sqlValue($value){
		//TODO: Find
	}
}
?>
