<?php
/**
 * @file Module managing functionality file - DO NOT EDIT
 * @author Will Steinmetz
 * 
 * Copyright (c)2010, Ralivue.com
 * Licensed under the BSD license.
 * http://fabriqframework.com/license
 */
abstract class FabriqModules {
	private static $modules = array();
	private static $body = '';
	private static $module_vars = array();
	private static $render_positions = array();
	private static $cssqueue = array();
	private static $jsqueue = array();
	private static $hasPermission = true;
	private static $stoppedMappedRender = false;
	private static $eventHandlers = array();
	
	/**
	 * Calls the install function to install a module for use in the
	 * Fabriq app
	 * @param string $module
	 * @return mixed
	 */
	public static function install($module) {
		// find the installer file
		$install = "modules/{$module}/{$module}.install.php";
		if (!file_exists($install)) {
			throw new Exception("Module {$module} install file could not be found");
		}
		require_once($install);
		eval('$installer = new ' . $module . '_install();');
		return $installer->install();
	}
	
	/**
	 * Registers a module with the modules database table
	 * @param string $module
	 */
	public static function register_module($module) {
		$mod = new Modules();
		$mod->getModuleByName($module);
		if ($mod->count() == 0) {
			$file = "modules/{$module}/{$module}.info.json";
			if (!file_exists($file)) {
				throw new Exception("Module {$module}'s information file does not exist");
			}
			ob_start();
			readfile($file);
			$info = ob_get_clean();
			$info = json_decode($info, true);
			$mod->module = $module;
			$mod->enabled = 0;
			$mod->installed = 0;
			$mod->hasconfigs = 0;
			$mod->description = $info['description'];
			$mod->versioninstalled = $info['version'];
			if (isset($info['dependsOn'])) {
				$mod->dependson = implode(',', $info['dependsOn']);
			}
			$mod->id = $mod->create();
			
			// register configs if available
			if (isset($info['configs'])) {
				foreach ($info['configs'] as $con) {
					$config = new ModuleConfigs();
					$config->module = $mod->id;
					$config->var = $con;
					if (isset($info['configDefaults']) && array_key_exists($con, $info['configDefaults'])) {
						$config->val = $info['configDefaults'][$con];
					} else {
						$config->val = '';
					}
					$config->create();
				}
				$mod->hasconfigs = 1;
				$mod->update();
			}
		}
		
		return $mod->id;
	}
	
	/**
	 * Registers permissions available for setting for this module
	 * @param int $module_id
	 * @param array $perms
	 * @return array
	 */
	public static function register_perms($module_id, $perms) {
		$mod = new Modules($module_id);
		if (($mod->id == null) || ($mod->id == '')) {
			throw new Exception('Module does not exist');
		}
		$perm_ids = array();
		foreach ($perms as $p) {
			$perm = new Perms();
			$perm->permission = $p;
			$perm->module = $module_id;
			$perm_ids[] = $perm->create();
		}
		
		return $perm_ids;
	}
	
	/**
	 * Calls the uninstall function to uninstall a module from a Fabriq app
	 * @param string $module
	 * @return mixed
	 */
	public static function uninstall($module) {
		// find the installer file
		$uninstall = "modules/{$module}/{$module}.install.php";
		if (!file_exists($uninstall)) {
			throw new Exception("Module {$module} install file could not be found");
		}
		require_once($uninstall);
		eval('$installer = new ' . $module . '_install();');
		return $installer->uninstall();
	}
	
	/**
	 * Remove permissions for the given module
	 * @param int $module_id
	 */
	public static function remove_perms($module_id) {
		global $db;
		
		$sql = "DELETE FROM fabmods_perms WHERE `module` = ?";
		$db->prepare_cud($sql, array($module_id));
	}
	
	/**
	 * Loads a module's code
	 * @param $module
	 */
	public static function load($module) {
		// check to see if module is already loaded
		if (array_key_exists($module, self::$modules)) {
			return;
		}
		// try to load the module file
		$modfile = "modules/{$module}/{$module}.module.php";
		if (!file_exists($modfile)) {
			throw new Exception("Module {$module} could not be loaded");
		}
		require_once($modfile);
		eval('$mod = new ' . $module . '_module();');
		self::$modules[$module] = $mod;
		self::$module_vars[$module] = array();
	}
	
