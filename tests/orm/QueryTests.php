<?php

require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');
require_once('orm/TheTestModel.class.php');



class QueryTests extends PHPUnit_Framework_TestCase{
	private static $test = array();
	private $manager;
	public function setUp(){
		$this->manager = TheTestModel::getManager();
	}

	public static function setUpBeforeClass(){
		self::$test[0] = new TheTestModel();
		self::$test[0]->textprop = 'moo!';
		self::$test[0]->intprop = 24;
		self::$test[0]->floatprop = 3.14;
		self::$test[0]->strlistprop = array('mew', 'wtf', 'lol', 'moo');
		self::$test[0]->boolprop = true;
		self::$test[0]->put();

		self::$test[1] = new TheTestModel();
		self::$test[1]->textprop = '\'tests for quotes!\'';
		self::$test[1]->intprop = 22;
		self::$test[1]->floatprop = 3.14;
		self::$test[1]->strlistprop = array('more"quotes', 'turns out you can\'t use the seperator value in the strings.');
		self::$test[1]->boolprop = false;
		self::$test[1]->put();
	}

	public function testFilter(){
		$query = $this->manager->all();
		$stuffz = $query->fetch();
		$len = count(self::$test);
		$this->assertEquals($len, $query->count());
		for ($i=0;$i<$len;$i++){
			$this->assertEquals(self::$test[$i]->getKey(), $stuffz[$i]->getKey());
			$this->assertEquals(self::$test[$i]->textprop, $stuffz[$i]->textprop);
			$this->assertEquals(self::$test[$i]->intprop, $stuffz[$i]->intprop);
			$this->assertEquals(self::$test[$i]->floatprop, $stuffz[$i]->floatprop);
			$this->assertEquals(self::$test[$i]->strlistprop, $stuffz[$i]->strlistprop);
			$this->assertEquals(self::$test[$i]->boolprop, $stuffz[$i]->boolprop);
		}

		$query->filter('intprop =', 24);
		$this->assertEquals(1, $query->count());
		$stuffz = $query->fetch();
		$this->assertEquals(self::$test[0]->getKey(), $stuffz[0]->getKey());
	}

	public function testOrder(){
		$query = $this->manager->all(true);
		$mrrow = $query->fetch();
		$len = count(self::$test);
		$this->assertEquals($len, $query->count());
		for ($i=0;$i<$len;$i++){
			$this->assertEquals(self::$test[$i]->getKey(), $mrrow[$i]);
		}

		$query->order('intprop');
		$mrrow = $query->fetch();
		$this->assertEquals(self::$test[1]->getKey(), $mrrow[0]);
		$this->assertEquals(self::$test[0]->getKey(), $mrrow[1]);
	}
}
?>
