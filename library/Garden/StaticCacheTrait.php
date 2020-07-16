<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

/**
 * For classes that need to cache some static values and configs.
 *
 */
trait StaticCacheTrait {
    /**
     * @var array
     */
    protected static $sCache = [];

    /**
     * @var boolean
     */
    protected static $scInit = false;

    /**
     * Returns of cached value or calls self::f() function if key not found yet
     *
     * @param string $key Key to get
     * @param mixed $default Default value for key to set if not set up
     *
     * @return mixed
     */
    public static function sc(string $key, $default = false) {
        if (defined('TESTMODE_ENABLED') && TESTMODE_ENABLED) {
            return self::f($key, $default);
        }

        if (empty($key)) {
            throw new \Exception('Static cache key can not be empty!');
        } else {
            if (!self::$scInit) {
                self::$sCache = self::scInit();
                self::$scInit = true;
            }
            if (!key_exists($key, self::$sCache)) {
                self::$sCache[$key] = self::f($key, $default);
            }
            return self::$sCache[$key];
        }
    }

    /**
     * Returns array of default values for current class
     *
     * @return array
     */
    protected static function scInit() {
        return [];
    }

    /**
     * Calculates value for particular key
     *
     * @param string $key Key to store
     * @param mixed $default Default value for the key if not defined
     *
     * @return array
     */
    protected static function f(string $key, $default) {
        return $default;
    }
}
