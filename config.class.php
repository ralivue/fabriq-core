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
      return file_exists('config/config.inc.php');
    }

    /**
     * Load the config file if the app is installed
     */
    public static function load_config() {
      if (Config::installed()) {
        require_once('config/config.inc.php');
        self::$config = $_FAPP;
        Databases::init_config($_FDB);
        // @TODO remove after code is fully refactored for new APIs
//        $GLOBALS['_FAPP'] = self::$config;
      } else {
        self::initialize_install();
      }
    }

    /**
     * Set up a basic config setup if the framework isn't installed
     */
    private static function initialize_install() {
      self::$config = array();
      self::$config['templates']['default'] = 'fabriqinstall';
      $appPath = '/';
      $aPath = substr($_SERVER['REQUEST_URI'], 1);
      $aPath = str_replace('index.php?q=', '', $aPath);
      $aPath = explode('/', $aPath);
      $i = 0;
      while (($aPath[$i] != 'fabriqinstall') && ($i < count($aPath))) {
        $appPath .= $aPath[$i] . '/';
        $i++;
      }
      self::$config['url'] = "http://{$_SERVER['HTTP_HOST']}";
      self::$config['apppath'] = str_replace('//', '/', $appPath);
    }

    /**
     * Set a config variable
     * @param string $key
     * @param mixed $val
     */
    public static function set($key, $val) {
      self::$config[$key] = $val;
    }

    /**
     * Get a config variable
     * @param string $key
     * @return mixed
     */
    public static function get($key) {
      if (array_key_exists($key, self::$config)) {
        return self::$config[$key];
      }
      return null;
    }
  }
}
