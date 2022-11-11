<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Vanilla\Addon;
use Vanilla\AddonManager;

/**
 * Addon manager that loads from test fixture directories.
 */
class TestAddonManager extends AddonManager
{
    /**
     * Get the default directories to scan.
     *
     * @return array
     */
    public static function getDefaultScanDirectories(): array
    {
        $root = "/tests/fixtures";
        return [
            Addon::TYPE_ADDON => ["$root/addons/addons", "$root/applications", "$root/plugins"],
            Addon::TYPE_THEME => ["$root/addons/themes", "$root/themes"],
            Addon::TYPE_LOCALE => "$root/locales",
        ];
    }

    /**
     * @inheritdoc
     */
    public function __construct(array $scanDirs = null, $cacheDir = "")
    {
        $cacheDir = $cacheDir ?: PATH_ROOT . "/tests/cache/am/test-manager";

        if ($scanDirs === null) {
            $root = "/tests/fixtures";
            $scanDirs = [
                Addon::TYPE_ADDON => ["$root/addons/addons", "$root/applications", "$root/plugins"],
                Addon::TYPE_THEME => ["$root/addons/themes", "$root/themes"],
                Addon::TYPE_LOCALE => "$root/locales",
            ];
        }
        parent::__construct($scanDirs, $cacheDir);
    }

    /**
     * Expose publicly.
     * @inheritdoc
     */
    public function matchClass($pattern, $class)
    {
        return parent::matchClass($pattern, $class);
    }
}
