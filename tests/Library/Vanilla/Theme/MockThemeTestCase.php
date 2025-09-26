<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\AddonManager;
use Vanilla\Theme\Asset\CssThemeAsset;
use Vanilla\Theme\Theme;
use Vanilla\Theme\ThemeAssetFactory;
use Vanilla\Theme\ThemeService;
use Vanilla\Theme\ThemeServiceHelper;
use Vanilla\Theme\ThemeFeatures;
use VanillaTests\Fixtures\MockAddon;
use VanillaTests\Fixtures\MockAddonManager;
use VanillaTests\Fixtures\Theme\MockThemeProvider;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\SiteTestCase;
use VanillaTests\SiteTestTrait;

/**
 * Tests for getting the current theme from the theme model.
 */
abstract class MockThemeTestCase extends SiteTestCase
{
    /** @var MockThemeProvider */
    protected $mockThemeProvider;

    /** @var MockAddonManager */
    protected $addonManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->addonManager = new MockAddonManager();

        self::container()
            ->setInstance(AddonManager::class, $this->addonManager)
            ->setInstance(\Gdn_Cache::class, new \Gdn_Dirtycache());

        self::container()->get(MockThemeProvider::class);
        $this->mockThemeProvider = self::container()->get(MockThemeProvider::class);

        $themeService = self::container()->get(ThemeService::class);
        $themeService->clearThemeProviders();
        $themeService->addThemeProvider($this->mockThemeProvider);
    }

    /**
     * @inheritdoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
        isMobile(null);
    }

    /**
     * @return ThemeService
     */
    protected function themeService(): ThemeService
    {
        return self::container()->get(ThemeService::class);
    }
}
