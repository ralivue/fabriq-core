<?php
/**
 * @file config.class.php
 * @author Will Steinmetz
 * Config class for Fabriq
 */

namespace Fabriq\Core {
	class Config {
		/**
		 * Is the system installed?
		 * @return bool
		 */
		public static function installed() {
			if (file_exists('config/config.inc.php')) {
				return true;
			}
			return false;
		}
		
		/**
		 * Load the config file if the app is installed
		 */
		public static function load_config() {
			if (Config::installed()) {
				require_once('config/config.inc.php');
				$GLOBALS['_FDB'] = $_FDB;
				$GLOBALS['_FAPP'] = $_FAPP;
			}
		}
	}
}
