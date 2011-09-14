<?php
require_once('../lite/commons.php');
require_once('../lite/libraries/lib_orm.php');
require_once('orm/TheTestModel.class.php');

use lite\orm\types;
use lite\orm\Model;
use lite\orm\ModelManager;

$liteDBDriver = ModelManager::getDefaultDriver();
$liteDBDriver->directaccess("CREATE TABLE moo (key VARCHAR(32), reference TEXT, content INTEGER)");
$liteDBDriver->directaccess("CREATE TABLE mew (key VARCHAR(32), reference TEXT, content INTEGER)");

class MooExclaimationMark extends Model{
	public static $tablename = 'moo';
	public static function setup(){
		$manager = self::getManager();
		$manager->addProperty('reference', new types\ReferenceProperty(array('reference_class' => 'MewExclaimationMark')));
		$manager->addProperty('content', new types\IntegerProperty());
		$manager->lock();
	}
}

class MewExclaimationMark extends Model{
	public static $tablename = 'mew';
	public static function setup(){
		$manager = self::getManager();
		$manager->addProperty('reference', new types\ReferenceProperty(array('reference_class' => 'MooExclaimationMark')));
		$manager->addProperty('content', new types\IntegerProperty());
		$manager->lock();
	}
}

MooExclaimationMark::setup();
MewExclaimationMark::setup();


class ReferencesTests extends PHPUnit_Framework_TestCase{
	public function testReferences(){
		$mew = new MewExclaimationMark();
		$moo = new MooExclaimationMark();
		$mew->reference = $moo;
		$mew->content = 1;
		$moo->reference = $mew;
		$moo->content = 2;

		$mew->put();
		$moo->put();

		$liteDBDriver = ModelManager::getDefaultDriver();
		$rows = $liteDBDriver->directaccess("SELECT * FROM moo");

		$this->assertEquals(true, $mew->is_saved());
		$this->assertEquals(true, $moo->is_saved());

		$mewkey = $mew->getKey();
		$mookey = $moo->getKey();

		$mooManager = MooExclaimationMark::getManager();
		$mewManager = MewExclaimationMark::getManager();

		$mooreturned = $mooManager->get($mookey);
		$mewreturned = $mewManager->get($mewkey);

		$this->assertEquals($mew->content, $mewreturned->content);
		$this->assertEquals($moo->content, $mooreturned->content);

		$this->assertEquals($mookey, $mewreturned->reference->getKey());
		$this->assertEquals($mewkey, $mooreturned->reference->getKey());

		$this->assertEquals($moo->content, $mewreturned->reference->content);
		$this->assertEquals($mew->content, $mooreturned->reference->content);
	}
}

?>
