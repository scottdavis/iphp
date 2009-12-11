<?php
 	/**
	* This Class exposes TempFile handles so that they are mockable in test cases
	*/
	class TempFile {
		/**
		* File handle cache
		*/
		static $tempFiles = array();
		/**
		* Creates a temp file and caches its location
		* @param string $name
		* @return string - path of tempfile
		*/		
		public static function fileName($name) {
			if(!isset(static::$tempFiles[$name])) {
				static::$tempFiles[$name] = tempnam(sys_get_temp_dir(), "iphp.{$name}.");
			}
      return static::$tempFiles[$name];
    }
		/**
		* Writes supplied data to a temp file
		* @param string $name - Shortname of file uses tmpFileName 
		* @param string $data - Data to write to file
		*/
		public static function writeToFile($name, $data) {
			file_put_contents(static::fileName($name), $data);
		}
		/**
		* Reads from a temp file
		* @param string $name - Shortname of file uses tmpFileName
		* @return string
		*/
		public static function readFromFile($name) {
			return file_get_contents(static::fileName($name));
		}
		/**
		* Deletes all temp files and resets the cache
		* Use this function to clean up the file system on exit
		* @return void
		*/
		public static function clear() {
			foreach(static::$tempFiles as $file) {
				unlink($file);
			}
			static::$tempFiles = array();
		}
		/**
		* Removes temp file and creates a new one
		* @param string $name - Shortname of file uses tmpFileName
		* @return void
		*/
		public static function reset($name) {
			$path = static::fileName($name);
			unlink($path);
			unset(static::$tempFiles[$name]);
			static::fileName($name);
		}
		
	}