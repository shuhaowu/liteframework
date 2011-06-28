<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace lite\orm\drivers;
use \lite\orm\drivers\DatabaseDriver;
use PDO, PDOException;

/**
 * Abstract class for PDO based databases.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @package \lite\orm\drivers
 */
abstract class GenericPDO implements DatabaseDriver{
	protected $pdoArgString;
	protected $conn;
	protected $prefix;
	
	public static function convertToPDOType($propertytype){
		
	}
	
	abstract public function __construct($database, 
										 $username, 
										 $password, 
										 $host, 
										 $prefix);
	
	abstract public function directaccess(); // must use func_get_args();
	
	abstract public function replace($tablename, $values);
	
	public function connect(){
		try{
			$this->conn = new PDO($this->pdoArgString);
			$this->conn->setAttribute(PDO::ATTR_ERRMODE, 
									  PDO::ERRMODE_EXCEPTION);  
			return true;
		} catch (PDOException $e){	
			return $e;
		}
	}
	
	
	public function disconnect(){
		$this->conn = null;
	}
	
	protected function executeAndReturn(&$stmt, &$values){
		return $stmt->execute($values) ? $stmt->rowCount() : 0;
	}
	
	public function insert($tablename, $values){
		$sql = "INSERT INTO $tablename ";
		$columns = array_keys($values);
		$values = array_values($values);
		$len = count($columns);
		
		// populate the columns
		$sql .= '(' . implode(', ', array_values($columns)) . ') VALUES';
		
		// populate the values
		$sql .= '(?' . str_repeat(', ?', $len-1) . ')';
				
		// Prepare and bind values
		$stmt = $this->conn->prepare($sql);
		
		// Execute and return row counts or 0. Will raise exception if fails.
		return $this->executeAndReturn($stmt, $values);
	}
	
	
	public function update($tablename, $values, $key){
		// Initial statement
		$sql = "UPDATE $tablename SET ";
		
		// Populate the columns and ? for values
		foreach ($values as $column => $value){
			$sql .= "$column = ?, ";
		}
		
		// Eliminate the ending ', '
		$sql = substr($sql, 0, -2);
		
		// WHERE key = ?
		$sql .= "WHERE key = ?";
		$stmt = $this->conn->prepare($sql);
		$values = array_values($values);
		array_push($values, $key);
		
		return $this->executeAndReturn($stmt, $values);
	}
	
	public function delete($tablename, $key){
		$sql = "DELETE FROM $tablename WHERE key=?";
		$values = array($key);
		return $this->executeAndReturn($stmt, $values);
	}
	
	
	public function select(){
		
	}
	
	public function get($tablename, $key){
		
	}
}


/**
 * The driver of the SQLite.
 *
 * @author Shuhao Wu
 * @package \lite\orm\drivers
 */
class SQLite extends GenericPDO{
	public function __construct($database, $username, $password, $host, $prefix){
		if (file_exists($database) || $database == ':memory:'){
			$this->pdoArgString = "sqlite:$database";
			$this->prefix = $prefix;
		} else {
			throw new DatabaseNotFound("$database is not a valid file.");
		}
	}
	
	public function directaccess(){
		$args = func_get_args();
		$sql = array_shift($args);
	}
	
	abstract public function replace($tablename, $values);
}

?>
