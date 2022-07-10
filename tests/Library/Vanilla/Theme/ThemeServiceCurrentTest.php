<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\Theme\Asset\CssThemeAsset;
use Vanilla\Theme\Asset\JsonThemeAsset;
use Vanilla\Theme\Theme;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Theme\ThemeFeatures;
use VanillaTests\Fixtures\MockAddon;

/**
 * Tests for getting the current theme from the theme model.
 */
class ThemeServiceCurrentTest extends MockThemeTestCase {

    const ASSET_THEME = 'mock-asset-theme';

    const ADDON_THEME = 'mock-addon-theme';

    const MOBILE_ADDON_THEME = 'mock-mobile-addon-theme';

    /**
     * Test the we get consistent results when all themes are set to the same.
     */
    public function testGetCurrentAllSame() {
        $addon = new MockAddon(self::ADDON_THEME, [
            'Features' => [
                'SharedMasterView' => true,
            ]
        ]);
        $mobileAddon = new MockAddon(self::MOBILE_ADDON_THEME);
        $this->addonManager->pushAddon($addon);
        $this->addonManager->pushAddon($mobileAddon);

        $addonTheme = $this->mockThemeProvider->addTheme([
            'themeID' => self::ADDON_THEME,
            'assets' => [
                'styles' => new CssThemeAsset(self::ADDON_THEME, ''),
            ]
        ], $addon);

        $mobileTheme = $this->mockThemeProvider->addTheme([
            'themeID' => self::MOBILE_ADDON_THEME,
            'assets' => [
                'styles' => new CssThemeAsset(self::MOBILE_ADDON_THEME, ''),
            ]
        ], $mobileAddon);

        $assetTheme = $this->mockThemeProvider->addTheme([
            'themeID' => self::ASSET_THEME,
            'parentTheme' => self::ADDON_THEME,
            'assets' => [
                'styles' => new CssThemeAsset(self::ASSET_THEME, ''),
            ]
        ], $addon);

        $this->setConfigs([
            ThemeServiceHelper::CONFIG_DESKTOP_THEME => self::ADDON_THEME,
            ThemeServiceHelper::CONFIG_MOBILE_THEME => self::MOBILE_ADDON_THEME,
            ThemeServiceHelper::CONFIG_CURRENT_THEME => self::ASSET_THEME,
        ]);

        $model = $this->themeService();

        $this->assertEquals(self::ADDON_THEME, $model->getCurrentThemeAddon()->getKey());

        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getThemeID());
        $this->assertEquals(self::ADDON_THEME.self::ASSET_THEME, $model->getCurrentTheme()->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(true, $features->useSharedMasterView());

        isMobile(true);
        // To the old system.
        $this->assertEquals(self::MOBILE_ADDON_THEME, $model->getCurrentThemeAddon()->getKey());

        // To the new system.
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getThemeID());
        $this->assertEquals(self::ADDON_THEME.self::ASSET_THEME, $model->getCurrentTheme()->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        // We have overlayed the new assets on top.
        $this->assertEquals(self::ADDON_THEME.self::ASSET_THEME, $model->getCurrentTheme(true)->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        // Theme features from the mobile theme were properly preserved.
        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(false, $features->useSharedMasterView());
    }

    /**
     * Test theme options working.
     *
     * @param string $optionName
     *
     * @dataProvider provideOptions
     */
    public function testThemeOptionVariables(string $optionName) {
        $this->setConfig('Garden.ThemeOptions.Styles.Value', $optionName);
        $addon = new Addon('/tests/fixtures/addons/themes/theme-options');
        $theme = Theme::fromAddon($addon);

        /** @var JsonThemeAsset $variables */
        $variables = $theme->getAsset('variables');
        $this->assertTrue($variables->get($optionName, false));
    }

    /**
     * @return string[][]
     */
    public function provideOptions(): array {
        return [
            ['option1'],
            ['option2'],
        ];
    }

    /**
     * Test theme variable overlay.
     *
     * @param array $in1
     * @param array $overlay
     * @param array $expected
     *
     * @dataProvider provideVariableOverlay
     */
    public function testThemeVariableOverlay(array $in1, array $overlay, array $expected) {
        $addon = new MockAddon(self::ADDON_THEME, []);
        $mobileAddon = new MockAddon(self::MOBILE_ADDON_THEME);
        $this->addonManager->pushAddon($addon);

        $addonTheme = $this->mockThemeProvider->addTheme([
            'themeID' => self::ADDON_THEME,
            'assets' => [
                'variables' => new JsonThemeAsset(json_encode($in1), ''),
            ]
        ], $addon);

        $addonTheme->overlayVariables($overlay);
        $this->assertEquals($expected, $addonTheme->getAsset('variables')->getValue());
    }

    /**
     * @return array
     */
    public function provideVariableOverlay(): array {
        return [
            'simple' => [
                [ 'foo' => 'foo' ],
                [ 'bar' => 'bar' ],
                [
                    'foo' => 'foo',
                    'bar' => 'bar'
                ],
            ],
            'nested' => [
                [
                    'foo' => [
                        'foo1' => 'foo1',
                    ],
                ],
                [
                    'foo' => [
                        'foo2' => 'foo2',
                    ],
                ],
                [
                    'foo' => [
                        'foo1' => 'foo1',
                        'foo2' => 'foo2',
                    ],
                ],
            ],
            'array' => [
                [
                    'foo' => [1, 2, 3],
                ],
                [
                    'foo' => [1.1],
                ],
                [
                    'foo' => [1.1],
                ],
            ],
        ];
    }

    /**
     * Test that we fall back to the current theme when the mock addon fails.
     */
    public function testDeletePreviewedTheme() {
        $currentAddon = new MockAddon('mock-current-addon');
        $this->addonManager->pushAddon($currentAddon);

        $currentTheme = $this->mockThemeProvider->addTheme([
            'themeID' => 'mock-current-addon',
        ], $currentAddon);
        $this->setConfigs([
            ThemeServiceHelper::CONFIG_DESKTOP_THEME => 'mock-current-addon',
            ThemeServiceHelper::CONFIG_MOBILE_THEME => 'mock-current-addon',
            ThemeServiceHelper::CONFIG_CURRENT_THEME => 'mock-current-addon',
        ]);

        $previewAddon = new MockAddon('mock-preview-addon');
        $this->addonManager->pushAddon($previewAddon);

        $previewTheme = $this->mockThemeProvider->addTheme([
            'themeID' => 'mock-preview-addon',
        ], $previewAddon);

        // Theme is previewed.
        $this->themeService()->setPreviewTheme('mock-preview-addon');
        $theme = $this->themeService()->getCurrentTheme();
        $this->assertEquals('mock-preview-addon', $theme->getThemeID());

        // Delete the preview theme
        $this->themeService()->deleteTheme('mock-preview-addon');
        $theme = $this->themeService()->getCurrentTheme();
        $this->assertEquals('mock-current-addon', $theme->getThemeID());
    }
}
