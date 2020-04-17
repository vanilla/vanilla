<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Vanilla\Models\ThemeModel;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Core tests for the theme model.
 */
class ThemeModelTest extends SharedBootstrapTestCase {

    /**
     * @return ThemeModel
     */
    private function getThemeModel(): ThemeModel {
        return self::container()->get(ThemeModel::class);
    }

    /**
     * Test getting a theme when no provider is registered.
     */
    public function testNoProviderRegisteredWarning() {
        // No provider should be registered.
        $this->expectWarning();
        $theme = $this->getThemeModel()->getThemeWithAssets(1);
        $this->assertSame(ThemeModel::FALLBACK_THEME_KEY, $theme['themeID']);
    }

    /**
     * Test getting a theme when no provider is registered.
     */
    public function testNoProviderRegisteredReturn() {
        // No provider should be registered.
        $theme = @$this->getThemeModel()->getThemeWithAssets(1);
        $this->assertSame(ThemeModel::FALLBACK_THEME_KEY, $theme['themeID']);
    }
}
