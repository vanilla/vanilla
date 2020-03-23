<?php
/**
 * Dummy class
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Core
 * @since 2.0
 */

/**
 * A dummy class that returns itself on all method and property calls.
 *
 * This class is useful for partial deliveries where parts of the page are not necessary,
 * but you don't want to have to check for them on every use.
 * @deprecated
 */
class Gdn_Dummy {

    public function __call($name, $arguments) {
        return $this;
    }

    public static function __callStatic($name, $arguments) {
        return self::getInstance();
    }

    public function __get($name) {
        return $this;
    }

    public function __set($name, $value) {
        return $this;
    }

    /**
     * Holds a static instance of this class.
     *
     * @var Dummy
     */
    private static $_Instance;

    /**
     * Return the singleton instance of this object.
     *
     * @static
     * @return Dummy The singleton instance of this class.
     */
    public static function getInstance() {
        if (!isset(self::$_Instance)) {
            self::$_Instance = new Gdn_Dummy();
        }
        return self::$_Instance;
    }
}
