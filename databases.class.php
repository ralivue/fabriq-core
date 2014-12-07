<?php
/**
 * @file database.class.php
 * @author Will Steinmetz
 * Database management class
 */

namespace Fabriq\Core {
  class Databases {
    private static $config;
    private static $use = 'default';

    /**
     * Initialize the config for databases
     * @param array $db_config
     */
    public static function init_config($db_config) {
      self::$config = $db_config;
      // @TODO remove after code is fully refactored for new APIs
      $GLOBALS['_FDB'] = self::$config;
    }

    /**
     * return the database settings for the given database
     * @param string $database
     */
    public static function db_config($database) {
      if (array_key_exists($database, self::$config)) {
        return self::$config[$database];
      }
      return false;
    }

    /**
     * Getter/setter for which database is in use/to use
     * @param string $database
     */
    public static function use_db($database = null) {
      if (is_null($database)) {
        return self::$use;
      } else {
        if (array_key_exists($database, self::$config)) {
          self::$use = $database;
        } else {
          throw new Exception("Fabriq\\Core\\Database error 1 - database '{$database}' is not configured in the config file");
        }
      }
    }
  }
}