	/**
	 * Returns a reference to the specified module for easier use
	 * @param string $module
	 * @return object
	 */
	public static function &module($module) {
		if (!array_key_exists($module, self::$modules)) {
			FabriqModules::load($module);
		}
		
		return self::$modules[$module];
	}
	
	/**
	 * Returns whether or the module is enabled
	 * @param string $module
	 * @return bool
	 */
	public static function enabled($module) {
		global $db;
		
		$sql = "SELECT enabled FROM fabmods_modules WHERE module = ?";
		$data = $db->prepare_select($sql, array('enabled'), array($module));
		if (count($data) == 0) {
			return FALSE;
		}
		return ($data[0]['enabled'] == 1) ? TRUE : FALSE;
	}
	
	/**
	 * Adds a module variable
	 * @param string $module
	 * @param string $name
	 * @param mixed $var
	 */
	public static function set_var($module, $name, $var) {
		self::$module_vars[$module][$name] = $var;
	}
	
	/**
	 * Adds a set of module variables at once
	 * @param string $module
	 * @param array $vars
	 */
	public static function set_vars($module, $vars) {
		if (count($vars) == 0) {
			return;
		}
		foreach ($vars as $key => $val) {
			self::$module_vars[$module][$key] = $val;
		}
	}
	
	/**
	 * Returns a module variable
	 * @param string $module
	 * @param string $module
	 * @return mixed
	 */
	public static function get_var($module, $var) {
		if (array_key_exists($var, self::$module_vars[$module])) {
			return self::$module_vars[$module][$var];
		}
		return false;
	}
	
	/**
	 * Returns the module variables for a module
	 * @param string $module
	 * @return array
	 */
	public static function get_vars($module) {
		if (array_key_exists($module, self::$module_vars)) {
			return self::$module_vars[$module];
		}
		return false;
	}
	
	/**
	 * Adds the output of this view to the body variable that is appended to the
	 * FabriqModules class' $body variable. For rendering one module at a time,
	 * use FabriqModules::render_now();
	 * @param string $module
	 * @param string $action
	 */
	public static function render($module, $action) {
		$file = "app/views/modules/{$module}/{$action}.view.php";
		if (!file_exists($file)) {
			$file = "modules/{$module}/views/{$action}.view.php";
			if (!file_exists($file)) {
				throw new Exception("View for {$module}'s {$action} action does not exist");
			}
		}
		ob_start();
		extract(self::$module_vars[$module]);
		require_once($file);
		self::$body .= ob_get_clean();
	}
	
	/**
	 * Returns the rendered module content
	 * @return string
	 */
	public static function body() {
		return self::$body;
	}
	
	/**
	 * Renders the module action's view content and returns it to be added at
	 * a specific place
	 * @param string $module
	 * @param string $action
	 */
	public static function render_now($module, $action) {
		$file = "app/views/modules/{$module}/views/{$action}.view.php";
		if (!file_exists($file)) {
			$file = "modules/{$module}/views/{$action}.view.php";
			if (!file_exists($file)) {
				throw new Exception("View for {$module}'s {$action} action does not exist");
			}
		}
		self::$render_positions[] = $module;
		ob_start();
		extract(self::$module_vars[$module]);
		require_once($file);
		return ob_get_clean();
	}
	
	/**
	 * Add a module stylesheet to the CSS queue
	 * @param string $module
	 * @param string $stylesheet
	 * @param string $media
	 * @param string $path
	 * @param string $ext;
	 */
	public static function add_css($module, $stylesheet, $media = 'screen', $path = '', $ext = '.css') {
		self::$cssqueue[] = array('css' => $stylesheet, 'media' => $media, 'path' => "modules/{$module}/stylesheets/{$path}", 'ext' => $ext);
	}
	
	/**
	 * Public getter for $cssqueue
	 * @return array
	 */
	public static function cssqueue() {
		return self::$cssqueue;
	}
	
	/**
	 * Add a module JavaScript to the JS queue
	 * @param string $module
	 * @param string $javascript
	 * @param string $path
	 * @param string $ext
	 */
	public static function add_js($module, $javascript, $path = '', $ext = '.js') {
		self::$jsqueue[] = array('js' => $javascript, 'path' => "modules/{$module}/javascripts/{$path}", 'ext' => $ext);
	}
	
	/**
	 * Public getter for $jsqueue
	 * @return array
	 */
	public static function jsqueue() {
		return self::$jsqueue;
	}
	
