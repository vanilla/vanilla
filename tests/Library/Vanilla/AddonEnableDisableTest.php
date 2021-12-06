<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Models\AddonModel;
use VanillaTests\SiteTestCase;

/**
 * Test that enables and disables every addon to test their bootstrapping.
 */
class AddonEnableDisableTest extends SiteTestCase {

    /**
     * Test enabling and disabling of every addon.
     *
     * @param string $addonKey
     * @param string|null $shouldThrowMessage
     *
     * @dataProvider provideAddonKeys
     */
    public function testEnableDisableAddon(string $addonKey, ?string $shouldThrowMessage) {
        $addonManager = self::container()->get(AddonManager::class);
        $addon = $addonManager->lookupAddon($addonKey);

        // Get normalized addon key.
        $addonKey = $addon->getKey();
        $this->assertInstanceOf(Addon::class, $addon);

        $addonModel = self::container()->get(AddonModel::class);

        if (!empty($shouldThrowMessage)) {
            $this->expectExceptionMessage($shouldThrowMessage);
        }

        try {
            $addonModel->enable($addon, ['force' => true]);
        } catch (\Exception $e) {
            if ($shouldThrowMessage) {
                $this->expectExceptionMessage($shouldThrowMessage);
                throw $e;
            } else {
                // Unexpected.
                $message = formatException($e);
                $path = $addon->path();
                $message = "Addon '$addonKey' at '$path' failed to enable properly.\n" . $message;
                $this->fail($message);
            }
        } finally {
            // Then disable the addon.
            $addonModel->disable($addon);
        }
    }

    /**
     * Provide the keys of all testable addons.
     *
     * @return array
     */
    public function provideAddonKeys() {
        // These addons are deprecated and should intentionally throw during setup.
        $addonErrors = [
            'AdvancedStats' => 'Deprecated',
            'AgeGate' => 'Deprecated',
            'ImageRequired' => 'requires: fileupload',
        ];
        $ignoredAddons = [
            // Dependent isn't in repo.
            'FAQ',
            'vfHelpPlan',
            // Setup is too complicated for this particular case. Circle back to this one later.
            'ElasticSearch'
        ];

        $allPluginPaths = glob(PATH_PLUGINS . '/*');
        $provided = [];
        foreach ($allPluginPaths as $pluginPath) {
            $realpath = realpath($pluginPath);
            if (!file_exists($realpath)) {
                // Addon is leftover bad symlink.
                // Don't let it pollute peoples local tests.
                continue;
            }
            if (!str_contains($realpath, PATH_ROOT . '/')) {
                // Addon is external to us.
                // We only test our own addons here.
                continue;
            }


            $addonKey = basename($pluginPath);
            if (in_array($addonKey, $ignoredAddons)) {
                continue;
            }
            $shouldThrow = $addonErrors[$addonKey] ?? null;
            $provided["Addon - $addonKey"] = [$addonKey, $shouldThrow];
        }

        return $provided;
    }
}
