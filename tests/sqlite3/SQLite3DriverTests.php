<?php
require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm\Model;
use lite\orm\drivers;
use lite\orm\drivers\DatabaseLulz;
use lite\orm\types\IntegerProperty;
use lite\orm\types\StringProperty;
use lite\orm\types\FloatProperty;


class SQLite3DriverTests extends PHPUnit_Framework_TestCase{			
	private static $key;
	
	public static function setUpBeforeClass(){
		self::$key = Model::generateKey();
	}
	
	public function setUp(){
		
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->connect();
		
		$this->driver->directaccess("CREATE TABLE IF NOT EXISTS multitable (key VARCHAR(64) PRIMARY KEY, lolvalue TEXT, mewvalue INTEGER, moovalue FLOAT)");
	}
	
	public function tearDown(){
		$this->driver->disconnect();
		$this->driver = null;
	}

	private function insertSomeDataz(){
		$params = array(new DatabaseLulz('lolvalue', 'wtf', new StringProperty()),
						new DatabaseLulz('mewvalue', 24, new IntegerProperty()),
						new DatabaseLulz('moovalue', 3.14, new FloatProperty()),
						new DatabaseLulz('key', self::$key, new StringProperty()));
		$this->driver->insert('multitable', $params);
	}
	
	public function testInsert(){		
		$this->insertSomeDataz();
		
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('wtf', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals(self::$key, $row['key']);
	}
	
	public function testUpdate(){

		$this->insertSomeDataz();

		$params = array(new DatabaseLulz('lolvalue', 'lol', new StringProperty()),
						new DatabaseLulz('mewvalue', 100, new IntegerProperty()),
						new DatabaseLulz('moovalue', 6.28, new FloatProperty()));
		
		$changes = $this->driver->update('multitable', $params, self::$key);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('lol', $row['lolvalue']);
		$this->assertEquals(100, $row['mewvalue']);
		$this->assertEquals(6.28, $row['moovalue']);
		$this->assertEquals(self::$key, $row['key']);
	}
	
	public function testDelete(){
		$this->insertSomeDataz();
		
		$changes = $this->driver->delete('multitable', self::$key);
		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals(false, $row);
	}
	
	public function testReplace(){
		$params = array(new DatabaseLulz('lolvalue', 'wtf', new StringProperty()),
						new DatabaseLulz('mewvalue', 24, new IntegerProperty()),
						new DatabaseLulz('moovalue', 3.14, new FloatProperty()));
		$changes = $this->driver->replace('multitable', $params, self::$key);
		$this->assertEquals(1, $changes);

		## Test if insertion worked ##
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 1");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals('wtf', $row['lolvalue']);
		$this->assertEquals(24, $row['mewvalue']);
		$this->assertEquals(3.14, $row['moovalue']);
		$this->assertEquals(self::$key, $row['key']); # Important

		## Replacement ##
		$changes = $this->driver->replace('multitable', array(new DatabaseLulz('lolvalue', 'lol', new StringProperty())), self::$key);

		$this->assertEquals(1, $changes);
		$res = $this->driver->directaccess("SELECT * FROM multitable LIMIT 10");
		$res = $res[0];
		$row = $res->fetchArray(SQLITE3_ASSOC);

		## Test if replacement worked ##
		$this->assertEquals('lol', $row['lolvalue']);

		## Test if it didn't insert another row ##
		$row = $res->fetchArray(SQLITE3_ASSOC);
		$this->assertEquals(false, $row);
	}
	
	public function testCount(){
		$count = $this->driver->count('multitable', array(new DatabaseLulz('key', self::$key, new StringProperty())));
		$this->assertEquals(0, $count);
		
		$this->insertSomeDataz();
		
		$count = $this->driver->count('multitable', array(new DatabaseLulz('key', self::$key, new StringProperty())));
		$this->assertEquals(1, $count);
	}
	
	public function testGet(){
		$this->insertSomeDataz();
		$columns = array('mewvalue', 'lolvalue', 'key');
		$rows = $this->driver->get('multitable', $columns, self::$key);
		$i = 0;
		foreach ($rows as $row){
			$this->assertEquals(24, $row['mewvalue']);
			$this->assertEquals(false, array_key_exists('moovalue', $row));
			$this->assertEquals('wtf', $row['lolvalue']);
			$this->assertEquals(self::$key, $row['key']);
			$i++;
		}
		$this->assertEquals(1, $rows->length());
		$this->assertEquals(1, $i);
	}
}


?>
