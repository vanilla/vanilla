<?php
/**
 * Dummy class
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * A dummy class that returns itself on all method and property calls.
 *
 * This class is useful for partial deliveries where parts of the page are not necessary,
 * but you don't want to have to check for them on every use.
 */
class Gdn_Dummy {

    public function __call($Name, $Arguments) {
        return $this;
    }

    public function __get($Name) {
        return $this;
    }

    public function __set($Name, $Value) {
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
