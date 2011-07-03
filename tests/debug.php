<?php 

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');


use lite\orm\drivers;
use lite\orm\types;


class LolTest{
	private $driver;
	
	public function assertEquals($expected, $actual, $message=""){
		if ($expected != $actual){
			if ($message) echo $message . "\n";
			else echo "Expected: $expected\nActual: $actual";
		}
	}
	
	public function setUp(){
		$this->driver = new drivers\SQLite(':memory:', null, null, null);
		$this->driver->connect();
	
		$this->driver->directaccess("CREATE TABLE IF NOT EXISTS multitable (key VARCHAR(64) PRIMARY KEY, lolvalue TEXT, mewvalue INTEGER, moovalue FLOAT)");
		foreach (self::$tablevalues as $tablename => $type){
			$this->driver->directaccess("CREATE TABLE IF NOT EXISTS $tablename (key VARCHAR(64) PRIMARY KEY, value {$type[0]})");
		}
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
	
	public function test(){
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
	
	public function run(){
		$this->setUp();
		$this->test();
	}
}

$t = new LolTest();
$t->run();

?>