	/**
	 * Public getter and setter for hasPermission
	 * @param boolean $hasPerm
	 * @return boolean
	 */
	public static function has_permission($hasPerm = -1) {
		if ($hasPerm == -1) {
			return self::$hasPermission;
		} else {
			self::$hasPermission = $hasPerm;
		}
	}
	
	/**
	 * Creates a new instance of a module model
	 * @param string $module
	 * @param string $model
	 * @return object
	 */
	public static function new_model($module, $model) {
		$class = "{$module}_{$model}";
		if (!class_exists($class)) {
			require_once("modules/{$module}/models/{$model}.model.php");
		}
		eval("\$item = new {$class}();");
		return $item;
	}
	
	/**
	 * Stops the mapped to module function's view from being rendered
	 * @param bool $stop
	 * @return bool
	 */
	public static function stopMappedRender($stop = null) {
		if ($stop != null) {
			self::$stoppedMappedRender = $stop;
		} else {
			return self::$stoppedMappedRender;
		}
	}
	
	/**
	 * Registers a handler for a module event
	 * @param string $eventModule
	 * @param string $eventAction
	 * @param string $eventName
	 * @param string $handlerModule
	 * @param string $handlerAction
	 */
	public static function register_handler($eventModule, $eventAction, $eventName, $handlerModule, $handlerAction) {
		global $db;
		
		$query = "INSERT INTO `fabmods_module_events`
			(`eventModule`, `eventAction`, `eventName`, `handlerModule`, `handlerAction`)
			VALUES (?, ?, ?, ?, ?)";
		$db->prepare_cud($query, array($eventModule, $eventAction, $eventName, $handlerModule, $handlerAction));
	}
	
	/**
	 * Removes a handler for a module event
	 * @param string $eventModule
	 * @param string $eventAction
	 * @param string $eventName
	 * @param string $handlerModule
	 * @param string $handlerAction
	 */
	public static function remove_handler($eventModule, $eventAction, $eventName, $handlerModule, $handlerAction) {
		global $db;
		
		$query = "DELETE FROM `fabmods_module_events`
			WHERE `eventModule` = ? AND `eventAction` = ? AND `eventName` = ? AND `handlerModule` = ? AND `handlerAction` = ?";
		$db->prepare_cud($query, array($eventModule, $eventAction, $eventName, $handlerModule, $handlerAction));
	}
	
	/**
	 * Get all module event handlers
	 */
	public static function get_handlers() {
		global $db;
		
		$query = "SELECT *
			FROM `fabmods_module_events`
			ORDER BY eventModule, eventAction, eventName";
		$data = $db->prepare_select($query, array('id', 'eventModule', 'eventAction', 'eventName', 'handlerModule', 'handlerAction'));
		for ($i = 0; $i < count($data); $i++) {
			if (!array_key_exists("{$data[$i]['eventModule']}_{$data[$i]['eventAction']}", self::$eventHandlers)) {
				self::$eventHandlers["{$data[$i]['eventModule']}_{$data[$i]['eventAction']}"] = array();
			}
			if (!is_array(self::$eventHandlers["{$data[$i]['eventModule']}_{$data[$i]['eventAction']}"][$eventName])) {
				self::$eventHandlers["{$data[$i]['eventModule']}_{$data[$i]['eventAction']}"][$eventName] = array();
			}
			self::$eventHandlers["{$data[$i]['eventModule']}_{$data[$i]['eventAction']}"][$data[$i]['eventName']][] = array(
				'module' => $data[$i]['handlerModule'],
				'action' => $data[$i]['handleAction']
			);
		}
	}
	
	/**
	 * Triggers an event so that handlers can take action if necessary
	 * @param string $module
	 * @param string $action
	 * @param string $name
	 * @param mixed $data
	 */
	public static function trigger_event($module, $action, $name, $data = null) {
		if (array_key_exists("{$module}_{$action}", self::$eventHandlers)) {
			if (array_key_exists($name, self::$eventHandlers["{$module}_{$action}"])) {
				for ($i = 0; $i < count(self::$eventHandlers["{$module}_{$action}"][$name]); $i++) {
					FabriqModules::module(self::$eventHandlers["{$module}_{$action}"][$name][$i]['module'])->{self::$eventHandlers["{$module}_{$action}"][$name][$i]['action']}($data);
				}
			}
		}
	}
}
	