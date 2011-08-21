<?php
/**
 * ORM Library import file and defines a couple of common functions.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package \lite\orm
 */

namespace lite\orm;
use \lite;
/**
 * This is the class of errors that corresponds to an invalid key.
 * @package \lite\orm
 */
class InvalidKeyError extends \Exception {}

/**
 * This is the class of errors that corresponds to a non-existing driver.
 * @package \lite\orm
 */
class DriverNotFound extends \Exception {}

$filepath = dirname(__FILE__);
require_once($filepath . '/orm/properties.class.php');
require_once($filepath . '/orm/query.class.php');
require_once($filepath . '/orm/model.class.php');
require_once($filepath . '/orm/db.interface.php');
lite\importLibraries($filepath . '/orm/drivers', 'driver.');
unset($filepath);

?>
