<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');
require_once('orm/TheTestModel.class.php');
use \lite\orm\Model;
class ModelTests extends PHPUnit_Framework_TestCase{
	public function setUp(){
		
	}
	

	public function testPut(){
		$liteDBDriver = Model::getDefaultDriver();
		$model = new TheTestModel();
		$model->textprop = 'hello';
		$model->intprop = 32;
		$model->floatprop = 20.2;
		$model->boolprop = false;
		$model->strlistprop = array('test', 'test2', 'test3');
		$this->assertEquals(false, $model->is_saved());
		$model->put();
		$this->assertEquals(true, $model->is_saved());
		$this->assertEquals(0, TheTestModel::unsavedObjectsCount());
		$rows = $liteDBDriver->get('testmodel', array('textprop', 'intprop', 'floatprop', 'boolprop', 'strlistprop'), $model->getKey());
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
		return $model;
	}
	/**
	 * @depends testPut
	 */
	public function testGetModifyAndUpdate($oldmodel){
		$model = TheTestModel::get($oldmodel->getKey());
		$this->assertEquals(true, $model->is_saved());
		$this->assertEquals('hello', $model->textprop);
		$this->assertEquals(32, $model->intprop);
		$this->assertEquals(20.2, $model->floatprop);
		$this->assertEquals(false, $model->boolprop);
		$this->assertEquals(array('test', 'test2', 'test3'), $model->strlistprop);
		$model->textprop = 'lulz';
		$model->put();
		$this->assertEquals(0, TheTestModel::unsavedObjectsCount());
		$this->assertEquals($model->getKey(), $oldmodel->getKey());
		$oldmodel->update();
		$this->assertEquals('lulz', $oldmodel->textprop);
		return $model;
	}


	/**
	 * @depends testGetModifyAndUpdate
	 * @expectedException \lite\orm\NotSavedError
	 */
	public function testDelete($model){
		$key = $model->getKey();
		$model->delete();
		$i = 0;
		TheTestModel::get($key);
	}
	/**
	 * @expectedException \lite\orm\NotSavedError
	 */
	public function testNonExistingGet(){
		$model = TheTestModel::get('notavailable');
	}
	
	public function tearDown(){
		
	}
}

?>
