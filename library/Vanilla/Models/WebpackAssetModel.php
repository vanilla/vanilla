<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Vanilla\AddonManager;

class WebpackAssetModel {

    /** @var string[] */
    private $enabledAddonKeys = [];

    /**
     * WebpackAssetModel constructor.
     *
     * @param AddonManager $addonManager
     */
    public function __construct(AddonManager $addonManager) {
        $enabledAddons = $addonManager->getEnabled();
        foreach($enabledAddons as $addon) {
            $this->enabledAddonKeys[] = $addon;
        }
    }

    /**
     * Get the resource paths of all of the webpack scripts that we need.
     *
     * @param $sectionName
     *
     * @return string[] An array of script paths relative to vanilla's root.
     * @throws \Exception If the passed section doesn't exist.
     */
    public function getScriptPaths(string $sectionName): array {
        $sectionDir = PATH_ROOT . DS . self::DIST_DIRECTORY . DS . $sectionName;

        if (!file_exists($sectionDir)) {
            throw new \Exception("That requested section $sectionName does not exist");
        }

        $sectionRoot = '/' . $sectionName;
        $scripts = [
            $sectionRoot . '/' . self::RUNTIME_FILE_NAME . self::SCRIPT_EXTENSION,
            $sectionRoot . '/' . self::VENDOR_FILE_NAME . self::SCRIPT_EXTENSION,
        ];

        foreach ($this->enabledAddonKeys as $addonKey) {
            $filePath = $sectionDir . DS . 'addons' . DS . $addonKey . self::SCRIPT_EXTENSION;
            if (file_exists($filePath)) {
                $resourcePath = $sectionRoot . '/addons/' . $addonKey . self::SCRIPT_EXTENSION;
                $scripts[] = $resourcePath;
            }
        }

        $scripts[] = $sectionRoot . '/' . self::BOOTSTRAP_FILE_NAME . self::SCRIPT_EXTENSION;
        return $scripts;
    }
}
