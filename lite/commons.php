<?php 

/**
 * Common functions
 * @author Shuhao Wu
 * @package commons
 */ 

/**
 * Simulated namespace with class. This is just a collection of miscellaneous functions.
 * @author Shuhao Wu
 * @package commons
 */
class LiteCommons{
	/**
	 * Imports a set of libraries. Used by the internal framework. This only
	 * imports classes and functions. All variables will be disregarded. Imports
	 * only files that starts with lib_ for its filename.
	 * @param string $directory The directory of PHP files.
	 */
	public static function importLibraries($directory){
		if ($directory){
			if ($handle = opendir($directory)){
				while (($file = readdir($handle)) !== false){
					$ext = explode('.', $file);
					if (end($ext) == 'php' && strpos($file, 'lib_') === 0){
						include_once ($directory . '/' . $file);
					}
				}
				closedir($handle);
			}
		}
	}
	/**
	 * Gets a value from an array given a key without raising an E_NOTICE
	 * @param array $array The search array.
	 * @param mixed $key The key to look for.
	 * @param mixed $default the value to return if the key is not found.
	 */
	public static function arrayGet($array, $key, $default=false){
		return (array_key_exists($key, $array) ? $array[$key] : $default);
	}
	
}

?>
