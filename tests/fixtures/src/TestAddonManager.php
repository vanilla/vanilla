<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Fixtures;

use Vanilla\Addon;
use Vanilla\AddonManager;

class TestAddonManager extends AddonManager {
    public function __construct(array $scanDirs = null, $cacheDir = '') {
        $cacheDir = $cacheDir ?: PATH_ROOT.'/tests/cache/am/test-manager';

        if ($scanDirs === null) {
            $root = '/tests/fixtures';
            $scanDirs = [
                Addon::TYPE_ADDON => ["$root/addons", "$root/applications", "$root/plugins"],
                Addon::TYPE_THEME => "$root/themes",
                Addon::TYPE_LOCALE => "$root/locales"
            ];
        }
        parent::__construct($scanDirs, $cacheDir);
    }

    public function findPatternInClassCollection($pattern, $collection) {
        return parent::findPatternInClassCollection($pattern, $collection);
    }
}
