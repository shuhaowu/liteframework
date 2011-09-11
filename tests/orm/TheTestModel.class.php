<?php
use lite\orm;
use lite\orm\types;
use lite\orm\Model;
use lite\orm\ModelManager;
$liteDBDriver = new \lite\orm\drivers\SQLite(':memory:', null, null, null);
$liteDBDriver->connect();
$liteDBDriver->directaccess("CREATE TABLE testmodel (key VARCHAR(32) PRIMARY KEY, textprop TEXT, intprop INTEGER, floatprop NUMBER, strlistprop TEXT, boolprop INTEGER)");
ModelManager::addDriver($liteDBDriver);
ModelManager::setDefaultDriver($liteDBDriver);

class TheTestModel extends Model{
	public static $tablename = 'testmodel';
	public static function setup(){
		$manager = self::getManager();

		$manager->addProperty('textprop', new types\StringProperty());
		$manager->addProperty('intprop', new types\IntegerProperty());
		$manager->addProperty('floatprop', new types\FloatProperty());
		$manager->addProperty('strlistprop', new types\StringListProperty());
		$manager->addProperty('boolprop', new types\BooleanProperty());
		$manager->lock();
	}

	public function init(){
	}
}
TheTestModel::setup();
?>
