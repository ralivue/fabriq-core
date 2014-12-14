<?php
/**
 * @file controller.class.php
 * @author Will Steinmetz
 * This file contains the base controller class that is extended by
 * application.controller.php which all controllers in a Fabriq application extend
 */

namespace Fabriq\Core {
  class Controller {
    /**
     * Returns an array of available methods
     * @return array
     */
    public function methods() {
      return get_class_methods(get_class($this));
    }

    /**
     * Returns boolean on whether or not method exists in controller
     * @param string $method
     * @return boolean
     */
    public function has_method($method) {
      return method_exists($this, $method);
    }

    /**
     * Functionality will be overridden in application controller and
     * specific controllers. It is added here so that one always exists
     * on the controller, even if nothing is necessary.
     */
    public function before() {}

    /**
     * Functionality will be overridden in application controller and
     * specific controllers. It is added here so that one always exists
     * on the controller, even if nothing is necessary.
     */
    public function after() {}
  }
}
