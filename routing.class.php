<?php
/**
 * @file routing.class.php
 * @author Will Steinmetz
 * This file handles the routing for Fabriq
 */

namespace Fabriq\Core {
  class Routing {
    private static $path;

    /**
     * Initialize the path variable
     */
    public static function init_path() {
      self::$path = explode('/', $_GET['q']);
      if (trim(self::$path[0]) == '') {
        array_shift(self::$path);
      }
      // @TODO remove after code is fully refactored for new APIs
      $GLOBALS['q'] = self::$path;
    }

    /**
     * Argument getter/setter
     * @param integer $index
     * @param mixed $val
     * @return mixed
     */
    public static function arg($index, $val = NULL) {
//      global $q;

      if (is_null($val)) {
//        if (count($q) > $index) {
        if (count(self::$path) > $index) {
//          return $q[$index];
          return self::$path[$index];
        } else {
          return FALSE;
        }
      } else {
//        $q[$index] = $val;
        self::$path[$index] = $val;
      }
    }
  }
}
