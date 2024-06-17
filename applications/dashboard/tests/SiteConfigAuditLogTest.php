<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Dashboard;

use Vanilla\Models\AddonModel;
use VanillaTests\AuditLogTestTrait;
use VanillaTests\ExpectedAuditLog;
use VanillaTests\SiteTestCase;

/**
 * Tests for audit logs of site configurations and other related admin settings pages that are defined in config.
 */
class SiteConfigAuditLogTest extends SiteTestCase
{
    use AuditLogTestTrait;

    /**
     * Test generic configuration changes get logged.
     */
    public function testGenericConfig(): void
    {
        $config = \Gdn::config();
        $config->saveToConfig("myKey.test", "foo");
        $config->shutdown();
        $this->assertAuditLogged(
            ExpectedAuditLog::create("configuration_change")
                ->withMessage("Site configuration was modified.")
                ->withModification("myKey.test", null, "foo")
        );

        $config->saveToConfig("myKey.test", "bar");
        $config->shutdown();
        $this->assertAuditLogged(
            ExpectedAuditLog::create("configuration_change")->withModification("myKey.test", "foo", "bar")
        );

        // We can handle multiple changes at once.
        $config->saveToConfig(["nested.test" => "nest1", "nested.test2" => "nest2"]);
        $config->saveToConfig("arrayVal", [1, 2, 3]);
        $config->saveToConfig("objVal", ["foo" => "bar"]);
        $config->shutdown();
        $this->assertAuditLogged(
            ExpectedAuditLog::create("configuration_change")
                ->withModification("nested.test", null, "nest1")
                ->withModification("nested.test2", null, "nest2")
                ->withModification("arrayVal", null, [1, 2, 3])
                ->withModification("objVal", null, ["foo" => "bar"])
        );
    }

    /**
     * Test that certain secret config keys are blanked out.
     *
     * @return void
     */
    public function testNoLoggingSecrets(): void
    {
        $config = \Gdn::config();
        $config->saveWithoutAuditLog("myKey.secret", "foo");
        $config->shutdown();
        $this->assertNotAuditLogged(ExpectedAuditLog::create("configuration_change"));
    }

    /**
     * Test events when addons are enabled and disabled.
     *
     * @return void
     */
    public function testAddonEnableDisable(): void
    {
        $this->api()->patch("/addons/jsconnect", ["enabled" => true]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("addon_toggled")->withMessage("Addon `Vanilla jsConnect` was enabled.")
        );

        $this->api()->patch("/addons/jsconnect", ["enabled" => false]);
        $this->assertAuditLogged(
            ExpectedAuditLog::create("addon_toggled")->withMessage("Addon `Vanilla jsConnect` was disabled.")
        );
    }
}
