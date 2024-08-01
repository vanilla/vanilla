<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Cache;

use Garden\StaticCacheTrait;

/**
 * Static cache class used to replace old static variables so they can be cleared in one place.
 */
final class StaticCache {

    /**
     * @var array
     */
    private static $sCache = [];

    /**
     * Get a value from the static cache, or hydrate it and return it.
     *
     * @param string $key
     * @param callable $hydrate
     *
     * @return mixed
     */
    public static function getOrHydrate(string $key, callable $hydrate) {
        if (array_key_exists($key, self::$sCache)) {
            return self::$sCache[$key];
        } else {
            $value = call_user_func($hydrate);
            self::$sCache[$key] = $value;
            return $value;
        }
    }

    /**
     * Clear the static cache.
     */
    public static function clear() {
        self::$sCache = [];
    }
}
