<?php 

// Settings

define('DEBUG', TRUE); // This shows some additional messages in error pages.
define('BASE_DIR', dirname(__FILE__));

$views_location = './tests/pages'; // Required. Location of the views
$controllers_file = './controllers.php'; // Required. The file that contains the Controllers class with a list of functions
$template_location = false; // Optional. Set to a string to the path of the template php file. All renderings will be done to that file.
$errors_location = false; // Folder containing 404.php and etc.
$lib_location = false; // Folder that contains code libraries. Everything there will be include_once'd right after the controller file is required. If there's executable code there it will be executed at that time (not recommended)
$url_map = array(); // map url to function name (string) in the controller file. Otherwise the default is used. (Do not put / at the end of the url. There is always a / at the beginning)
$use_db = false;
$dbinfo = array(
				'driver'=>'',
				'database'=>'',
				'username'=>'',
				'password'=>'',
				'host'=>'',
				'prefix'=>''
				);

require_once './lite/dispatcher.php';

?>