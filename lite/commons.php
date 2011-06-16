<?php 

/**
 * Simulated namespace with class. This is just a collection of miscellaneous functions.
 * @author Shuhao Wu
 * @package commons
 */
class LiteCommons{
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
	
	public static function arrayGet($array, $key, $default=false){
		return (array_key_exists($key, $array) ? $array[$key] : $default);
	}
	
}

?>