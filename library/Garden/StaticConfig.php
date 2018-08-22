<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden;

use Gdn;

/**
 * For classes that need to cache some static values and configs.
 *
 */
trait StaticConfig {
    /**
     * @var array
     */
    protected static $c = [];

    /**
     * @var boolean
     */
    protected static $cInit = false;

    /**
     * Returns of cached value of config key or calls Gdn::config() function if key not found yet
     *
     * @param string $key Config key to get
     * @param mixed $default Default value for config key to set if not set up
     *
     * @return mixed
     */
    public static function c(string $key, $default = false) {
        if (!self::$cInit) {
            self::$c = self::cInit();
            self::$cInit = true;
        }
        if (empty($key)) {
            return '';
        } else {
            if (!key_exists($key, self::$c)) {
                self::$c[$key] = Gdn::config($key, $default);
            }
            return self::$c[$key];
        }
    }

    /**
     * Returns all configuration cached properties
     *
     * @return array
     */
    public static function cAll() {
        if (!self::$cInit) {
            self::$c = self::cInit();
            self::$cInit = true;
        }
        return self::$c;
    }
    /**
     * Returns array of config for current class
     *
     * @return array
     */
    protected static function cInit() {
        return [];
    }
}
