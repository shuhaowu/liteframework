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
	
	public function insert($tablename, $values);
	public function update($tablename, $values, $key);
	public function delete($tablename, $key);
	
	// Per database implementation on the most effective one.
	public function replace($tablename, $values, $key);
	public function directaccess(); // must use func_get_args();
	public function filter($tablename, $columns, 
						   $args, $limit=1000, $offset=0,
						   $ordercolumn=false, $order=false,
						   $flag=Flags::F_AND);
	// public function exclude($tablename, $values, $flag=Flags::F_AND);
	// Need a better implementation
	// public function select($tablename, $conditions, $limit=1000, $orderby=null);
	// public function get($tablename, $key);
}

class DatabaseNotFound extends \Exception {}
class DatabaseError extends \Exception {}
?>