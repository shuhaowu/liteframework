<?php
namespace lite\orm\drivers;
use SQLite3;

use \lite\orm\BasePropertyType;

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


	public static function getType($propertytype){
		switch ($propertytype->type){
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
			$this->db = new SQLite3($this->database);
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
			$stmt->bindValue($i, $value[0], self::getType($value[1]));
			$i++;
		}
	}
	
	private function prepBindExecute($sql, &$values){
		$stmt = $this->db->prepare($sql);
		$this->bindValuesToStatements($values, $stmt);
		
		$result = $stmt->execute();
		return !!$result;
	}


	public function insert($tablename, $values){
		$sql = "INSERT INTO $tablename ";
		$columns = array_keys($values);

		$len = count($columns); // Get the length of the columns.

		// Populate the columns.
		$sql .= '(' . implode(', ', $columns) . ') VALUES '; 

		// Populate the values
		$sql .= '(?' . str_repeat(', ?', $len-1) . ')';

		return $this->prepBindExecute($sql, $values);
	}


	public function update($tablename, $values, $key){
		$sql = "UPDATE $tablename SET ";
		// Populate the columns and ? for values
		foreach ($values as $column => $value){
			$sql .= "$column = ?, ";
		}
		
		// Eliminate the ending ', '
		$sql = substr($sql, 0, -2);
		
		// WHERE key = ?
		$sql .= "WHERE key = ?";
		
		$values['key'] = array($key, new StringProperty());
		
		return $this->prepBindExecute($sql, $values);
	}
	
	
	public function delete($tablename, $key){
		$sql = "DELETE FROM $tablename WHERE key=?";
		$values = array($key, new StringProperty());
		return $this->prepBindExecute($sql, $values);
	}
	
	public function replace($tablename, $values){
		
	}
	
	public function directaccess(){
		
	}
	
	// Missing select...
}
?>