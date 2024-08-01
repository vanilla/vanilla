<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Theme\ThemeServiceHelper;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the theme helper model.
 */
class ThemeServiceHelperTest extends MinimalContainerTestCase
{
    /**
     * Utility for getting the helper.
     */
    private function getHelper(): ThemeServiceHelper
    {
        return self::container()->get(ThemeServiceHelper::class);
    }

    /**
     * Test the config theme key fetching.
     */
    public function testConfigThemeKey()
    {
        $this->setConfigs([
            "Garden.Theme" => "oldKey",
        ]);
        $this->assertEquals("oldKey", $this->getHelper()->getConfigThemeKey());

        $this->setConfigs([
            "Garden.Theme" => "oldKey",
            "Garden.CurrentTheme" => "newKey",
        ]);
        $this->assertEquals("newKey", $this->getHelper()->getConfigThemeKey());
    }

    /**
     * Test current theme always visible.
     */
    public function testVisiblityCurrentTheme()
    {
        $this->setConfigs([
            "Garden.CurrentTheme" => "theme-active",
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("theme-active", ["hidden" => true])));
    }

    /**
     * With no other factors test the theme hidden value.
     */
    public function testThemeHidden()
    {
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon("theme-no-hidden-field")));
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon("theme-hidden-true", ["hidden" => true])));

        // Doesn't actually do anything,
        $this->assertFalse(
            $this->getHelper()->isThemeVisible(new MockAddon("theme-hidden-false", ["hidden" => false]))
        );

        // These are on be default
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("theme-foundation")));
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("keystone")));
    }

    /**
     * Test the configuration based visibilies.
     */
    public function testVisibilityConfig()
    {
        $this->setConfigs([]);
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon("theme-hidden", ["hidden" => true])));

        $this->setConfigs([
            "Garden.Themes.Visible" => "all",
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("theme-hidden", ["hidden" => true])));
        $this->setConfigs([
            "Garden.Themes.Visible" => "some-theme, theme-hidden",
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("theme-hidden", ["hidden" => true])));
    }

    /**
     * Test the configuration based visibilies.
     */
    public function testSites()
    {
        $this->setConfigs([]);
        $this->assertFalse(
            $this->getHelper()->isThemeVisible(
                new MockAddon("theme-hidden", ["sites" => ["adam.vanillawip.com"]]),
                "todd.vanillawip.com"
            )
        );

        $this->assertTrue(
            $this->getHelper()->isThemeVisible(
                new MockAddon("theme-hidden", ["sites" => ["adam.vanillawip.com"]]),
                "adam.vanillawip.com"
            )
        );

        $this->assertTrue(
            $this->getHelper()->isThemeVisible(
                new MockAddon("theme-hidden", ["site" => "adam.vanillawip.com"]),
                "adam.vanillawip.com"
            )
        );

        // Globs
        $this->assertTrue(
            $this->getHelper()->isThemeVisible(
                new MockAddon("theme-hidden", ["sites" => ["adam-*.vanillawip.com"]]),
                "adam-hub.vanillawip.com"
            )
        );
    }

    /**
     * Test that sysadmins always see all themes.
     */
    public function testThemeVisibleSysAdmin()
    {
        $this->setUserInfo(["Admin" => 2]);
        $this->setConfigs([]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon("theme-hidden", ["hidden" => true])));
    }

    /**
     * Test saving the current themes into the visible themes.
     */
    public function testSaveCurrentThemeToVisible()
    {
        $this->setConfigs([
            ThemeServiceHelper::CONFIG_THEMES_VISIBLE => ThemeServiceHelper::ALL_VISIBLE,
            ThemeServiceHelper::CONFIG_DESKTOP_THEME => "test-active",
        ]);
        $this->getHelper()->saveCurrentThemeToVisible();
        $this->assertEquals(
            ThemeServiceHelper::ALL_VISIBLE,
            self::getConfig()->get(ThemeServiceHelper::CONFIG_THEMES_VISIBLE)
        );

        $this->setConfigs([
            ThemeServiceHelper::CONFIG_THEMES_VISIBLE => "",
            ThemeServiceHelper::CONFIG_DESKTOP_THEME => "test-active",
            ThemeServiceHelper::CONFIG_MOBILE_THEME => "test-active",
        ]);
        $this->getHelper()->saveCurrentThemeToVisible();
        $this->assertEquals("test-active", self::getConfig()->get(ThemeServiceHelper::CONFIG_THEMES_VISIBLE));

        $this->setConfigs([
            ThemeServiceHelper::CONFIG_THEMES_VISIBLE => "",
            ThemeServiceHelper::CONFIG_DESKTOP_THEME => "test-desktop",
            ThemeServiceHelper::CONFIG_MOBILE_THEME => "test-mobile",
            ThemeServiceHelper::CONFIG_CURRENT_THEME => "test-current",
        ]);
        $this->getHelper()->saveCurrentThemeToVisible();
        $this->assertEquals(
            "test-desktop,test-mobile,test-current",
            self::getConfig()->get(ThemeServiceHelper::CONFIG_THEMES_VISIBLE)
        );
    }
}
