<?php
/**
 * The renderer handles the view rendering.
 * @author Shuhao Wu <shuhao@shuhaowu.com>
 * @copyright Copyright (c) 2011, Shuhao Wu
 * @package renderer
 */
namespace lite;

/**
 * The root directory of the framework.
 * @var string
 */
define('lite\FRAMEWORK_DIR', dirname(__FILE__));
/**
 * The $page variable in that can be used by templates. The variables under the 
 * $page variable are passed from the controller.
 * @author Shuhao Wu
 * @package renderer
 */
class PageHelper{
	private $return = false;
	private $errorcode = 0;
	private $errormessage;
	private $debugmessage;
	private $renderer;
	private $NAME = false;
	
	/**
	 * The object that contains all the custom functions and variables the 
	 * template should have, given by the developer.
	 * @var object
	 */
	public $HELPER;
	
	/**
	 * Initializes a new $page variable.
	 * This is only allowed once per execution.
	 * @param array $initialVariables A key => value array that's going to be 
	 * assigned to be a variable under $page
	 * 
	 * @param Renderer $renderer The renderer instance.
	 * @param object $helper The helper object contains custom functions and 
	 * attributes.
	 */
	public function __construct(array $initialVariables, Renderer &$renderer, 
								&$helper){
		$this->bulkAddVariables($initialVariables);
		$this->renderer = &$renderer;
		$this->HELPER = &$helper;
	}
	/**
	 * Sets the name of the page. Ensures that the name only get set once to 
	 * vent issues like rendering sidebars in a template page.
	 * @param string $name The name of the rendered page.
	 */
	public function setName($name){
		if (!$this->NAME){
			$this->NAME = $name;
		}
	}
	
	/**
	 * Gets the name of the current page.
	 * @return string The name of the current page.
	 */
	public function getName(){
		return $this->NAME;
	}
	
	/**
	 * Adds key value pairs to the object.
	 * @param array $variables The key value pairs
	 */
	public function bulkAddVariables(array $variables){
		foreach ($variables as $key => $value){
			$this->$key = $value;
		}
	}
	
	/**
	 * Sets the return value for the renderer to pick up.
	 * To get the return use getReturn.
	 * @param mixed $value
	 * @see getReturn()
	 */
	public function setReturn($value){
		$this->return = $value;
	}
	
	/**
	 * Gets the return value.
	 * @return mixed The return code set up setReturn.
	 * @see setReturn()
	 */
	public function getReturn(){
		return $this->return;
	}
	
	/**
	 * Throws an error for LiteRenderer to pickup.
	 * It's the programmer's responsibility to not echo anything out (including 
	 * any html source). It's important to note that even though an error has 
	 * been thrown, it doesn't necessarily mean that the template page stop 
	 * executing. A good way to stop the execution is to return in the template 
	 * page.
	 * @param int $errorcode The error code that will be given to the header of 
	 * the return sent by the server if the template page didn't echo anything 
	 * out.
	 * 
	 * @param string $errormessage The error message that will be displayed.
	 * @param string $debugmessage Additional debug message.
	 */
	public function throwError($errorcode, 
							   $errormessage='An unknown error has occured.', 
							   $debugmessage='No messages.'){
		$this->errorcode = $errorcode;
		$this->errormessage = $errormessage;
		$this->debugmessage = $debugmessage;
	}
	
	/**
	 * Clears all errors. Useful when need to render another part of the page 
	 * within an error page.
	 */
	public function clearError(){
		$this->errorcode = 0;
		$this->errormessage = "";
		$this->debugmessage = "";
	}
	
	/**
	 * Sets the debugmessage attribute.
	 * @param string $message
	 */
	public function setDebugMessage($message){
		$this->debugmessage = $message;
	}
	
	/**
	 * Gets the debug message.
	 * @return string
	 */
	public function getDebugMessage(){
		return $this->debugmessage;
	}
	
	/**
	 * Gets the error that has been thrown or false.
	 * @return mixed false when no errors are found. Array containing error 
	 * code on [0], error message on [1], and debug message on [2] if there is 
	 * an error.
	 */
	public function getError(){
		if (!$this->errorcode){
			return false;
		} else {
			return array($this->errorcode, $this->errormessage, 
						 $this->debugmessage);
		}
	}
	
	/**
	 * Gets the current URL.
	 * Credit goes to http://www.webcheatsheet.com/PHP/get_current_page_url.php
	 * @return string the current url.
	 */
	public function currentURL(){
		$pageURL = 'http';
		if (array_key_exists('HTTPS', $_SERVER)) {
			if ($_SERVER['HTTPS'] == 'on') {$pageURL .= 's';}
		}
		
		$pageURL .= '://';
		
		if ($_SERVER['SERVER_PORT'] != '80') {
			$pageURL .= $_SERVER['SERVER_NAME'].
						':'.
						$_SERVER['SERVER_PORT'].
						$_SERVER['REQUEST_URI'];
		} else {
			$pageURL .= $_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
		}
		return $pageURL;
	}
	
	/**
	 * Renders a page from the $page_location set in the renderer
	 * @param string $name The name of the script without the .php
	 * @param mixed $path Overrides the generated path
	 * @return mixed Returns the outcome of the page rendering.
	 */
	public function render($name, $path=false){
		if (!$path) $path = $this->renderer->getViewPath($name);
		return $this->renderer->render($name, $this, $path);
	}
	
}

