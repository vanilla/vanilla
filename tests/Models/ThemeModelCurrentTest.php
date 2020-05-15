<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Models;

use Garden\Container\Reference;
use Vanilla\Contracts\AddonProviderInterface;
use Vanilla\Models\ThemeModel;
use Vanilla\Models\ThemeModelHelper;
use Vanilla\Theme\StyleAsset;
use Vanilla\Theme\ThemeFeatures;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonProvider;
use VanillaTests\Fixtures\MockThemeProvider;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for getting the current theme from the theme model.
 */
class ThemeModelCurrentTest extends MinimalContainerTestCase {

    const ASSET_THEME = 'asset-theme';

    const ADDON_THEME = 'addon-theme';

    const MOBILE_ADDON_THEME = 'mobile-addon-theme';


    /** @var MockThemeProvider */
    private $mockThemeProvider;

    /** @var MockAddonProvider */
    private $mockAddonProvider;

    /**
     * Prepare the container.
     */
    public function setUp(): void {
        parent::setUp();
        $this->mockAddonProvider = self::container()->get(MockAddonProvider::class);
        $this->mockThemeProvider = self::container()->get(MockThemeProvider::class);

        // Fresh container.
        self::configureContainer();

        self::container()
            ->rule(ThemeModel::class)
            ->addCall('addThemeProvider', [$this->mockThemeProvider])
            ->setInstance(AddonProviderInterface::class, $this->mockAddonProvider);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void {
        parent::tearDown();
        isMobile(null);
    }

    /**
     * @return ThemeModel
     */
    private function themeModel(): ThemeModel {
        return self::container()->get(ThemeModel::class);
    }

    /**
     * Test the we get consistent results when all themes are set to the same.
     */
    public function testGetCurrentAllSame() {
        $this->mockAddonProvider->pushAddon(new MockAddon(self::ADDON_THEME, [
            'Features' => [
                'SharedMasterView' => true,
            ]
        ]));
        $this->mockAddonProvider->pushAddon(new MockAddon(self::MOBILE_ADDON_THEME));

        $addonTheme = $this->mockThemeProvider->postTheme([
            'themeID' => self::ADDON_THEME,
            'assets' => [
                'styles' => new StyleAsset(self::ADDON_THEME),
            ]
        ]);

        $mobileTheme = $this->mockThemeProvider->postTheme([
            'themeID' => self::MOBILE_ADDON_THEME,
            'assets' => [
                'styles' => new StyleAsset(self::MOBILE_ADDON_THEME),
            ]
        ]);

        $assetTheme = $this->mockThemeProvider->postTheme([
            'themeID' => self::ASSET_THEME,
            'parentTheme' => self::ADDON_THEME,
            'assets' => [
                'styles' => new StyleAsset(self::ASSET_THEME),
            ]
        ]);

        $this->setConfigs([
            ThemeModelHelper::CONFIG_DESKTOP_THEME => self::ADDON_THEME,
            ThemeModelHelper::CONFIG_MOBILE_THEME => self::MOBILE_ADDON_THEME,
            ThemeModelHelper::CONFIG_CURRENT_THEME => self::ASSET_THEME,
        ]);

        $model = $this->themeModel();

        $this->assertEquals(self::ADDON_THEME, $model->getCurrentThemeAddon()->getKey());
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()['themeID']);
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()['assets']['styles']->getData());

        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(true, $features->useSharedMasterView());

        isMobile(true);

        // To the old system.
        $this->assertEquals(self::MOBILE_ADDON_THEME, $model->getCurrentThemeAddon()->getKey());

        // To the new system.
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()['themeID']);
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme()['assets']['styles']->getData());

        // We have overlayed the new assets on top.
        $this->assertEquals(self::ASSET_THEME, $model->getCurrentTheme(true)['assets']['styles']->getData());

        // Theme features from the mobile theme were properly preserved.
        /** @var ThemeFeatures $features */
        $features = self::container()->get(ThemeFeatures::class);
        $this->assertEquals(false, $features->useSharedMasterView());
    }
}
