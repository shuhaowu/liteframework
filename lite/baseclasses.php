<?php

/**
 * Contains the base classes for inheriting.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package baseclasses
 */

/**
 * Exception is thrown when something is trying to be initialized again.
 * @author Shuhao Wu
 * @package baseclasses
 */
class AlreadyInitializedError extends Exception {}


/**
 * Base controller class. A couple of shortcut functions.
 * @author Shuhao Wu
 * @package baseclasses
 */
class BaseController{
	/**
	 * The controller base class that provides a couple of simple functions.
	 * @var LiteRenderer
	 * @see LiteRenderer
	 */
	private $renderer;
	private $helper;
	
	/**
	 * Initializes the controller. This method may not be overwritten. Use init() instead. It will be called right after the object is initialized.
	 * @param UltiRenderer $renderer The renderer instance.
	 * @param Helper $helper The helper instance.
	 * @see init()
	 */
	final function __construct($renderer, $helper){
		$this->renderer = $renderer;
		$this->helper = $helper;
	}
	
	/**
	 * Shortcut to autorendering.
	 * @param string $name Name of the page to be rendered
	 * @param array $args Arguments
	 */
	protected function render($name, array $args=array()){
		return $this->renderer->autoRender($name, $args);
	}
	
	/**
	 * The index function. Should be overwritten in order to do something in the homepage.
	 * @param array $args The args are given by the dispatcher. /index/something/something will give the args of array('something', 'something'). However. /something/something will not.
	 */
	function index($args=array()){
		$this->render('index');
	}
	
	/**
	 * Function that's executed before the rendering processes.
	 * @param array $functions An array of function names (in string) under the Helper class. 
	 */
	protected function addFunctionsToPreRender(array $functions){
		$this->renderer->preRenderFunctions = array_unique(array_merge($this->renderer->preRenderFunctions, $functions));
	}
	
	/**
	 * Should be overwritten if the developer needs extra things to be set after __construct (which is final).
	 */
	function init(){}
	
	/**
	 * Shortcut function that buffers the output, requires a page, then return and cleans the output.
	 * @param string $path Path to the page
	 * @return string Whatever that's echoed from the $path.
	 */
	function parse($path){
		ob_start();
		require $path;
		return ob_get_clean();
	}
	
	/**
	 * Shortcut function to render an error page.
	 * @param int $errorcode The HTTP error code
	 * @param string $errormessage The error message
	 * @param string $debugmessage The debug message
	 * @param array $args Used to construct the page var. Key=>value array.
	 */
	function error($errorcode, $errormessage, $debugmessage='', array $args=array()){
		$page = $this->renderer->createPageVar($args);
		$error = array($errorcode, $errormessage, $debugmessage);
		$this->renderer->renderError($error, $page);
	}
}

/**
 * Base helper class. These are functions that are both available for the view AND the controller.
 * @author Shuhao Wu
 * @package baseclasses
 */
class BaseHelper{	
	private static $instance = false;
	/**
	 * Safe guard against initializing 2 copies. Unnecessary.
	 * Call this method if you decide to overwrite it.
	 */
	function __construct(){
		if (self::$instance){
			throw new AlreadyInitializedError("There's already an instance of the BaseHelper");
		}
		self::$instance = $this;
	}
	
	/**
	 * Gets an instance of the BaseHelper
	 */
	public static function getInstance(){
		return self::$instance;
	}
	
	/**
	 * Seperates the first part of the name from the rest.
	 * Example: /something/args/args1 would return something
	 * Note: / would return index
	 * @param string $pagename The name of the page
	 */
	function getControllerFromName($pagename){
		if ($pagename == '/') return 'index';
		$out = explode('/', trim($pagename, '/'));
		return $out[0];
	}
}

?>