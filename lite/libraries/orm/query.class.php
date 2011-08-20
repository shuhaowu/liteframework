<?php
/**
 * The Query class. http://code.google.com/appengine/docs/python/datastore/queryclass.html
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;

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
	public function __construct($class, $keyOnly=false){
		$this->modelClass = $class;
		$this->keyOnly = $keyOnly;
		$this->vars = $this->modelClass->getStaticProperties();
		$this->params = array();
		$this->orderby = false;
		$this->order = false;
	}
	
	public function filter($propertyOperator, $value){
		
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
	}
	public function get(){
		$results = $this->fetch(1);
		return $results[0];
	}
	
	public function fetch($limit, $offset=0){
		$results = array();
		$driver = Model::getDefaultDriver();
		$columns = array_keys($this->vars['properties']);
		$rows = $driver->select($this->vars['tablename'], 
								$columns,
								$this->params,
								$limit, $offset,
								$this->orderby,
								$this->order);
		foreach ($rows as $row){
			if (array_key_exists($row['key'], $this->vars['objects'])){
				$obj = $this->vars['objects'];
				$obj->updateWithRow($row);
			} else {
				$obj = $this->modelClass->newInstance($row['key'], $row);
			}
			array_push($results, $obj);
		}
		return $results;
	}
	
	public function count(){
		$driver = Model::getDefaultDriver();
		return $driver->count($this->var['tablenames'], $this->requirements);
	}
}

?>
