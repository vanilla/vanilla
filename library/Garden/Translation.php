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
    use StaticCache {
        sc as t;
        scInit as tInit;
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
        return Gdn::translate($key, $default);
    }
}