/**
 * The main renderer for liteFramework. 
 * Note that views can get access to the renderer via $this. However this is 
 * highly discouraged as all operations should go through $page. This makes the
 * code more manageable by separating the code.
 * @author Shuhao Wu
 * @package renderer
 *
 */
class Renderer{
	private $DEFAULT_ERRORS_DIR;
	
	private static $instance = false;
	
	private $helper;
	private $views_location;
	private $template_location;
	private $errors_location;
	private $debug;
	public $preRenderFunctions;
	
	/**
	 * Initializes a new Renderer instance
	 * @param string $views_location The location to the views directory.
	 * @param object $helper A instance of a class that contains all the 
	 * functions and attributes to be accessible via $page->HELPER.
	 * @param string $template_location The location of the template file.
	 * @param string $errors_location The location of the errors page.
	 * @param boolean $debug Debug mode on or off.
	 */
	public function __construct($views_location, &$helper, 
								$template_location=false, 
								$errors_location=false, 
								$debug=false){
		if (self::$instance) {
			throw new AlreadyInitializedError(
					"liteFramework already has an instance.");
		}
		
		$this->DEFAULT_ERRORS_DIR = FRAMEWORK_DIR . '/errors/';
		
		$this->helper = &$helper;
		$this->views_location = $views_location;
		$this->template_location = $template_location;
		$this->errors_location = $errors_location;
		$this->debug = $debug;
		$this->preRenderFunctions = array();
		self::$instance = $this;
	}
	
	/**
	 * Gets an instance of the Renderer
	 * @return Renderer The renderer instance
	 */
	public static function &getInstance(){
		return self::$instance;
	}
	
	/**
	 * Creates a new instance of the PageHelper $page variable.
	 * @param array $variables The variables you want to include.
	 * @return PageHelper
	 */
	public function createPageVar(array $variables=array()){
		return new PageHelper($variables, $this, $this->helper);
	}
	
	
	/**
	 * Shortcut function to create the $page variable and rendering.
	 * @param string $name The name of the page.
	 * @param array $variables The array of variables given to the view
	 * @param mixed $path An override on what path to use upon rendering. 
	 * Set to false to use default.
	 * @return mixed Whatever the view decides to return.
	 */
	public function autoRender($name, array $variables=array(), $path=false){
		$page = $this->createPageVar($variables);
		return $this->render($name, $page, $path);
	}
	
	/**
	 * Renders a page.
	 * @param string $name The name of the page found in the views.
	 * @param PageHelper $page The page var.
	 * @param mixed $path An override on what path to use upon rendering. 
	 * Set to false to use default.
	 */
	public function render($name, PageHelper &$page, $path=false){
		$page->setName($name);
		$page->DEBUG = $this->debug;
		
		if (!$path){
			if ($this->template_location){
				$path = $this->template_location; 
			} else {
				$path = $this->getViewPath($name);
			}
		}
		
		return $this->cleanRender($path, $page);
	}
	
	/**
	 * Gets the path of the view given a name.
	 * @param string $name
	 * @return string The path to the view.
	 */
	public function getViewPath($name){
		$path = $this->views_location . '/' . $name . '.php';
		if (!is_file($path)) $path = FRAMEWORK_DIR . '/views/' . $name . '.php';
		return $path;
	}
	
	private function callPreRenderFunctions(PageHelper &$page){
		foreach ($this->preRenderFunctions as $func){
			$this->helper->$func($page);
		}
	}
	
	/**
	 * Renders an error page
	 * @param array $error The array that contains the error information
	 * @param PageHelper $page The page variable.
	 * @return array returns the $error that's fed in.
	 */
	public function renderError(array $error, PageHelper &$page){
		$page->clearError();
		$page->setName($error[0]);
		$path = '';

		if ($this->errors_location){
			$path = $this->errors_location . '/' . $error[0] . '.php';
		}
		if (!is_file($path)) $path = $this->DEFAULT_ERRORS_DIR . 
									 $error[0] . '.php';
		
		if (!is_file($path)) $path = $this->DEFAULT_ERRORS_DIR . 'general.php';
		$page->error = $error;
		header('HTTP/1.1 ' . $error[0] . $error[1]);
		$this->callPreRenderFunctions($page);
		require $path; 
		return $error;
	}

	/**
	 * What a terrible failure. (Renders a 500 error page)
	 * @param string $debuginfo The debug message.
	 * @param PageHelper $page The $page variable we all learned to love.
	 * @return array The error array.
	 */
	public function wtf($debuginfo, PageHelper &$page){
		return $this->renderError(
		array(500,
			'WTF. What a Terrible Failure. You should not have seen this.',
			$debug),
		$page
		);
	}
	
	/**
	 * Renders a page, giving the page only access to the $page variable 
	 * (and $path);
	 * @param string $path Path to the file to include
	 * @param PageHelper $page the page variable
	 * @param array $errorpages the error pages.
	 * @see PageHelper
	 * @return mixed Whatever the page sets the return to.
	 */
	public function cleanRender($path, PageHelper &$page){;
		if (is_file($path)){
			$page->clearError();
			$this->callPreRenderFunctions($page);
			require $path;
		} else {
			$page->throwError(404, 'Page not found.', "Path: $path");	
		}
		
		$error = $page->getError();
		
		if ($error) return $this->renderError($error, $page);
		
		return $page->getReturn();
	}	
	
}
?>
