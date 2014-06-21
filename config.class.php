<?php
/**
 * @file config.class.php
 * @author Will Steinmetz
 * Config class for Fabriq
 */

namespace Fabriq\Core {
	class Config {
		private static $config;
		
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
				self::$config = $_FAPP;
				Database::init_config($_FDB);
				// @TODO remove after code is fully refactored for new APIs
				$GLOBALS['_FAPP'] = self::$config;
			}
		}
	}
}
