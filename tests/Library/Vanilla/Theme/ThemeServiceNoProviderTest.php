<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Theme;

use Vanilla\Theme\ThemeService;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Core tests for the theme model.
 */
class ThemeServiceNoProviderTest extends SharedBootstrapTestCase {

    /**
     * @return ThemeService
     */
    private function getThemeModel(): ThemeService {
        return self::container()->get(ThemeService::class);
    }

    /**
     * Test that getting the current theme doesn't fail when no provider is configured.
     */
    public function testCurrentNoProvider() {
        // No provider should be registered.
        $this->runWithConfig([
            'Garden.Theme' => 'asdfasdf',
            'Garden.MobileTheme' => 'asdfasdfasdf',
            'Garden.CurrentTheme' => 'asdfasdf'
        ], function () {
            $theme = @$this->getThemeModel()->getCurrentTheme();
            $this->assertSame(ThemeService::FALLBACK_THEME_KEY, $theme->getThemeID());
        });
    }

    /**
     * Test getting a theme when no provider is registered.
     */
    public function testNoProviderRegisteredWarning() {
        // No provider should be registered.
        $this->expectWarning();
        $theme = $this->getThemeModel()->getTheme(1);
    }

    /**
     * Test getting a theme when no provider is registered.
     */
    public function testNoProviderRegisteredReturn() {
        // No provider should be registered.
        $theme = @$this->getThemeModel()->getTheme(1);
        $this->assertSame(ThemeService::FALLBACK_THEME_KEY, $theme->getThemeID());
    }
}
