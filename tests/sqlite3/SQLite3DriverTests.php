<?php
require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');


use lite\orm\drivers;
use lite\orm\types;


class SQLite3DriverTests extends PHPUnit_Framework_TestCase{			
	
	const KEY = 'unittestingkey';
	
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
		$this->driver->connect();
		
		$this->driver->directaccess("CREATE TABLE IF NOT EXISTS multitable (key VARCHAR(64) PRIMARY KEY, lolvalue TEXT, mewvalue INTEGER, moovalue FLOAT)");
		foreach (self::$tablevalues as $tablename => $type){
			$this->driver->directaccess("CREATE TABLE IF NOT EXISTS $tablename (key VARCHAR(64) PRIMARY KEY, value {$type[0]})");	
		}
	}
	
	public function tearDown(){
		$this->driver->disconnect();
		$this->driver = null;
	}
	
	private function insertSomeDataz(){
		$this->driver->insert('multitable',
							  array('lolvalue'=>array('wtf', new types\StringProperty()),
									'mewvalue'=>array(24, new types\IntegerProperty()), 
									'moovalue'=>array(3.14, new types\FloatProperty()),
									'key' => array(self::KEY, new types\StringProperty())
									)
							 );
	}
	
	public function testInsert(){
		foreach (self::$tablevalues as $tablename => $value){
			$this->driver->insert($tablename, array('key' => array(self::KEY, new types\StringProperty()), 'value'=>$value[1]));
			$res = $this->driver->directaccess("SELECT key, value FROM $tablename LIMIT 1");
			$res = $res[0];
			$row = $res->fetchArray(SQLITE3_ASSOC);
			$this->assertEquals($value[1][0], $row['value']);
			$this->assertEquals(self::KEY, $row['key']);
		}
		
		$this->insertSomeDataz();
		
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('wtf', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals(self::KEY, $row['key']);
	}
	
	public function testUpdate(){

		$this->insertSomeDataz();
		
		$changes = $this->driver->update('multitable', array('lolvalue' => array('lol', new types\StringProperty())), self::KEY);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('lol', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals(self::KEY, $row['key']);
	}
	
	public function testDelete(){
		$this->insertSomeDataz();
		
		$changes = $this->driver->delete('multitable', self::KEY);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals(false, $row);
	}
	
	public function testReplace(){
		$this->driver->replace('multitable', array( 'lolvalue'=>array('wtf', new types\StringProperty()),
													'mewvalue'=>array(24, new types\IntegerProperty()), 
													'moovalue'=>array(3.14, new types\FloatProperty())
													), self::KEY);
		$changes = $this->driver->replace('multitable', array('lolvalue' => array('lol', new types\StringProperty())), self::KEY);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('lol', $row['lolvalue']);
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals(false, $row);
	}
	
	public function testFilter(){
		$this->insertSomeDataz();
		$columns = array('mewvalue', 'moovalue', 'lolvalue', 'key');
		$args = array('mewvalue' => array(24, new types\IntegerProperty()));
		$results = $this->driver->filter('multitable', $columns, $args);
		$i = 0;
		foreach ($results as $row){
			$this->assertEquals(24, $row['mewvalue']);
			$this->assertEquals(3.14, $row['moovalue']);
			$this->assertEquals('wtf', $row['lolvalue']);
			$this->assertEquals(self::KEY, $row['key']);
			$i++;
		}
		$this->assertEquals(1, $i);
	}
}


?>
