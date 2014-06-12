<?php
/**
 * @file bootstrap.class.php
 * @author Will Steinmetz
 * This file contains the code for bootstrapping the code
 */

namespace Fabriq\Core {
	class Bootstrap {
		/**
		 * Initalize the code
		 */
		public static function init() {
			// set error displaying for testing purposes
			ini_set('display_errors', 1);
			error_reporting(E_ALL & ~E_NOTICE);
			
			// start sessions
			session_start();
			
			// register default __autoload function
			spl_autoload_register('\Fabriq\Core\Bootstrap::default_autoloader');
			
			// load the config file if the system has been installed
			Config::load_config();
		}
		
		/**
		 * Default autoloader function
		 * @param string $class
		 */
		public static function default_autoloader($class) {
			// is this a core class?
			if (strpos($class, 'Fabriq\Core') !== FALSE) {
				$class = strtolower($class);
				$class = substr($class, strpos($class, 'core') + 5);
				if (file_exists("core/{$class}.class.php")) {
					require_once("core/{$class}.class.php");
					return;
				}
			}
			// module installer [legacy]
			if (strpos($class, '_install') !== FALSE) {
				$module = str_replace('_install', '', $class);
				require_once("modules/{$module}/{$module}.install.php");
			// initialize module core
			// } else if (trim($class) == 'FabriqModules') {
				// \Fabriq::init_module_core();
			// module model [legacy]
			} else if (strpos($class, '_') !== FALSE) {
				$class = explode('_', $class);
				$module = $class[0];
				$model = $class[1];
				require_once("modules/{$module}/models/{$model}.model.php");
			// model [legacy]
			} else {
				// see if the site had the model
				$model = "app/models/{$class}.model.php";
				require_once($model);
			}
		}
	}
}
