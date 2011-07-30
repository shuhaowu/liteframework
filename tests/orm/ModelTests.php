<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');

use lite\orm;
use lite\orm\types;
use lite\orm\Model;
$liteDBDriver = new \lite\orm\drivers\SQLite(':memory:', null, null, null);
$liteDBDriver->connect();
$liteDBDriver->directaccess("CREATE TABLE testmodel (key VARCHAR(64), textprop TEXT, intprop INTEGER, floatprop NUMBER, strlistprop TEXT, boolprop INTEGER)");
Model::addDriver($liteDBDriver);
Model::setDefaultDriver($liteDBDriver);

class TheTestModel extends Model{
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
		$liteDBDriver = Model::getDefaultDriver();
		$this->model->textprop = 'hello';
		$this->model->intprop = 32;
		$this->model->floatprop = 20.2;
		$this->model->boolprop = false;
		$this->model->strlistprop = array('test', 'test2', 'test3');
		$this->model->put();
		$rows = $liteDBDriver->get('testmodel', array('textprop', 'intprop', 'floatprop', 'boolprop', 'strlistprop'), $this->model->getKey());
		$i = 0;
		foreach ($rows as $row){
			$this->assertEquals('hello', $row['textprop']);
			$this->assertEquals(32, $row['intprop']);
			$this->assertEquals(20.2, $row['floatprop']);
			$this->assertEquals(0, $row['boolprop']);
			$this->assertEquals('test;test2;test3', $row['strlistprop']);
			$i++;
		}
		$this->assertEquals(1, $i);
		return $this->model;
	}
	/**
	 * @depends testPut
	 */
	public function testUpdateAndGet($oldmodel){
		$model = TheTestModel::get($oldmodel->getKey());
		$this->assertEquals('hello', $model->textprop);
		$this->assertEquals(32, $model->intprop);
		$this->assertEquals(20.2, $model->floatprop);
		$this->assertEquals(false, $model->boolprop);
		$this->assertEquals(array('test', 'test2', 'test3'), $model->strlistprop);
	}
	
	public function tearDown(){
		$this->model = null;
	}
}

?>
