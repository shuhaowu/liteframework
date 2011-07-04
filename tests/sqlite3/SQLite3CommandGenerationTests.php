<?php
require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm\drivers;
use lite\orm\types;

class SQLite3CommandGenerationTests extends PHPUnit_Framework_TestCase{
	private static $tablevalues;
	public static function setUpBeforeClass(){
		self::$tablevalues = array('textvalue' => array('TEXT', array('Hello', new types\StringProperty())),
								 'intvalue' => array('INTEGER', array(24, new types\IntegerProperty())),
								 'floatvalue' => array('FLOAT', array(3.14, new types\FloatProperty())),
								 'blobvalue' => array('BLOB', array(b'lol', new types\BlobProperty())),
								 'stringarrayvalue' => array('TEXT', array('cool;stuff', new types\StringListProperty())),
								 'datetimevalue' => array('INTEGER', array(1300309200, new types\DateTimeProperty()))); 
	}
	
	
	public function setUp(){
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->returnSQL = true;
	}
	
	public function testInsert(){
		
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
		foreach (self::$tablevalues as $tablename => $value){
			array_push($sqls, trim($this->driver->insert($tablename, array('value'=>$value[1]))));
		}
		$this->assertEquals($len, count($sqls), 'The amount of SQL statements are incorrect. Please reconfigure tests');
		
		for ($i=0; $i<$len; $i++){
			$this->assertEquals($sqlscheck[$i], $sqls[$i]);
		}
	}
	
	public function testUpdate(){
		
		$sqls = array();
		$sqlscheck = array('UPDATE textvalue SET value = ? WHERE key = ?',
						   'UPDATE intvalue SET value = ? WHERE key = ?',
						   'UPDATE floatvalue SET value = ? WHERE key = ?',
						   'UPDATE blobvalue SET value = ? WHERE key = ?',
						   'UPDATE stringarrayvalue SET value = ? WHERE key = ?',
						   'UPDATE datetimevalue SET value = ? WHERE key = ?');
		$len = count($sqlscheck);
		foreach (self::$tablevalues as $tablename => $value){
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
	
	public function testFilter(){
		$sql = trim($this->driver->filter(
			'sometable', array('column1', 'column2'), 
			array('key'=>'somekey', 'mew' => 24), 1000, 0, 'mew', 'ASC'));
		
		$this->assertEquals('SELECT column1, column2 FROM sometable WHERE key = ? AND mew = ? ORDER BY mew ASC LIMIT 0, 1000', $sql);
		
		$sql = trim($this->driver->filter(
					'sometable', array('column1', 'column2'), 
					array('key'=>'somekey', 'mew' => 24)));
		
		$this->assertEquals('SELECT column1, column2 FROM sometable WHERE key = ? AND mew = ? LIMIT 0, 1000', $sql);
	}
	
	public function testExclude(){
		$sql = trim($this->driver->exclude(
					'sometable', array('column1', 'column2'), 
		array('key'=>'somekey', 'mew' => 24), 1000, 0, 'mew', 'ASC'));
		
		$this->assertEquals('SELECT column1, column2 FROM sometable WHERE key != ? AND mew != ? ORDER BY mew ASC LIMIT 0, 1000', $sql);
		
		$sql = trim($this->driver->exclude(
							'sometable', array('column1', 'column2'), 
		array('key'=>'somekey', 'mew' => 24)));
		
		$this->assertEquals('SELECT column1, column2 FROM sometable WHERE key != ? AND mew != ? LIMIT 0, 1000', $sql);
	}
	
	public function testGet(){
		$sql = trim($this->driver->get('sometable', array('column1', 'column2'), 'lolkey'));
		$this->assertEquals('SELECT column1, column2 FROM sometable WHERE key = ? LIMIT 0, 1', $sql);
	}
}
?>