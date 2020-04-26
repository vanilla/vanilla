<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

/**
 * Utility functions that operate on arrays or array-like objects.
 */
final class ArrayUtils {
    /**
     * Recursively walk a nested array and call a callback on it and each of its sub-arrays.
     *
     * This method is reminiscent of `array_walk_recursive()`, but it calls the callback on array elements rather than non-array elements.
     *
     * @param array|\ArrayAccess $array
     * @param callable $callback
     */
    public static function walkRecursiveArray(&$array, callable $callback): void {
        $m = function (&$array, array $path) use ($callback, &$m): void {
            if (is_iterable($array)) {
                $keys = static::keys($array);
                $callback($array, $path);

                foreach ($keys as $key) {
                    if (static::isArray($array[$key])) {
                        $currentPath = array_merge($path, [$key]);
                        $m($array[$key], $currentPath);
                    }
                }
            } else {
                $callback($array, $path);
            }
        };

        self::assertArray($array, __METHOD__.'() expects argument 1 to be an array or array-like object.');
        $m($array, []);
    }

    /**
     * Get the keys of an array or array like object.
     *
     * This method is similar to `array_keys()`, but works on objects that act like arrays.
     *
     * @param mixed $array An array or array like object.
     * @return array
     */
    public static function keys($array): array {
        if (is_array($array)) {
            return array_keys($array);
        } elseif ($array instanceof \ArrayObject) {
            return array_keys($array->getArrayCopy());
        } elseif (is_iterable($array)) {
            $r = [];
            foreach ($array as $key => $_) {
                $r[] = $key;
            }
            return $r;
        } else {
            throw new \InvalidArgumentException(__METHOD__."() expects argument 1 to be an array or array-like object.", 400);
        }
    }

    /**
     * Test whether or not an input object is an array or accessible as an array.
     *
     * @param mixed $input The input to test.
     * @return bool
     */
    public static function isArray($input): bool {
        return is_array($input) || $input instanceof \ArrayAccess;
    }

    /**
     * Ensure the input is an array or accessible as an array.
     *
     * @param mixed $input The input to test.
     * @param string $message
     */
    private static function assertArray($input, string $message): void {
        if (!self::isArray($input)) {
            throw new \InvalidArgumentException($message, 400);
        }
    }
}
