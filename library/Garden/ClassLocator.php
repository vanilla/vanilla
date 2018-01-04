<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Garden;


/**
 * An abstraction around {@link class_exists()} and {@link method_exists()}.
 */
class ClassLocator {
    /**
     * Find a class with a given name.
     *
     * The default implementation returns the given name if it exists, but other implementations may do something different.
     *
     * @param string $name The name to lookup.
     * @return string Returns the name of the class found or **null** if the class isn't found.
     */
    public function findClass($name) {
        if (class_exists($name)) {
            return $name;
        } else {
            return null;
        }
    }

    /**
     * Find a method on an object.
     *
     * @param object $object An object to search.
     * @param string $method The name of the method to look up.
     * @return callable|null Returns a callback to the method or null if it does not exist.
     */
    public function findMethod($object, $method) {
        if (method_exists($object, $method)) {
            return [$object, $method];
        } else {
            return null;
        }
    }
}
