<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace lite\orm\drivers;
use \lite\orm\drivers\DatabaseDriver;


/**
 * The driver of the SQLite.
 *
 * @author Shuhao Wu
 * @package \lite\orm\drivers
 */
class SQLite implements DatabaseDriver{
	private $database;
	private $prefix;
	private $db = null;
	
	
	public static function getType($type){
		switch ($type){
			case 'string':
				return \SQLITE3_TEXT;
			break;
			case 'float':
				return \SQLITE3_FLOAT;
			break;
			case 'blob':
				return \SQLITE3_BLOB;
				break;
			case 'int':
				return \SQLITE3_INTEGER;
			break;
			case 'stringarray':
				return \SQLITE3_TEXT;
			break;
			case '':
			break;

			default:
				return false;
		}
	}
	
	public function __construct($database, $username, $password, $host, $prefix){
		if (!file_exists($database)){
			throw new DatabaseNotFound("$database cannot be found!");
		}
		$this->database = $database;
		$this->prefix = $prefix;
	}
	
	public function connect(){
		if (!$this->db){
			$this->db = SQLite3($this->database);
			
		} 
	}
	
	public function disconnect(){
		if ($this->db){
			return $this->db->close();
		}
	}
	
	
	private function bindValuesToStatements(&$values, &$stmt){
		$i = 1;
		foreach ($values as $key => $value){
			$stmt->bindValue($i, $value->sqlFriendlyValue(), self::getType($value->type));
			$i++;
		}
	}
	
	
	
	public function insert($tablename, $values){
		$sql = "INSERT INTO $tablename "; // Insert into part.
		$columns = array_keys($values); // Get the column names
		$len = count($columns); // Get the length of the columns.
		
		// Get populate the columns.
		$columns = '(' . implode(', ', array_values($columns)) . ')'; 
		
		// Populate the values for prepared statement.
		$sql .= $columns . ' VALUES ';
		$values = '(?' . str_repeat(', ?', $len-1) . ')';
		$sql .= $values;
		
		// Prepare and bind values
		$stmt = $this->db->prepare($sql);
		$this->bindValuesToStatements($values, $stmt);
		
		// Executes.
		$result = $stmt->execute();
		
		// Check for affected rows.
		if ($result->numColumns() && $result->columnType(0) != SQLITE3_NULL){
			return true;
		} else {
			return false;
		}
	}
	
	
	public function update($tablename, $values, $id=NULL);
	public function delete($tablename, $id);
	public function replace($tablename, $values);
	public function directaccess($args=array());
	public function select($conditions, $limit=1000, $orderby=null);
}

?>
