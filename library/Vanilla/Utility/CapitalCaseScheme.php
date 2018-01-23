<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla\Utility;


class CapitalCaseScheme extends CamelCaseScheme {
    public function convert($name) {
        $result = parent::convert($name);
        $result = ucfirst($result);

        // Fix some known exceptions.
        // This is done a second time because of the above ucfirst().
        $result = preg_replace_callback('`(Id|Ip|Php)(?=[A-Z0-9]|$|s$)`', function ($m) {
            return strtoupper($m[1]);
        }, $result);

        return $result;
    }

//    public function valid($name) {
//        // Make sure the first character is capitalized.
//        if (!preg_match('`^[A-Z]`', $name)) {
//            return false;
//        }
//
//        // Otherwise this should just be a valid camel case name.
//        $name = lcfirst($name);
//
//        return parent::valid($name);
//    }
}
