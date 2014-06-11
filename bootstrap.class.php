<?php
/**
 * @file bootstrap.class.php
 * @author Will Steinmetz
 * This file contains the code for bootstrapping the code
 */

namespace Fabriq\core {
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
		}
		
		/**
		 * Default autoloader function
		 * @param string $class
		 */
		public static function default_autoloader($class) {
			if (strpos($class, '_install') !== FALSE) {
				$module = str_replace('_install', '', $class);
				// see if the site has a version of this module to use instead
				// if (file_exists('sites/' . FabriqStack::site() . "/modules/{$module}/{$module}.install.php")) {
					// require_once('sites/' . FabriqStack::site() . "modules/{$module}/{$module}.install.php");
				// } else {
					require_once("modules/{$module}/{$module}.install.php");
				// }
			// initialize module core
			} else if (trim($class) == 'FabriqModules') {
				\Fabriq::init_module_core();
			// autoload model
			} else {
				// see if the site had the model
				$model = "app/models/{$class}.model.php";
				// if (file_exists('sites/' . FabriqStack::site() . "/{$model}")) {
					// require_once('sites/' . FabriqStack::site() . "/{$model}");
				// } else if (file_exists($model)) {
					require_once($model);
				// }
			}
		}
	}
}
