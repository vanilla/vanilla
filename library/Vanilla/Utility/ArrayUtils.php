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
final class ArrayUtils
{
    private const PATH_SEPARATOR = ".";

    /**
     * Ensure the input is an array or accessible as an array.
     *
     * @param mixed $input The input to test.
     * @param string $message
     */
    public static function assertArray($input, string $message): void
    {
        if (!self::isArray($input)) {
            throw new \InvalidArgumentException($message, 400);
        }
    }

    /**
     * Ensure the input is iterable.
     *
     * @param mixed $input The input to test.
     * @param string $message The error message for the exception if the input is not iterable.
     */
    private static function assertIterable($input, string $message)
    {
        if (!is_iterable($input)) {
            throw new \InvalidArgumentException($message, 400);
        }
    }

    /**
     * Escape reserved characters in an associative array key.
     *
     * @param string $key
     * @return string
     */
    public static function escapeKey(string $key): string
    {
        $result = str_replace(self::PATH_SEPARATOR, "\\" . self::PATH_SEPARATOR, $key);

        return $result;
    }

    /**
     * Split a string by a string and do some trimming to clean up faulty user input.
     *
     * @param string $delimiter The boundary string.
     * @param string $string The input string.
     * @return array Returns the exploded string as an array.
     */
    public static function explodeTrim(string $delimiter, string $string): array
    {
        $arr = explode($delimiter, $string);
        $arr = array_map("trim", $arr);
        $arr = array_filter($arr);
        return $arr;
    }

