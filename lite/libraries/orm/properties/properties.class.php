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
	protected $default = null;
	protected $required = false;
	protected $validator = false;
	
	/**
	 * Creates a new property object.
	 * @param array $param Parameters to be passed in. 
	 * It's a key=>value dictionary with the key of 'default', '
	 * required', and 'validator'
	 */
	public function __construct(array $param){		
		$things = array('default', 'required', 'validator');
		foreach ($things as $var){
			if (array_key_exists($var, $param)) $this->$var = $param[$var];
		}
	}
	
	public function validate($value){
		return $this->validator($value);
	}
	
	public function createValueObject($value){
		if ($this->validate($value)){
			$type = $this->type;
			$cls = "\\lite\\orm\\{$type}";
			return new $cls;
		} else {
			return false;
		}
	}
	
	
}

?>
