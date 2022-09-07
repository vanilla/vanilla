<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\ThemeFeatures;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for theme features.
 */
class ThemeFeaturesTest extends MinimalContainerTestCase
{
    /**
     * Test that data driven theme forces most other features on.
     */
    public function testDataDriven()
    {
        $addon = new MockAddon("test", ["Features" => ["DataDrivenTheme" => true]]);
        $features = new ThemeFeatures($this->getConfig(), $addon);
        $this->assertTrue($features->useDataDrivenTheme());
        $this->assertTrue($features->useSharedMasterView());
        $this->assertTrue($features->useNewFlyouts());
        $this->assertTrue($features->useProfileHeader());
    }

    /**
     * Test that data driven theme forces most other features on.
     */
    public function testSpecificItems()
    {
        $addon = new MockAddon("test", ["Features" => ["SharedMasterView" => true, "NewFlyouts" => true]]);
        $features = new ThemeFeatures($this->getConfig(), $addon);
        $this->assertFalse($features->useDataDrivenTheme());
        $this->assertTrue($features->useSharedMasterView());
        $this->assertTrue($features->useNewFlyouts());
        $this->assertFalse($features->useProfileHeader());
    }

    /**
     * Test that data driven themes can opt out of features.
     */
    public function testOverrideDataDrivenDefaults()
    {
        $addon = new MockAddon("test", ["Features" => ["DataDrivenTheme" => true, "NewQuickLinks" => false]]);
        $features = new ThemeFeatures($this->getConfig(), $addon);
        $this->assertTrue($features->useDataDrivenTheme());
        $this->assertTrue($features->useSharedMasterView());
        $this->assertFalse($features->useNewQuickLinks());
    }

    /**
     * Test that data driven theme forces most other features on.
     */
    public function testForcedItems()
    {
        $addon = new MockAddon("test", ["Features" => []]);
        $features = new ThemeFeatures($this->getConfig(), $addon);
        $features->forceFeatures(["SharedMasterView" => true]);

        $this->assertFalse($features->useDataDrivenTheme());
        $this->assertTrue($features->useSharedMasterView());
        $this->assertFalse($features->useNewFlyouts());
        $this->assertFalse($features->useProfileHeader());
    }
}
