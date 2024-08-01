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
abstract class AbstractAddonEnableDisableTest extends SiteTestCase
{
    /**
     * We split this into multiple test cases because it takes a while.
     * Return a divisibility, either 0, 1, or 3 from here.
     *
     * @return int
     */
    abstract public function getDisibilityRemainder(): int;

    /**
     * Test enabling and disabling of every addon.
     *
     * @param string $addonKey
     * @param string|null $shouldThrowMessage
     *
     * @dataProvider provideAddonKeys
     */
    public function testEnableDisableAddon(string $addonKey, ?string $shouldThrowMessage)
    {
        $addonManager = self::container()->get(AddonManager::class);
        $addon = $addonManager->lookupAddon($addonKey);

        // Get normalized addon key.
        $addonKey = $addon->getKey();
        $this->assertInstanceOf(Addon::class, $addon);

        $addonModel = self::container()->get(AddonModel::class);

        // Some cloud plugins may require this.
        $devCompanionAddon = $addonManager->lookupAddon("dev-companion");
        if ($devCompanionAddon !== null && !$addonModel->isEnabledConfig($devCompanionAddon)) {
            $addonModel->enable($devCompanionAddon, ["force" => true]);
        }

        if (!empty($shouldThrowMessage)) {
            $this->expectExceptionMessage($shouldThrowMessage);
        }

        $builtInAddonKeys = ["conversations", "vanilla", "dashboard"];

        try {
            try {
                $enabled = $addonModel->enable($addon, ["force" => true]);
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
            }
            // check that if we run the addon structure again, there are no stray SQL statements.
            if (\Gdn::structure()->CaptureOnly) {
                $this->fail("Something left Gdn::structure() in capture only mode.");
            }
            $updateModel = \Gdn::getContainer()->get(\UpdateModel::class);
            $captured = $updateModel->runStructure(true);
            $this->assertEmpty(
                $captured,
                "Addon should not leave extra structure statements. Instead found the following statements:\n\n" .
                    implode("\n\n", $captured)
            );
        } finally {
            if (!empty($enabled)) {
                foreach ($enabled as $enabledAddon) {
                    if (in_array($enabledAddon->getKey(), $builtInAddonKeys)) {
                        continue;
                    }
                    $addonModel->disable($enabledAddon);
                }
            } else {
                $addonModel->disable($addon);
            }
        }
    }

    /**
     * Provide the keys of all testable addons.
     *
     * @return array
     */
    public function provideAddonKeys(): array
    {
        // These addons are deprecated and should intentionally throw during setup.
        $addonErrors = [
            "AdvancedStats" => "Deprecated",
            "AgeGate" => "Deprecated",
            "ImageRequired" => "requires: fileupload",
        ];
        $ignoredAddons = [
            // Dependent isn't in repo.
            "FAQ",
            "vfHelpPlan",
            // Setup is too complicated for this particular case. Circle back to this one later.
            "ElasticSearch",
        ];

        $allPluginPaths = array_merge([PATH_APPLICATIONS . "/groups"], glob(PATH_PLUGINS . "/*"));
        $provided = [];
        foreach ($allPluginPaths as $i => $pluginPath) {
            $realpath = realpath($pluginPath);
            if (!file_exists($realpath)) {
                // Addon is leftover bad symlink.
                // Don't let it pollute peoples local tests.
                continue;
            }
            if (!str_contains($realpath, PATH_ROOT . "/")) {
                // Addon is external to us.
                // We only test our own addons here.
                continue;
            }

            $addonKey = basename($pluginPath);
            if (in_array($addonKey, $ignoredAddons)) {
                continue;
            }

            $remainder = $i % 3;
            if ($remainder !== $this->getDisibilityRemainder()) {
                continue;
            }
            $shouldThrow = $addonErrors[$addonKey] ?? null;
            $provided["Addon - $addonKey"] = [$addonKey, $shouldThrow];
        }

        return $provided;
    }
}
