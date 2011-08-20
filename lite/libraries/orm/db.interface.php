<?php
/**
 * Database Driver Interface
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm\drivers
 */

namespace lite\orm\drivers;
/**
 * This interface is for all the results that's returned from the ->insert,
 * ->update ... etc statements, if there is any results to be returned.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package \lite\orm\drivers
 */
abstract class ResultRows implements \Iterator{
	
}

class Flags{
	const F_AND = 1;
	const F_OR = 2;
}

/**
 * This interface shows all the appropriate variables and functions a class that
 * uses different backends for database must have. This means that if the app
 * was to switch backend, the operations should be unaffected.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package \lite\orm\drivers
 */
interface DatabaseDriver{
	public function __construct($database, $username, $password, $host);
	
	public function connect();
	
	public function disconnect();
	
	public function insert($tablename, $params);
	public function update($tablename, $params, $key);
	public function delete($tablename, $key);
	
	// Per database implementation on the most effective one.
	public function replace($tablename, $params, $key);
	public function directaccess(); // must use func_get_args();
	
	public function select($tablename, $columns, 
						   $params, $limit=1000, $offset=0,
						   $ordercolumn=false, $order=false,
						   $flag=Flags::F_AND);
						   
	public function count($tablename, $params, $flag=Flags::F_AND);
	
	public function get($tablename, $columns, $key);
	public function connected();
}

/**
 * The comparison operation such as column = 'value' or column LIKE 'value'
 * Can't think of a better name.
 */
class DatabaseLulz{
	public $name;
	public $value;
	public $valuetype;
	public $operation;
	public function __construct($name, $value, $valuetype, $operation='='){
		$this->name = $name;
		$this->value = $value;
		$this->valuetype = $valuetype;
		$this->operation = $operation;
	}

	public function type(){
		return $this->valuetype->type;
	}
}

class DatabaseNotFound extends \Exception {}
class DatabaseError extends \Exception {}

?>
