<?php
/**
 * @author Alexander Kim <alexander.k@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL v2
 */

namespace Garden;

use Gdn;

/**
 * For classes that want translation to incorporate.
 *
 */
trait StaticCacheTranslationTrait {
    use StaticCacheTrait {
        sc as t;
        scInit as tInit;
    }

    /**
     * Calculates value for particular key (overwrite f() of StaticCache trait)
     *
     * @param string $key Key to store
     * @param mixed $default Default value for the key if not defined
     *
     * @return string
     */
    protected static function f(string $key, $default) {
        return Gdn::translate($key, $default);
    }
}
