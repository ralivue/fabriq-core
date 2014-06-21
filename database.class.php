<?php
/**
 * @file database.class.php
 * @author Will Steinmetz
 * Database management class
 */

namespace Fabriq\Core {
	class Database {
		private static $config;
		
		/**
		 * Initialize the config for databases
		 * @param array $db_config
		 */
		public static function init_config($db_config) {
			self::$config = $db_config;
			// @TODO remove after code is fully refactored for new APIs
			$GLOBALS['_FDB'] = self::$config;
		}
	}
}
