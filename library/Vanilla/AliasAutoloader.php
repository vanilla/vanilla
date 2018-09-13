<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

/**
 * An autoloader for class aliases.
 *
 * We need this because declaring a class_alias autoloads the class.
 * Declaring aliases up front (like in the bootstrap) would autoload all of our classes.
 * This class provides an autoloader for usage with spl_autoload_register to autoload these aliases
 * which will then autoload their new classes if they are not loaded yet.
 */
class AliasAutoloader {
    /**
     * An array of OLD_CLASS_NAME => New classname.
     */
    protected static $aliases = [];

    /**
     * An autoload function for use with spl_autoload_register.
     *
     * @param string $aliasName the class name to try and load.
     */
    public static function autoload($aliasName) {
        if (isset(static::$aliases[$aliasName])) {
            $newName = static::$aliases[$aliasName];
            trigger_error("The className $aliasName has been renamed. Use $newName instead", E_USER_DEPRECATED);
            class_exists($newName, true);
        }
    }
}
