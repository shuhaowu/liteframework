<?php
use lite\orm;
use lite\orm\types;
use lite\orm\Model;
$liteDBDriver = new \lite\orm\drivers\SQLite(':memory:', null, null, null);
$liteDBDriver->connect();
$liteDBDriver->directaccess("CREATE TABLE testmodel (key VARCHAR(64) PRIMARY KEY, textprop TEXT, intprop INTEGER, floatprop NUMBER, strlistprop TEXT, boolprop INTEGER)");
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
?>
