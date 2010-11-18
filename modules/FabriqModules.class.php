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
		$mod = new Model(array('module', 'enabled'), 'fabmods_modules');
		$mod->module = $module;
		$mod->enabled = 0;
		return $mod->create();
	}
	
	/**
	 * Registers permissions available for setting for this module
	 * @param int $module_id
	 * @param array $perms
	 * @return array
	 */
	public static function register_perms($module_id, $perms) {
		$mod = new Model(array('module', 'enabled'), 'fabmods_modules');
		$mod->find($module_id);
		if (($mod->id == null) || ($mod->id == '')) {
			throw new Exception('Module does not exist');
		}
		$perm_ids = array();
		foreach ($perms as $perm) {
			$perm = new Model(array('permission', 'module'), 'fabmods_perms');
			$perm->permission = $perm;
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
		
		$sql = sprintf("DELETE FROM %s WHERE %s%s%s = %s", 'fabmods_perms', $db->delim, 'module', $db->delim, (($db->type == 'MySQL') ? '?' : '$1'));
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
		
		$sql = "SELECT enabled FROM fabmods_modules WHERE module = " . (($db->type == 'MySQL') ? '?' : '$1');
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
	 * FabriqModules class' $body variable. This variable's content is appended
	 * the output content of a page. For rendering a module to put in a specific place
	 * use FabriqModules::render_now();
	 * @param string $module
	 * @param string $action
	 */
	public static function render($module, $action) {
		if (!file_exists("modules/{$module}/views/{$action}.view.php")) {
			throw new Exception("View for {$module}'s {$action} action does not exist");
		}
		ob_start();
		extract(self::$module_vars[$module]);
		require_once("modules/{$module}/views/{$action}.view.php");
		self::$body .= ob_get_clean();
	}
	
	/**
	 * Renders the module action's view content and returns it to be added at
	 * a specific place
	 * @param string $module
	 * @param string $action
	 */
	public static function render_now($module, $action) {
		if (!file_exists("modules/{$module}/views/{$action}.view.php")) {
			throw new Exception("View for {$module}'s {$action} action does not exist");
		}
		ob_start();
		extract(self::$module_vars[$module]);
		require_once("modules/{$module}/views/{$action}.view.php");
		return ob_get_clean();
	}
}
	