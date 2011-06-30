<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm\drivers;
use lite\orm\types;

$tablevalues = array('textvalue' => array('TEXT', array('Hello', new types\StringProperty())),
					 'intvalue' => array('INTEGER', array(24, new types\IntegerProperty())),
					 'floatvalue' => array('FLOAT', array(3.14, new types\FloatProperty())),
					 'blobvalue' => array('BLOB', array(b'lol', new types\BlobProperty())),
					 'stringarrayvalue' => array('TEXT', array('cool;stuff', new types\StringListProperty())),
					 'datetimevalue' => array('INTEGER', array(1300309200, new types\DateTimeProperty())));


class SQLite3DriverTest extends PHPUnit_Framework_TestCase{							
	public function setUp(){
		global $tablevalues;
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->connect();
		
		$this->driver->directaccess("CREATE TABLE IF NOT EXISTS multitable (key VARCHAR(64), lolvalue TEXT, mewvalue INTEGER, moovalue FLOAT)");
		foreach ($tablevalues as $tablename => $type){
			$this->driver->directaccess("CREATE TABLE IF NOT EXISTS $tablename (key VARCHAR(64), value {$type[0]})");	
		}
	}
	
	public function tearDown(){
		$this->driver->disconnect();
		$this->driver = null;
	}
	
	public function testInsert(){
		global $tablevalues;
		$key = 'someinsertkey';
		foreach ($tablevalues as $tablename => $value){
			$this->driver->insert($tablename, array('key' => array($key, new types\StringProperty()), 'value'=>$value[1]));
			$res = $this->driver->directaccess("SELECT key, value FROM $tablename LIMIT 1");
			$res = $res[0];
			$row = $res->fetchArray(SQLITE3_ASSOC);
			$this->assertEquals($value[1][0], $row['value']);
			$this->assertEquals($key, $row['key']);
		}
		$this->driver->insert('multitable', 
							  array('lolvalue'=>array('wtf', new types\StringProperty()), 
									'mewvalue'=>array(24, new types\IntegerProperty()), 
									'moovalue'=>array(3.14, new types\FloatProperty()),
									'key' => array($key, new types\StringProperty())));
		
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('wtf', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals($key, $row['key']);
	}
	
	public function testUpdate(){
		$key = 'someupdatekey';
		$this->driver->insert('multitable',	array('lolvalue'=>array('wtf', new types\StringProperty()),
												  'mewvalue'=>array(24, new types\IntegerProperty()), 
												  'moovalue'=>array(3.14, new types\FloatProperty()),
												  'key' => array($key, new types\StringProperty())));
		
		$changes = $this->driver->update('multitable', array('lolvalue' => array('lol', new types\StringProperty())), $key);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('lol', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals($key, $row['key']);
	}
	
	public function testDelete(){
		$key = 'somedeletekey';
		$this->driver->insert('multitable',	array('lolvalue'=>array('wtf', new types\StringProperty()),
												  'mewvalue'=>array(24, new types\IntegerProperty()), 
												  'moovalue'=>array(3.14, new types\FloatProperty()),
												  'key' => array($key, new types\StringProperty())));
		
		$changes = $this->driver->delete('multitable', $key);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals(false, $row);
	}
}


class SQLite3SQLTest extends PHPUnit_Framework_TestCase{
	
	public function setUp(){
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->returnSQL = true;
	}
	
	public function testInsert(){
		global $tablevalues;
		$sqls = array();
		$sqlscheck = array('INSERT INTO textvalue (value) VALUES (?)',
						   'INSERT INTO intvalue (value) VALUES (?)',
						   'INSERT INTO floatvalue (value) VALUES (?)',
						   'INSERT INTO blobvalue (value) VALUES (?)',
						   'INSERT INTO stringarrayvalue (value) VALUES (?)',
						   'INSERT INTO datetimevalue (value) VALUES (?)');
		
		$insertFunc = function($tablename, $values){
			return $this->driver->insert($tablename, $values);
		};
		$len = count($sqlscheck);
		foreach ($tablevalues as $tablename => $value){
			array_push($sqls, trim($this->driver->insert($tablename, array('value'=>$value[1]))));
		}
		$this->assertEquals($len, count($sqls), 'The amount of SQL statements are incorrect. Please reconfigure tests');
		
		for ($i=0; $i<$len; $i++){
			$this->assertEquals($sqlscheck[$i], $sqls[$i]);
		}
	}
	
	public function testUpdate(){
		global $tablevalues;
		$sqls = array();
		$sqlscheck = array('UPDATE textvalue SET value = ? WHERE key = ?',
						   'UPDATE intvalue SET value = ? WHERE key = ?',
						   'UPDATE floatvalue SET value = ? WHERE key = ?',
						   'UPDATE blobvalue SET value = ? WHERE key = ?',
						   'UPDATE stringarrayvalue SET value = ? WHERE key = ?',
						   'UPDATE datetimevalue SET value = ? WHERE key = ?');
		$len = count($sqlscheck);
		foreach ($tablevalues as $tablename => $value){
			array_push($sqls, trim($this->driver->update($tablename, array('value'=>$value[1]), 'lolkey')));
		}
		$this->assertEquals($len, count($sqls), 'The amount of SQL statements are incorrect. Please reconfigure tests');
		
		for ($i=0; $i<$len; $i++){
			$this->assertEquals($sqlscheck[$i], $sqls[$i]);
		}
	}
	
	public function testDelete(){
		$sql = trim($this->driver->delete('sometable', 'somekey'));
		$this->assertEquals('DELETE FROM sometable WHERE key = ?', $sql);
	}
	
	public function testReplace(){
		$sql = trim($this->driver->replace('sometable', array('lol' => array('cool', new types\StringProperty()),
															  'mew' => array(123, new types\IntegerProperty())), 'somekey'));
		$this->assertEquals('INSERT OR REPLACE INTO sometable (key, lol, mew) VALUES (?, ?, ?)', $sql);
	}
}

?>
