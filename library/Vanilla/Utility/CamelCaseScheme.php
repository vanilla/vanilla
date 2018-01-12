<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Utility;


class CamelCaseScheme extends NameScheme {

    /**
     * Convert a name into this name spec.
     *
     * @param string $name The name to convert to this scheme.
     * @return string Returns the new name as a string.
     */
    public function convert($name) {
        // Camel case underscores, spaces and dashes.
        $name = preg_replace_callback('`([ _-]+[a-z])`', function ($m) {
            return strtoupper(ltrim($m[1], ' _-'));
        }, $name);

        // Check for the final ID case.
        if (preg_match('`[a-z](I[Dd]|I[Pp])(s?)$`', $name, $m)) {
            $sx = strtoupper($m[1]).$m[2];
            $name = substr($name, 0, -strlen($sx));
        }

        // Fix multiple occurrences of capital letters.
        $name = preg_replace_callback('`([A-Z]{2,})(.|$)`', function ($m) {
            $str = $m[1];

            $str = ucfirst(strtolower($str));

            if (strlen($str) > 2 && !empty($m[2])) {
                $str = substr($str, 0, -1).strtoupper(substr($str, -1));
            }

            return $str.$m[2];
        }, $name);

        if (!empty($sx)) {
            $name .= $sx;
        }

        // Make sure the first character is lowercase.
        $name = lcfirst($name);

        // Fix some known exceptions.
        $name = preg_replace_callback('`(Id|Ip|Php)(?=[A-Z0-9]|$|s$)`', function ($m) {
            return strtoupper($m[1]);
        }, $name);

        return $name;
    }

    /**
     * Test that a name is valid for this scheme.
     *
     * @param string $name The name to test.
     * @return bool Returns **true** if the name is valid for this spec or **false** otherwise.
     */
//    public function valid($name) {
//        // Test the basic regex of the name.
//        if (!preg_match('`^[a-z][a-zA-Z0-9]*$`', $name)) {
//            return false;
//        }
//
//        // There should not be two capital letters together, unless it's a string ending in "ID".
//        if (preg_match('`[A-Z]{2,}`', $name) && !preg_match('`[a-z](ID|IP)s?$`', $name)) {
//            return false;
//        }
//
//        return true;
//    }
}
