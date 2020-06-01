<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility;

use ArrayAccess;

/**
 * Utility functions that operate on arrays or array-like objects.
 */
final class ArrayUtils {

    private const PATH_SEPARATOR = ".";

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

    /**
     * Escape reserved characters in an associative array key.
     *
     * @param string $key
     * @return string
     */
    public static function escapeKey(string $key): string {
        $result = str_replace(
            self::PATH_SEPARATOR,
            "\\".self::PATH_SEPARATOR,
            $key
        );

        return $result;
    }

    /**
     * Break a path up into its individual associative keys.
     *
     * @param string $path
     * @return array
     */
    private static function explodePath(string $path): array {
        $explodePattern = "#(?<!\\\\)\\".self::PATH_SEPARATOR."#";
        $result = preg_split($explodePattern, $path);
        array_walk($result, function (&$value) {
            $value = self::unescapeKey($value);
        });
        return $result;
    }

    /**
     * Lookup a value in an associative array by its full key path.
     *
     * @param string $path
     * @param array|ArrayAccess $array
     * @param null $default
     * @return mixed
     */
    public static function getByPath(string $path, $array, $default = null) {
        self::assertArray($array, __METHOD__."() expects argument 2 to be an array or array-like object.");

        $keys = self::explodePath($path);

        $search = function ($array, array $keys) use ($default, &$search) {
            self::assertArray($array, "Unexpected argument type. Expected an array or array-like object.");

            $currentKey = reset($keys);
            if (array_key_exists($currentKey, $array)) {
                $value = $array[$currentKey];
                $nextKeys = array_slice($keys, 1);
                if (count($nextKeys) > 0) {
                    return is_array($value) ? $search($value, $nextKeys) : $default;
                } else {
                    return $value;
                }
            } else {
                return $default;
            }
        };
        $result = $search($array, $keys);

        return $result;
    }

    /**
     * Test whether or not an input object is an array or accessible as an array.
     *
     * @param mixed $input The input to test.
     * @return bool
     */
    public static function isArray($input): bool {
        return is_array($input) || $input instanceof ArrayAccess;
    }

    /**
     * Does an array contain string keys?
     *
     * @param array|ArrayAccess $array
     * @return bool
     */
    public static function isAssociative($array): bool {
        self::assertArray($array, __METHOD__ . "() expects argument 1 to be an array or array-like object.");
        $result = false;

        if (is_array($array)) {
            reset($array);
            while (($key = key($array)) !== null) {
                if (is_string($key)) {
                    $result = true;
                    break;
                }
                next($array);
            }
        } elseif ($array instanceof ArrayAccess) {
            $result = true;
        }

        return $result;
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
            throw new \InvalidArgumentException(
                __METHOD__."() expects argument 1 to be an array or array-like object.",
                400
            );
        }
    }

    /**
     * Set a value in an associative array by its full key path, creating new segments as necessary.
     *
     * @param mixed $value
     * @param string $path
     * @param array|ArrayAccess $array
     * @return mixed
     */
    public static function setByPath($value, string $path, $array) {
        self::assertArray($array, __METHOD__ . "() expects argument 2 to be an array or array-like object.");

        $keys = self::explodePath($path);
        $search = function ($array, array $keys) use ($value, &$search) {
            $currentKey = reset($keys);
            if (array_key_exists($currentKey, $array) && !self::isArray($array[$currentKey])) {
                throw new \InvalidArgumentException(
                    "Unexpected type in path. Expected an array or array-like object."
                );
            }

            if (!array_key_exists($currentKey, $array)) {
                $array[$currentKey] = [];
            }

            $nextKeys = array_slice($keys, 1);
            if (count($nextKeys) > 0) {
                $array[$currentKey] = $search($array[$currentKey], $nextKeys);
            } else {
                $array[$currentKey] = $value;
            }

            return $array;
        };
        $array = $search($array, $keys);

        return $array;
    }

    /**
     * Unescape reserved characters in an escaped associative array key.
     *
     * @param string $key
     * @return string
     */
    private static function unescapeKey(string $key): string {
        $result = str_replace(
            "\\".self::PATH_SEPARATOR,
            self::PATH_SEPARATOR,
            $key
        );

        return $result;
    }

    /**
     * Recursively walk a nested array and call a callback on it and each of its sub-arrays.
     *
     * This method is reminiscent of `array_walk_recursive()`, but it calls the callback on array elements rather than non-array elements.
     *
     * @param array|ArrayAccess $array
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
}
