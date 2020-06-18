<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

/**
 * Class OffNullCache.
 *
 * A NullCache that is always offline
 */
class OfflineNullCache extends NullCache {

    /**
     * @return bool
     */
    public function online() {
        return false;
    }

    /**
     * Get the status of the active cache.
     *
     * @param bool $forceEnable
     * @return bool
     */
    public static function activeEnabled($forceEnable = false) {
        return false;
    }
}
