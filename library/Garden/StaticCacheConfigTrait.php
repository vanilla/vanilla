<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

use Gdn;

/**
 * For classes that need to cache some static values and configs.
 *
 */
trait StaticCacheConfigTrait {
    use StaticCacheTrait {
        sc as c;
        scInit as cInit;
    }

    /**
     * Calculates value for particular key (overwrite f() of StaticCache trait)
     *
     * @param string $key Key to store
     * @param mixed $default Default value for the key if not defined
     *
     * @return array
     */
    protected static function f(string $key, $default) {
        return Gdn::config($key, $default);
    }
}
