<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Utility;


/**
 * A class that helps to enforce and convert a naming scheme.
 */
abstract class NameScheme {
    /**
     * Convert a name into this name spec.
     *
     * @param string $name The name to convert to this scheme.
     * @return string Returns the new name as a string.
     */
    abstract public function convert($name);

    /**
     * Recursively convert all of the array keys in an array to this name scheme.
     *
     * @param array $array The array to convert.
     * @return array Returns the converted array.
     */
    public function convertArrayKeys(array $array) {
        $result = [];
        foreach ($array as $key => $value) {
            $key = $this->convert($key);
            if (is_array($value)) {
                $value = $this->convertArrayKeys($value);
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * Test that a name is valid for this scheme.
     *
     * @param string $name The name to test.
     * @return bool Returns **true** if the name is valid for this spec or **false** otherwise.
     */
    public function valid($name) {
        return $this->convert($name) === $name;
    }
}
