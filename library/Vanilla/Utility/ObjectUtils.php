<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Static methods usefull for operating on objects.
 */
final class ObjectUtils {
    /**
     * Hydrate an object from an array.
     *
     * The array is parsed where keys represent properties to be set by the values. To set a property, the following is done:
     *
     * 1. Look for a method in the form: `set$property`.
     * 2. Look for an actual property.
     *
     * @param object $object The object to hydrate.
     * @param array $data The data to hydrate with.
     * @return object Returns the same class or a new one in the case of with methods.
     */
    public static function hydrate(object $object, array $data): object {
        foreach ($data as $key => $value) {
            try {
                if (method_exists($object, $setter = "set$key")) {
                    call_user_func([$object, $setter], $value);
                } elseif (property_exists($object, $key)) {
                    $object->$key = $value;
                } else {
                    throw new \InvalidArgumentException("Object doesn't have a $key property.", 400);
                }
            } catch (\Error $ex) {
                // Private access error, change to an exception.
                throw new \InvalidArgumentException("Could not set the $key property.", 403);
            }
        }

        return $object;
    }

    /**
     * Create a copy of an object and hydrate it.
     *
     * This method is the same as `ObjectUtils::hydrate()`, but clones the object first before hydrating it.
     *
     * @param object $object The object to base the hydration off of.
     * @param array $data The data to hydrate with.
     * @return object Returns a new object, hydrated with data.
     */
    public static function with(object $object, array $data): object {
        $clone = clone $object;
        return self::hydrate($clone, $data);
    }
}
