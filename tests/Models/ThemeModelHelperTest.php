<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\ThemeModelHelper;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the theme helper model.
 */
class ThemeModelHelperTest extends MinimalContainerTestCase {

    /**
     * Utility for getting the helper.
     */
    private function getHelper(): ThemeModelHelper {
        return self::container()->get(ThemeModelHelper::class);
    }

    /**
     * Test the config theme key fetching.
     */
    public function testConfigThemeKey() {
        $this->setConfigs([
            'Garden.Theme' => 'oldKey'
        ]);
        $this->assertEquals('oldKey', $this->getHelper()->getConfigThemeKey());

        $this->setConfigs([
            'Garden.Theme' => 'oldKey',
            'Garden.CurrentTheme' => 'newKey',
        ]);
        $this->assertEquals('newKey', $this->getHelper()->getConfigThemeKey());
    }

    /**
     * Test current theme always visible.
     */
    public function testVisiblityCurrentTheme() {
        $this->setConfigs([
            'Garden.CurrentTheme' => 'theme-active'
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon('theme-active', ['hidden' => true])));
    }

    /**
     * With no other factors test the theme hidden value.
     */
    public function testThemeHidden() {
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon('theme-no-hidden-field')));
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden-true', ['hidden' => true])));
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden-false', ['hidden' => false])));
    }


    /**
     * Test the configuration based visibilies.
     */
    public function testVisibilityConfig() {
        $this->setConfigs([]);
        $this->assertFalse($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden', ['hidden' => true])));

        $this->setConfigs([
            'Garden.Themes.Visible' => 'all',
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden', ['hidden' => true])));
        $this->setConfigs([
            'Garden.Themes.Visible' => 'some-theme, theme-hidden',
        ]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden', ['hidden' => true])));
    }

    /**
     * Test the configuration based visibilies.
     */
    public function testSites() {
        $this->setConfigs([]);
        $this->assertFalse($this->getHelper()->isThemeVisible(
            new MockAddon(
                'theme-hidden',
                ['sites' => ['adam.vanillawip.com']]
            ),
            'todd.vanillawip.com'
        ));

        $this->assertTrue($this->getHelper()->isThemeVisible(
            new MockAddon(
                'theme-hidden',
                ['sites' => ['adam.vanillawip.com']]
            ),
            'adam.vanillawip.com'
        ));

        $this->assertTrue($this->getHelper()->isThemeVisible(
            new MockAddon(
                'theme-hidden',
                ['site' => 'adam.vanillawip.com']
            ),
            'adam.vanillawip.com'
        ));

        // Globs
        $this->assertTrue($this->getHelper()->isThemeVisible(
            new MockAddon(
                'theme-hidden',
                ['sites' => ['adam-*.vanillawip.com']]
            ),
            'adam-hub.vanillawip.com'
        ));
    }

    /**
     * Test that sysadmins always see all themes.
     */
    public function testThemeVisibleSysAdmin() {
        $this->setUserInfo([ 'Admin' => 2 ]);
        $this->setConfigs([]);
        $this->assertTrue($this->getHelper()->isThemeVisible(new MockAddon('theme-hidden', ['hidden' => true])));
    }
}
