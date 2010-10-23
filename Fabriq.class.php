<?php
/**
 * @files This file contains functions used throughout Fabriq applications - DO NOT EDIT
 * @author Will Steinmetz
 * 
 * Copyright (c)2010, Ralivue.com
 * Licensed under the BSD license.
 * http://fabriqframework.com/license
 */

function __autoload($class) {
	// autoload appropriate database class
	if (strpos($class, 'Database') !== FALSE) {
		require_once("core/{$class}.class.php");
	// autoload model
	} else {
		require_once("app/models/{$class}.model.php");
	}
}

abstract class Fabriq {
	private static $cssqueue = array();
	private static $jsqueue = array();
	private static $title;
	private static $render = 'layout';
	private static $layout = 'application';
	
	/**
	 * Adds a stylesheet to the CSS queue for stylesheet includes
	 * @param string $stylesheet
	 * @param string $media
	 * @param string $path
	 */
	public static function add_css($stylesheet, $media = 'screen', $path = 'public/stylesheets/', $ext = '.css') {
		self::$cssqueue[] = array('css' => $stylesheet, 'media' => $media, 'path' => $path, 'ext' => $ext);
	}
	
	/**
	 * Public getter for $cssqueue
	 * @return array
	 */
	public static function cssqueue() {
		return self::$cssqueue;
	}
	
	/**
	 * Adds a JavaScript file to the JavaScript queue for JavaScript includes
	 * @param string $javascript
	 * @param string $path
	 * @param string $ext
	 */
	public static function add_js($javascript, $path = 'public/javascripts/', $ext = '.js') {
		self::$jsqueue[] = array('js' => $javascript, 'path' => $path, 'ext' => $ext);
	}
	
	/**
	 * Public getter for $jsqueue
	 * @return array
	 */
	public static function jsqueue() {
		return self::$jsqueue;
	}
	
	/**
	 * Creates a link to another page in the application
	 * @param string $linktext
	 * @param string $controller
	 * @param string $action
	 * @param array $queries
	 * @param boolean $blank
	 */
	public static function link_to($linktext, $controller, $action = NULL, $queries = false, $blank = false, $title = NULL) {
		global $_FAPP;
		
		echo "<a href=\"";
		if (!$_FAPP['cleanurls']) {
			echo "index.php?q=";
		} else {
			echo $_FAPP['apppath'];
		}
		echo "{$controller}";
		if ($action != NULL) {
			echo "/{$action}";
		}
		if ($queries != false) {
			foreach($queries as $key => $val) {
				echo "/{$val}";
			}
		}
		echo "\"";
		if ($blank) {
			echo " target=\"_blank\"";
		}
		echo " title=\"";
		if ($title) {
			echo strip_tags($title);
		} else {
			echo strip_tags($linktext);
		}
		
		echo "\">{$linktext}</a>";
	}
	
	/**
	 * page title getter/setter
	 * if NULL, return the page title
	 * @param string $title
	 * @return string
	 */
	public static function title($title = NULL) {
		if ($title != NULL) {
			self::$title = strip_tags($title);
		} else {
			return self::$title;
		}
	}
	
	/**
	 * getter/setter for the $render variable
	 * if NULL, return the $render variable
	 * @param string $render
	 * @return string
	 */
	public static function render($r = NULL) {
		if ($r != NULL) {
			switch($r) {
				case 'none':
					self::$render = 'none';
					break;
				case 'layout':
					self::$render = 'layout';
					break;
				case 'view': default:
					self::$render = 'view';
					break;
			}
		} else {
			return self::$render;
		}
	}
	
	/**
	 * layout file getter/setter
	 * if NULL, return the $layout variable
	 * @param string $layout
	 * @return string
	 */
	public static function layout($l = NULL) {
		if ($l != NULL) {
			self::$layout = $l;
		} else {
			return self::$layout;
		}
	}
	
	/**
	 * Issue a server error
	 */
	public static function fabriq_error() {
		Fabriq::render('none');
		header('Location: ' . PathMap::build_path(500));
	}
	
	/**
	 * turn on page javascript include
	 */
	public static function page_js_on() {
		Fabriq::add_js(PathMap::render_controller() . '.script', 'app/scripts/');
	}
	
	/**
	 * Determines whether or not the configuration file has been
	 * created yet
	 */
	public static function installed() {
		if (!file_exists('config/config.inc.php')) {
			header("Location: install.php");
			exit();
		}
	}
}