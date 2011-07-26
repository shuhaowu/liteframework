<?php
namespace lite\orm\drivers;
use SQLite3;

use \lite\orm\types\StringProperty;
use \lite\orm\types\Types;

class SQLiteResultRows extends ResultRows{
	private $result;
	private $position = 0;
	private $currentRow;
	private $len = 0;
	public function __construct($result){
		$this->result = $result;
		$this->position = 0;
		$this->currentRow = null;
		$this->len = 0;
		while ($this->result->fetchArray()){
			$this->len++;
		}
	}

	public function length(){
		return $this->len;
	}
	
	public function current(){
		return $this->currentRow;
	}
	
	public function key(){
		return $this->position;
	}
	
	public function next(){
		$this->position++;
		$this->currentRow = $this->result->fetchArray(\SQLITE3_ASSOC);
	}
	
	public function rewind(){
		$this->position = 0;
		$this->result->reset();
		$this->currentRow = $this->result->fetchArray(\SQLITE3_ASSOC);
	}
	
	public function valid(){
		return !!$this->currentRow;
	}
}


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
	private $stmt = null;

	const IDENTITY = 'sqlite';

	public static function getType($type){
		switch ($type){
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

	public function connected(){
		return (bool) $this->db;
	}

	public function disconnect(){
		if ($this->db){
			$code = $this->db->close();
			$this->db = null;
			return $code;
		}
	}

	private function bindParamsToStatements(&$params, &$stmt){
		$i = 1;
		foreach ($params as $param){
			$stmt->bindParam($i, $param->value,
							 self::getType($param->type()));
			$i++;
		}
	}
	
	public function __destruct(){
		$this->disconnect();
	}
	
	private function prepBindExecute($sql, &$params, $returnResult=false){
		if ($this->returnSQL) return trim($sql);
		
		$stmt = $this->db->prepare($sql);
		$this->bindParamsToStatements($params, $stmt);
		
		
		$result = $stmt->execute();
		$errcode = $this->db->lastErrorCode();
		
		if ($errcode){
			throw new DatabaseError($this->db->lastErrorMsg(), $errcode);
		} else {
			if ($returnResult) return $result;
			return $this->db->changes();
		}
	}


	public function insert($tablename, $params){
		$sql = "INSERT INTO $tablename ";
		$columns = array();
		$len = 0;
		foreach ($params as $param){
			array_push($columns, $param->name);
			$len++;
		}
		
		// Populate the columns.
		$sql .= '(' . implode(', ', $columns) . ') VALUES '; 

		// Populate the values
		$sql .= '(?' . str_repeat(', ?', $len-1) . ')';
		return $this->prepBindExecute($sql, $params);
	}


	private function createKeyComparison($key){
		return new DatabaseLulz('key', $key, new StringProperty(), '=');
	}

	private function getColumns(&$params){
		$columns = array();
		foreach ($params as $param){
			array_push($columns, $param->name);
		}
		return $columns;
	}

	public function update($tablename, $params, $key){
		$sql = "UPDATE $tablename SET ";
		// Populate the columns and ? for values
		$columns = $this->getColumns($params);

		$sql .= implode(' = ?, ', $columns) . ' = ?';
		
		// WHERE key = ?
		$sql .= " WHERE key = ?";
		
		array_push($params, $this->createKeyComparison($key));
		
		return $this->prepBindExecute($sql, $params);
	}
	
	public function delete($tablename, $key){
		$sql = "DELETE FROM $tablename WHERE key = ?";
		$params = array($this->createKeyComparison($key));
		return $this->prepBindExecute($sql, $params);
	}
	
	
	public function replace($tablename, $params, $key){
		$sql = "INSERT OR REPLACE INTO $tablename ";
		$columns = $this->getColumns($params);

		$len = count($params); // Get the length of the columns.

		// Populate the columns.
		$sql .= '(key, ' . implode(', ', $columns) . ') VALUES '; 

		// Populate the values
		$sql .= '(?' . str_repeat(', ?', $len) . ')';
		
		array_unshift($params, $this->createKeyComparison($key));
		return $this->prepBindExecute($sql, $params);
	}
	
	public function directaccess(){
		$args = func_get_args();
		$sql = array_shift($args);
		$stmt = $this->db->prepare($sql);
		$this->bindParamsToStatements($args, $stmt);
		
		$result = $stmt->execute();
		return array($result, $this->db->changes(), $this->db->lastErrorMsg());
	}
	
	private function selectSQL($tablename, $columns, 
						   	  $params, $limit=1000, 
						   	  $offset=0, $ordercolumn=false, 
						   	  $order=false, $flag=Flags::F_AND){
		$sql = 'SELECT key, ' . implode(', ', $columns) . " FROM $tablename WHERE ";
		$len = count($params);
		if ($len > 0){
			switch($flag){
				case Flags::F_AND:
					$operator = 'AND';
					break;
				case Flags::F_OR:
					$operator = 'OR';
					break;
				default:
					throw new \Exception("Not a valid flag: $flag");
			}
	
			foreach ($params as $param){
				if ($param != $params[$len-1]){
					$sql .= "{$param->name} {$param->operation} ? $operator ";
				} else {
					$sql .= "{$param->name} {$param->operation} ?";
				}
			}
		}
		if ($ordercolumn && $order) $sql .= " ORDER BY $ordercolumn $order ";
		$sql .= " LIMIT $offset, $limit";
		return $sql;
	}
	
		public function select($tablename, $columns, 
						   $params, $limit=1000, $offset=0,
						   $ordercolumn=false, $order=false,
						   $flag=Flags::F_AND){
		$sql = $this->selectSQL($tablename, $columns, $params, $limit,
								$offset, $ordercolumn, $order, $flag);
		
		if ($this->returnSQL) return $sql;
		$result = $this->prepBindExecute($sql, $params, true);
		return new SQLiteResultRows($result);
	}
	
	public function get($tablename, $columns, $key){
		$params = array(new DatabaseLulz('key', $key, new StringProperty()));
		return $this->select($tablename, $columns, $params, 1);
	}
}
?>
