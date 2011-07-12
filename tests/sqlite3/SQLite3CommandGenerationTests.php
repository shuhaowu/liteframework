<?php
require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm\drivers;
use lite\orm\types\StringProperty;
use lite\orm\types\IntegerProperty;
use lite\orm\drivers\DatabaseLulz;

class SQLite3CommandGenerationTests extends PHPUnit_Framework_TestCase{
	
	public function setUp(){
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->returnSQL = true;
	}
	
	public function testInsert(){
		$sqlcheck = 'INSERT INTO sometable (valueone, valuetwo, valuethree) VALUES (?, ?, ?)';
		$params = array(new DatabaseLulz('valueone', 'lolvalue', new StringProperty()),
						new DatabaseLulz('valuetwo', 'lolvalue', new StringProperty()),
						new DatabaseLulz('valuethree', 'lolvalue', new StringProperty()));
		$sql = $this->driver->insert('sometable', $params);
		$this->assertEquals($sqlcheck, $sql);
	}
	
	public function testUpdate(){
		$sqlscheck = array('UPDATE sometable SET valueone = ?, valuetwo = ? WHERE key = ?',
						  'UPDATE sometable SET lol = ? WHERE key = ?');
		$params = array();
		$params[0] = array(new DatabaseLulz('valueone', 'lolvalue', new StringProperty()),
						   new DatabaseLulz('valuetwo', 'lolvalue', new StringProperty()));
		$params[1] = array(new DatabaseLulz('lol', 'lolvalue', new StringProperty()));
		
		$sqls = array($this->driver->update('sometable', $params[0], 'somekey'),
					  $this->driver->update('sometable', $params[1], 'somekey'));
		
		
		for ($i=0; $i<count($params); $i++){
			$this->assertEquals($sqlscheck[$i], $sqls[$i]);
		}
	}
	
	public function testDelete(){
		$sql = trim($this->driver->delete('sometable', 'somekey'));
		$this->assertEquals('DELETE FROM sometable WHERE key = ?', $sql);
	}
	
	public function testReplace(){
		$sqlcheck = 'INSERT OR REPLACE INTO sometable (key, lol, mew) VALUES (?, ?, ?)';
		$params = array(new DatabaseLulz('lol', 1, new IntegerProperty()),
						new DatabaseLulz('mew', 2, new IntegerProperty()));
		$sql = $this->driver->replace('sometable', $params, 'somekey');
		$this->assertEquals($sqlcheck, $sql);
	}

	public function testSelect(){
		$sqlcheck = 'SELECT key, column1, column2 FROM sometable WHERE column1 > ? AND key = ? LIMIT 0, 1000';
		$params = array();
		$params[0] = new DatabaseLulz('column1', 1, new IntegerProperty(), '>');
		$params[1] = new DatabaseLulz('key', 'lolkey', new StringProperty());
		$sql = $this->driver->select('sometable', array('column1', 'column2'), $params);
		$this->assertEquals($sqlcheck, $sql);
	}
	
	public function testGet(){
		$sql = trim($this->driver->get('sometable', array('column1', 'column2'), 'lolkey'));
		$this->assertEquals('SELECT key, column1, column2 FROM sometable WHERE key = ? LIMIT 0, 1', $sql);
	}
}
?>