    /**
     * Lookup a value in an associative array by its full key path.
     *
     * @param string $path
     * @param array|ArrayAccess $array
     * @param null $default
     * @return mixed
     */
    public static function getByPath(string $path, $array, $default = null)
    {
        self::assertArray($array, __METHOD__ . "() expects argument 2 to be an array or array-like object.");

        $keys = explode(self::PATH_SEPARATOR, $path);

        $search = function ($array, array $keys) use ($default, &$search) {
            self::assertArray($array, "Unexpected argument type. Expected an array or array-like object.");

            $currentKey = reset($keys);
            if (self::arrayKeyExists($currentKey, $array)) {
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
     * An object safe version of `array_key_exists()`
     *
     * @param string|int $key The key to lookup.
     * @param array|ArrayAccess $array The array or object to look at.
     * @return bool Returns **true** if the key exists or **false** otherwise.
     */
    public static function arrayKeyExists($key, $array)
    {
        if (is_array($array)) {
            return array_key_exists($key, $array);
        } elseif ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return false;
    }

    /**
     * Test whether or not an input object is an array or accessible as an array.
     *
     * @param mixed $input The input to test.
     * @return bool
     */
    public static function isArray($input): bool
    {
        return is_array($input) || $input instanceof ArrayAccess;
    }

    /**
     * Does an array contain string keys?
     *
     * @param array|ArrayAccess $array
     * @return bool
     */
    public static function isAssociative($array): bool
    {
        if (!self::isArray($array)) {
            return false;
        }
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
     * Check if something will serialize as a numeric array.
     *
     * - All keys are numeric.
     * - Keys are in order.
     * - There are no holes.
     *
     * @param mixed $array
     *
     * @return bool
     */
    public static function serializesAsNumericArray($array): bool
    {
        if (!is_array($array)) {
            return false;
        }

        reset($array);
        $expectedIndex = 0;
        foreach ($array as $key => $value) {
            if ($key !== $expectedIndex) {
                return false;
            }
            $expectedIndex++;
        }

        return true;
    }

    /**
     * Get the keys of an array or array like object.
     *
     * This method is similar to `array_keys()`, but works on objects that act like arrays.
     *
     * @param mixed $array An array or array like object.
     * @return array
     */
    public static function keys($array): array
    {
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
                __METHOD__ . "() expects argument 1 to be an array or array-like object.",
                400
            );
        }
    }

    /**
     * Set a value in an associative array by its full key path, creating new segments as necessary.
     *
     * @param string $path
     * @param array|ArrayAccess $array
     * @param mixed $value
     * @return mixed
     */
    public static function setByPath(string $path, &$array, $value)
    {
        self::assertArray($array, __METHOD__ . "() expects argument 2 to be an array or array-like object.");

        $keys = explode(self::PATH_SEPARATOR, $path);
        $search = function ($array, array $keys) use ($value, &$search) {
            $currentKey = reset($keys);
            $isLastKey = count($keys) === 1;
            if (self::arrayKeyExists($currentKey, $array) && !self::isArray($array[$currentKey]) && !$isLastKey) {
                throw new \InvalidArgumentException("Unexpected type in path. Expected an array or array-like object.");
            }

            if (!self::arrayKeyExists($currentKey, $array)) {
                $array[$currentKey] = [];
            }

            $nextKeys = array_slice($keys, 1);
            if (count($nextKeys) > 1) {
                $array[$currentKey] = $search($array[$currentKey], $nextKeys);
            } elseif (count($nextKeys) === 1) {
                $nextKey = reset($nextKeys);
                $array[$currentKey][$nextKey] = $value;
            } else {
                $array[$currentKey] = $value;
            }

            return $array;
        };
        $array = $search($array, $keys);

        return $array;
    }

    /**
     * Remove a value by a dot notation string path.
     *
     * @param string $path
     * @param array|ArrayAccess $array
     */
    public static function unsetByPath(string $path, &$array): void
    {
        self::assertArray($array, __METHOD__ . "() expects argument 2 to be an array or array-like object.");
        $arr = &$array;
        $pieces = explode(self::PATH_SEPARATOR, $path);
        foreach ($pieces as $i => $piece) {
            if (!self::arrayKeyExists($piece, $arr)) {
                // Nothing to unset.
                return;
            }

            $isLastKey = $i === count($pieces) - 1;

            if ($isLastKey) {
                unset($arr[$piece]);
            } elseif (!self::isArray($arr[$piece])) {
                throw new \InvalidArgumentException("Unexpected type in path. Expected an array or array-like object.");
            } else {
                $arr = &$arr[$piece];
            }
        }
    }

    /**
     * Recursively walk a nested array and call a callback on it and each of its sub-arrays.
     *
     * This method is reminiscent of `array_walk_recursive()`, but it calls the callback on array elements rather than non-array elements.
     *
     * @param array|ArrayAccess $array
     * @param callable $callback
     */
    public static function walkRecursiveArray(&$array, callable $callback): void
    {
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

        self::assertArray($array, __METHOD__ . "() expects argument 1 to be an array or array-like object.");
        $m($array, []);
    }

    /**
     * Recursively walk a nested array and filter sub-arrays based on a filter callback.
     *
     * This function is similar to `array_filter()`, but it will walk the array recursively and filter based on sub-arrays
     * and not each value.
     *
     * @param array|ArrayAccess $array The nested array to filter.
     * @param callable $filter The filter function.
     * @param bool $includeRoot Whether or not to include the root array.
     * @return array|null Returns the filtered array or null if the root array is filtered out.
     */
    public static function filterRecursiveArray($array, callable $filter, bool $includeRoot = false)
    {
        $m = function (&$array, array $path) use ($filter, &$m) {
            $r = [];
            $isSimpleArray = ArrayUtils::isArray($array) && !ArrayUtils::isAssociative($array);
            foreach ($array as $key => $value) {
                if (static::isArray($value)) {
                    $subpath = array_merge($path, [$key]);
                    $include = $filter($value, $subpath);

                    if ($include) {
                        if (is_iterable($value)) {
                            $r[$key] = $m($value, $subpath);
                        } else {
                            $r[$key] = $value;
                        }
                    }
                } else {
                    $r[$key] = $value;
                }
            }

            if ($isSimpleArray) {
                $r = array_values($r);
            }

            return $r;
        };

        self::assertArray($array, __METHOD__ . "() expects argument 1 to be an array or array-like object.");
        self::assertIterable($array, __METHOD__ . "() expects argument 1 to be iterable.");

        if ($includeRoot) {
            $include = $filter($array, []);
            if (!$include) {
                return null;
            }
        }

        return $m($array, []);
    }

    /**
     * Recursively merge two arrays with special handling for numeric arrays.
     *
     * This method is very similar to `array_merge_recursive()` however it allows you to customize the handling of
     * numeric array merging. This is handy when you do have some nested numeric arrays because you rerely want the
     * behavior of `array_merge_recursive()` when dealing with JSON style model data.
     *
     * @param array $arr1 The first array.
     * @param array $arr2 The second array. This array will overwrite the first array when keys match.
     * @param callable|null $numeric The merge function to use when numeric arrays are encountered.
     * If you don't supply a callback then the arrays will be uniquely merged. If you want to supply a callback you can
     * look at the default definition at the top of the method.
     * @return array
     */
    public static function mergeRecursive(array $arr1, array $arr2, callable $numeric = null): array
    {
        if ($numeric === null) {
            $numeric = function (array $arr1, array $arr2, string $key): array {
                return array_values(array_unique(array_merge($arr1, $arr2)));
            };
        }

        // Do a full replace to simplify some of the walking logic.
        // For the purposes of this method, replace is the same as merge, but presumably faster.
        $arr = array_replace_recursive($arr1, $arr2);

        $clean = function (array &$arr, array $arr1, array $arr2) use (&$clean, $numeric) {
            foreach ($arr as $key => &$value) {
                // Do both array's have the key. This would indicate this is a replace operation.
                if (isset($arr1[$key]) && isset($arr2[$key])) {
                    $v1 = $arr1[$key];
                    $v2 = $arr2[$key];

                    // Are both numeric array's.
                    if (is_array($v1) && is_array($v2)) {
                        if (isset($v1[0]) && (isset($v2[0]) || empty($v2))) {
                            // This is the case where you have a numeric array replacing the other.
                            // We rarely want that.
                            $value = $numeric($v1, $v2, $key);
                        } else {
                            // Here we recurse to child arrays.
                            $clean($value, $arr1[$key] ?? null, $arr2[$key] ?? null);
                        }
                    }
                }
            }
        };
        $clean($arr, $arr1, $arr2);

        return $arr;
    }

    /**
     * Recursively flatten a nested array into a single-dimension array.
     *
     * @param string $separator The string used to separate keys.
     * @param array $array The array to flatten.
     * @param bool $stopOnSequentialArrayValues Stop flattening when a sequential array value is met, or not.
     * @return array Returns the flattened array.
     */
    public static function flattenArray(
        string $separator,
        array $array,
        bool $stopOnSequentialArrayValues = false
    ): array {
        $result = [];

        $fn = function ($array, $previousLevel = null) use ($separator, &$fn, &$result, $stopOnSequentialArrayValues) {
            foreach ($array as $key => $value) {
                $currentLevel = $previousLevel ? "{$previousLevel}{$separator}{$key}" : $key;

                if (is_object($value)) {
                    $value = (array) $value;
                }
                if (is_array($value)) {
                    if ($stopOnSequentialArrayValues && !self::isAssociative($value)) {
                        $result[$currentLevel] = $value;
                    } else {
                        $fn($value, $currentLevel);
                    }
                } else {
                    $result[$currentLevel] = $value;
                }
            }
        };

        $fn($array);

        return $result;
    }

    /**
     * Return a function that can be used to sort a dataset by one or more keys.
     *
     * Consider a dataset that looks something like this:
     *
     * ```
     * $data = [
     *     ['score' => 0, 'date' => '2010-05-02', ...],
     *     ...
     * ]
     * ```
     *
     * I can sort this dataset by score (descending), then date with the following code:
     *
     * ```
     * usort($data, ArrayUtils::datasetSortCallback('-score', 'date'));
     * ```
     *
     * Note that string comparisons are case-insensitive to emulate common database collations.
     *
     * @param string[] $keys The keys to sort by.
     * @return callable Returns a function that can be passed to `usort`.
     */
    public static function sortCallback(string ...$keys): callable
    {
        $sortKeys = [];
        foreach ($keys as $key) {
            if ($key[0] === "-") {
                $sortKeys[substr($key, 1)] = -1;
            } else {
                $sortKeys[$key] = 1;
            }
        }

        return function ($a, $b) use ($sortKeys): int {
            foreach ($sortKeys as $key => $dir) {
                $va = $a[$key];
                $vb = $b[$key];

                if (is_string($va) && is_string($vb)) {
                    // Emulate case insensitive database collations.
                    $s = strcasecmp($va, $vb);
                } else {
                    $s = $va <=> $vb;
                }

                if ($s !== 0) {
                    return $dir * $s;
                }
            }
            return 0;
        };
    }

    /**
     * Make a filte function based on a simple where like filter array.
     *
     * @param array $where The filter array.
     * @param bool $strict Whether or not to use strict comparisons.
     * @return callable Returns a function suitable to be used with `array_filter` or on its own.
     */
    public static function filterCallback(array $where, bool $strict = false): callable
    {
        return function ($row) use ($where, $strict) {
            foreach ($where as $key => $value) {
                if (!array_key_exists($key, $row)) {
                    return false;
                } elseif ($strict && $row[$key] !== $value) {
                    return false;
                } elseif ($row[$key] != $value) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * Like `array_column` except values the values ar always an array of values.
     * If there are multiple matching the same index key, then the multiple will be in that array.
     *
     * @param array $input The input array.
     * @param string|null $valueKey The key to pull out or null for the full value.
     * @param string $indexKey The key to index under. Unlike array_column this is required.
     *
     * @return array The indexed array.
     */
    public static function arrayColumnArrays(array $input, ?string $valueKey, string $indexKey): array
    {
        $result = [];
        foreach ($input as $inputRow) {
            $rowKey = $inputRow[$indexKey] ?? "";
            $rowValue = $inputRow;
            if ($valueKey !== null) {
                $rowValue = $inputRow[$valueKey] ?? null;
            }
            $result[$rowKey][] = $rowValue;
        }
        return $result;
    }

    /**
     * Remap one data key into another if it doesn't exist.
     *
     * @param array $data The data to modify.
     * @param array $modifications The mapping of 'newName' => 'oldName'.
     * @return array
     */
    public static function remapProperties(array $data, array $modifications): array
    {
        foreach ($modifications as $newName => $oldName) {
            $hasExistingNewValue = valr($newName, $data, null);
            if ($hasExistingNewValue !== null) {
                continue;
            }

            $oldValue = valr($oldName, $data, null);
            if ($oldValue !== null) {
                setvalr($newName, $data, $oldValue);
            }
        }
        return $data;
    }

    /**
     * Pluck a set of keys from an array.
     *
     * @param mixed $arr
     * @param string[] $keys
     * @return array
     * @todo Add tests.
     */
    public static function pluck($arr, array $keys): array
    {
        self::assertArray($arr, __METHOD__ . "() expects argument 1 to be an array or array-like object.");

        $keys = array_fill_keys($keys, true);
        $result = array_intersect_key($arr, $keys);
        return $result;
    }

    /**
     * Convert an array's key from pascal case to camel case.
     *
     * @param array $arr
     * @return array
     */
    public static function camelCase(array $arr): array
    {
        $result = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $result[lcfirst($key)] = self::camelCase($value);
            } else {
                $result[lcfirst($key)] = $value;
            }
        }
        return $result;
    }

    /**
     * Convert an array's key from camel case to pascal case.
     *
     * @param array $arr
     * @return array
     */
    public static function pascalCase(array $arr): array
    {
        $result = [];
        foreach ($arr as $key => $value) {
            if (is_array($value)) {
                $result[ucfirst($key)] = self::pascalCase($value);
            } else {
                $result[ucfirst($key)] = $value;
            }
        }
        return $result;
    }

    /**
     * Box a mixed variable into an array by exploding a string or returning the array.
     *
     * This method is meant to handle legacy functionality where we allow array variables to also be strings.
     *
     * @param string $glue The glue used to explode.
     * @param mixed $array The arrayish variable to work on.
     * @return array Returns an exploded string.
     */
    public static function explodeMixed(string $glue, $array): array
    {
        if (empty($array)) {
            return [];
        } elseif (is_string($array)) {
            return self::explodeTrim($glue, $array);
        } else {
            return (array) $array;
        }
    }

    /**
     * Given an object, attempt to convert it to an associative array.
     *
     * @param object $object
     * @return array
     */
    public static function objToArrayRecursive(object $object): array
    {
        return json_decode(json_encode($object), true);
    }

    /**
     * Recursively apply `json_decode()` to a provided array's values.
     *
     * @param array $array
     * @return array
     */
    public static function jsonDecodeArray(array $array): array
    {
        $returnValues = [];

        foreach ($array as $arrayKey => $arrayValue) {
            $returnValues[$arrayKey] = $arrayValue;

            if (is_array($arrayValue)) {
                $returnValues[$arrayKey] = ArrayUtils::jsonDecodeArray($arrayValue);
            }

            if (is_string($arrayValue)) {
                $decodedArrayValue = json_decode($arrayValue, true);
                if (is_array($decodedArrayValue)) {
                    $returnValues[$arrayKey] = $decodedArrayValue;
                }
            }
        }

        return $returnValues;
    }

    /**
     * Trim a prefix from all keys of an array.
     *
     * @param array $array
     * @param string $prefix
     *
     * @return array
     */
    public static function trimKeyPrefix(array $array, string $prefix): array
    {
        if (empty($prefix)) {
            return $array;
        }

        $result = [];
        foreach ($array as $key => $val) {
            if (str_starts_with($key, $prefix)) {
                $result[str_replace($prefix, "", $key)] = $val;
            }
        }
        return $result;
    }
}
