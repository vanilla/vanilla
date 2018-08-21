<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

use Gdn;

/**
 * For classes that want translation to incorporate.
 *
 */
trait Translation {
    /**
     * @var array
     */
    protected static $t = [];

    /**
     * @var boolean
     */
    protected static $tInit = false;

    /**
     * Returns of translation of a string or all translations as an associative array
     *
     * @param string $key String to translate
     *
     * @return string
     */
    public static function t(string $key) {
        if (!self::$tInit) {
            self::$t = self::tInit();
            self::$tInit = true;
        }
        if (empty($key)) {
            return '';
        } else {
            if (!key_exists($key, self::$t)) {
                self::$t[$key] = Gdn::translate($key);
            }
            return self::$t[$key];
        }
    }

    /**
     * Returns all translations as an associative array
     *
     * @return array
     */
    public static function tAll() {
        if (!self::$tInit) {
            self::$t = self::tInit();
            self::$tInit = true;
        }
        return self::$t;
    }
    /**
     * Returns array of translations for current class
     *
     * @return array
     */
    protected static function tInit() {
        return [];
    }
}
