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
	abstract public function current();
	abstract public function key()
	abstract public function next()
	abstract public function rewind()
	abstract public function valid() 
}


/**
 * This interface shows all the appropriate variables and functions a class that
 * uses different backends for database must have. This means that if the app
 * was to switch backend, the operations should be unaffected.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package \lite\orm\drivers
 */
interface DatabaseDriver{
	/**
	 * Creates a new database connection
	 * @param string $database Database name
	 * @param string $username Username to connect
	 * @param string $password Password for connecting
	 * @param string $prefix Database table prefix
	 * @param string $host Hostname
	 */
	public function __construct($database, $username, $password, $host, $prefix);
	
	/**
	 * Connects to the database.
	 * @return boolean true upon success, false upon failure.
	 */
	public function connect();
	
	/**
	 * Disconnects from the database.
	 * @return boolean true upon success, false upon failure.
	 */
	public function disconnect();
	
	/**
	 * Inserts a record into the database.
	 * @param string $tablename the name of the table
	 */
	public function insert($tablename, $values);
	public function update($tablename, $values, $id=NULL);
	public function delete($tablename, $id);
	public function replace($tablename, $values);
	public function directaccess($args=array());
	public function select($conditions, $limit=1000, $orderby=null);
}

class DatabaseNotFound extends \Exception {}

?>