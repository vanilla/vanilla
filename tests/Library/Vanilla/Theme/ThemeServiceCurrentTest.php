<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\AddonManager;
use Vanilla\Theme\Asset\CssThemeAsset;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Theme\ThemeFeatures;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\Theme\MockThemeProvider;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for getting the current theme from the theme model.
 */
class ThemeServiceCurrentTest extends MinimalContainerTestCase {

    const ASSET_THEME = 'mock-asset-theme';

    const ADDON_THEME = 'mock-addon-theme';

    const MOBILE_ADDON_THEME = 'mock-mobile-addon-theme';

    /** @var MockThemeProvider */
    private $mockThemeProvider;

    /** @var MockAddonManager */
    private $addonManager;

    /**
     * Prepare the container.
     */
    public function setUp(): void {
        parent::setUp();
        $this->addonManager = self::container()->get(MockAddonManager::class);
        $this->mockThemeProvider = self::container()->get(MockThemeProvider::class);

        // Fresh container.
        self::configureContainer();

        self::container()
            ->rule(ThemeService::class)
            ->addCall('addThemeProvider', [$this->mockThemeProvider])
            ->setInstance(AddonManager::class, $this->addonManager);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        parent::tearDown();
        isMobile(null);
    }

    /**
     * @return ThemeService
     */
    private function themeModel(): ThemeService {
        return self::container()->get(ThemeService::class);
    }

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

        $model = $this->themeModel();

        $this->assertEquals(self::ADDON_THEME, $model->getCurrentThemeAddon()->getKey());
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getThemeID());
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(true, $features->useSharedMasterView());

        isMobile(true);

        // To the old system.
        $this->assertEquals(self::MOBILE_ADDON_THEME, $model->getCurrentThemeAddon()->getKey());

        // To the new system.
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getThemeID());
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        // We have overlayed the new assets on top.
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme(true)->getAssets()[ThemeAssetFactory::ASSET_STYLES]->__toString());

        // Theme features from the mobile theme were properly preserved.
        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(false, $features->useSharedMasterView());
    }
}
