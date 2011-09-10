<?php
/**
 * The Query class. http://code.google.com/appengine/docs/python/datastore/queryclass.html
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;
use \lite\orm\drivers\DatabaseLulz;

/**
 * This is pretty much Google App Engine's Query class. Available at 
 * http://code.google.com/appengine/docs/python/datastore/queryclass.html
 * However, this may be a bit different, such as not supporting passing
 * a model instance to get a query object.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */
class Query{
	private $tablename;
	private $keyonly;
	private $requirements;
	private $orderby;
   /**
	* This creates a new Query object. Note: Different from GAE's interface.
	* Instead of manually creating this, use the static methods provided.
	* @param \ReflectionClass $class A reflection class for the Model class.
	* @param boolean $keyOnly If only get keys
	*/
	public function __construct($class, $keyonly=false){
		$this->modelClass = $class;
		$this->keyonly = $keyonly;
		$this->vars = $this->modelClass->getStaticProperties();
		$this->params = array();
		$this->orderby = false;
		$this->order = false;
	}
	
	public function filter($propertyOperator, $value){
		$propertyOperator = explode(' ', $propertyOperator);
		$name = $propertyOperator[0];
		$operator = $propertyOperator[1];
		$valuetype = $this->vars['properties'][$name];
		$moo = new DatabaseLulz($name, $value, $valuetype, $operator); // lols
		array_push($this->params, $moo);
		return $this;
	}
	
	public function order($property){
		$this->order = substr($property, 0, 1);
		$hasOrderOperator = true;
		switch ($this->order){
			case '-':
				$this->order = 'DESC';
			break;
			case '+':
				$this->order = 'ASC';
			break;
			default:
				$hasOrderOperator = false;
				$this->order = 'ASC';
		}
		if ($hasOrderOperator){
			$this->orderby = substr($property, 1);
		} else {
			$this->orderby = $property;
		}
		return $this;
	}
	
	public function get(){
		$results = $this->fetch(1);
		return $results[0];
	}
	
	public function fetch($limit=1000, $offset=0){
		$results = array();
		$driver = Model::getDefaultDriver();
		$columns = ($this->keyonly) ? 
					array() : array_keys($this->vars['properties']);

		$rows = $driver->select($this->vars['tablename'], 
								$columns,
								$this->params,
								$limit, $offset,
								$this->orderby,
								$this->order);
		foreach ($rows as $row){
			if ($this->keyonly){
				array_push($results, $row['key']);
			} else {
				if (array_key_exists($row['key'], $this->vars['objects'])){
					$obj = $this->vars['objects'];
					$obj->updateWithRow($row);
				} else {
					$obj = $this->modelClass->newInstance($row['key'], $row);
				}
				array_push($results, $obj);
			}
		}
		return $results;
	}
	
	public function count(){
		$driver = Model::getDefaultDriver();
		return $driver->count($this->vars['tablename'], $this->params);
	}
	
	public function reset(){
		$this->orderby = false;
		$this->order = false;
		$this->params = array();
	}
}

?>