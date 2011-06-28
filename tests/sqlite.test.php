<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm\drivers;

class SQLite3DriverTest extends PHPUnit_Framework_TestCase{
	protected static $tablevalues = array('textvalue' => array('TEXT', 'Hello'),
										  'intvalue' => array('INTEGER', 24),
										  'floatvalue' => array('FLOAT', 3.14),
										  'blobvalue' => array('BLOB', b'lol'),
										  'stringarrayvalue' => array('TEXT', 'cool;stuff'),
										  'datetimevalue' => array('INTEGER', 1300309200));
									
	public function setUp(){
		$this->driver = new drivers\SQLite(':memory:', null, null, null, '');
		$this->driver->connect();
		foreach (static::$tablevalues as $tablename => $type){
			$this->driver->directaccess("CREATE TABLE IF NOT EXISTS $tablename (value {$type[0]})");	
		}
	}
	
	public function tearDown(){
		$this->driver->disconnect();
		$this->driver = null;
	}
	
	public function testInsert(){
		foreach (static::$tablevalues as $tablename => $value){
			$this->driver->insert($tablename, array('value'=>$value[1]));
		}
		
	}
}

?>
