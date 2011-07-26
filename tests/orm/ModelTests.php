<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm;
use lite\orm\types;
$liteDBDriver = new \lite\orm\drivers\SQLite(':memory:', null, null, null);
$liteDBDriver->connect();
$liteDBDriver->directaccess("CREATE TABLE testmodel (key VARCHAR(64), textprop TEXT, intprop INTEGER, floatprop NUMBER, strlistprop TEXT, boolprop INTEGER)");

class TheTestModel extends orm\Model{
	public static $tablename = 'testmodel';
	public static function setup(){
		self::addProperty('textprop', new types\StringProperty());
		self::addProperty('intprop', new types\IntegerProperty());
		self::addProperty('floatprop', new types\FloatProperty());
		self::addProperty('strlistprop', new types\StringListProperty());
		self::addProperty('boolprop', new types\BooleanProperty());
		self::lock();
	}

	public function init(){
	}
}
TheTestModel::setup();


class ModelTests extends PHPUnit_Framework_TestCase{
	public function setUp(){
		$this->model = new TheTestModel();
		
	}
	

	public function testPut(){
		$this->model->textprop = 'hello';
		$this->model->intprop = 32;
		$this->model->floatprop = 20.2;
		$this->model->boolprop = false;
		$this->model->strlistprop = array('test', 'test2', 'test3');
		$this->model->put();
	}
	
	public function tearDown(){
		
	}
}

?>
