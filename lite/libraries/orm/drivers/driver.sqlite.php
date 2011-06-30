<?php
namespace lite\orm\drivers;
use SQLite3;

use \lite\orm\types\StringProperty;
use \lite\orm\types\Types;

/**
 * The driver of the SQLite.
 *
 * @author Shuhao Wu
 * @package \lite\orm\drivers
 */
class SQLite implements DatabaseDriver{
	private $database;
	private $db = null;
	public $returnSQL = false;
	

	public static function getType(&$propertytype){
		switch ($propertytype->type){
			case Types::STRING: case Types::STRING_LIST: 
			case Types::REFERENCE:	case Types::REFERENCES_COLLECTION:
				return \SQLITE3_TEXT;
			break;
			case Types::FLOAT:
				return \SQLITE3_FLOAT;
			break;
			case Types::BLOB:
				return \SQLITE3_BLOB;
				break;
			case Types::INTEGER: case Types::BOOLEAN:
			case Types::DATETIME:
				return \SQLITE3_INTEGER;
			break;

			default:
				return false;
		}
	}

	public function __construct($database, $username, $password, $host){
		if (!file_exists($database) && $database != ':memory:'){
			throw new DatabaseNotFound("$database cannot be found!");
		}
		$this->database = $database;
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


	private function bindParamsToStatements(&$values, &$stmt){
		$i = 1;
		foreach ($values as $value){
			$stmt->bindParam($i, $value[0], self::getType($value[1]));
			$i++;
		}
	}
	
	private function prepBindExecute($sql, &$values){
		if ($this->returnSQL) return $sql;
		
		$stmt = $this->db->prepare($sql);
		$this->bindParamsToStatements($values, $stmt);
		
		$result = $stmt->execute();
		$errcode = $this->db->lastErrorCode();
		if ($errcode){
			throw new DatabaseError($this->db->lastErrorMsg(), 
									$errcode);
		} else {
			return $this->db->changes();
		}
	}


	public function insert($tablename, $values){
		$sql = "INSERT INTO $tablename ";
		$columns = array_keys($values);

		$len = count($columns); // Get the length of the columns.

		// Populate the columns.
		$sql .= '(' . implode(', ', $columns) . ') VALUES '; 

		// Populate the values
		$sql .= '(?' . str_repeat(', ?', $len-1) . ')';
		return $this->prepBindExecute($sql, array_values($values));
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
		$sql .= " WHERE key = ?";
		
		$values['key'] = array($key, new StringProperty());
		return $this->prepBindExecute($sql, array_values($values));
	}
	
	
	public function delete($tablename, $key){
		$sql = "DELETE FROM $tablename WHERE key = ?";
		$values = array(array($key, new StringProperty()));
		return $this->prepBindExecute($sql, $values);
	}
	
	
	public function replace($tablename, $values, $key){
		$sql = "INSERT OR REPLACE INTO $tablename ";
		$columns = array_keys($values);

		$len = count($columns); // Get the length of the columns.

		// Populate the columns.
		$sql .= '(key, ' . implode(', ', $columns) . ') VALUES '; 

		// Populate the values
		$sql .= '(?' . str_repeat(', ?', $len) . ')';
		
		$values = array_values($values);
		array_unshift($values, array($key, new StringProperty()));
		return $this->prepBindExecute($sql, $values);
	}
	
	public function directaccess(){
		$args = func_get_args();
		$sql = array_shift($args);
		$stmt = $this->db->prepare($sql);
		$this->bindParamsToStatements($args, $stmt);
		
		$result = $stmt->execute();
		return array($result, $this->db->changes(), $this->db->lastErrorMsg());
	}
	
	public function filter($tablename, $columns, $args, $flag=Flags::F_AND){
		$sql = 'SELECT ' . implode(', ', $columns) . " FROM $tablename";
		$len = count($args);
		if ($len > 0){
			switch($flag){
				case Flags::F_AND:
					$operator = 'AND';
				break;
				case Flags::F_OR:
					$operator = 'OR';
				break;
			}
			
			$sql .= ' WHERE';
			$i = 1;
			foreach ($args as $column => $value){
				$sql .= " $column = ? ";
				if ($i != $len) $sql .= $operator;
				$i++;
			}
		}
	}
	
	// Missing select...
}
?>