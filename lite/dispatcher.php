<?php
// Gets the base classes
require_once './lite/baseclasses.php';
require_once './lite/commons.php';

/* Global Variable Listings
 * $requestURL
 */


// Process the url
if (array_key_exists('page', $_GET)){
	$requestURL = trim($_GET['page'], '/');
	$args = explode('/', $requestURL);
	$name = array_shift($args);
} else {
	$requestURL = '';
	$name = 'index';
	$args = array();
}

$requestURL = '/' . $requestURL;

// Get the renderer and the controller
require_once './lite/renderer.php';
require_once $controllers_file;

// Check for helper existance
if(!class_exists('Helper')){
   class Helper extends BaseHelper {}
}

// Import third party libraries
LiteCommons::importLibraries($lib_location);
LiteCommons::importLibraries(FRAMEWORK_DIR . '/libraries');

// Sets up database if $use_db is true.
if (isset($use_db) && $use_db){
	$driver = LiteCommons::arrayGet($dbinfo, 'driver');
	try{
		$driverClass = new ReflectionClass("LiteORM_{$driver}Driver");
	} catch (ReflectionException $e){
		throw new LiteORM_DriverNotFound("Driver $driver is not found! Check if class LiteORM_{$driver}Driver exists");
	}
	
	$database = LiteCommons::arrayGet($dbinfo, 'database');
	$username = LiteCommons::arrayGet($dbinfo, 'username');
	$password = LiteCommons::arrayGet($dbinfo, 'password');
	$host = LiteCommons::arrayGet($dbinfo, 'host');
	$prefix = LiteCommons::arrayGet($dbinfo, 'prefix');
	$dbdriver = $driverClass->newInstance($database, $username, $password, $host, $prefix);
	
	// Cleanup global scope. Yay 'Garbage' collection.
	unset($database);
	unset($username);
	unset($password);
	unset($host);
	unset($prefix);
	unset($driverClass);
}

// Initializes components
$helper = new Helper();
$renderer = new LiteRenderer($views_location, $helper, $template_location, $errors_location, DEBUG);
$controllers = new Controllers($renderer, $helper);
$controllers->init();

// Check for url override
foreach ($url_map as $pattern=>$controller){
	if (preg_match($pattern, $requestURL)){
		$name = $controller;
		break;
	}
}

// Fire controller
if (method_exists($controllers, $name)){
	$controllers->$name($args);
} else {
	$controllers->error(404, 'Controller Not Found', "Controller $name is not found");
}

?>