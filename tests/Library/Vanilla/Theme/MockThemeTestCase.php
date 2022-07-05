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
abstract class MockThemeTestCase extends MinimalContainerTestCase
{
    /** @var MockThemeProvider */
    protected $mockThemeProvider;

    /** @var MockAddonManager */
    protected $addonManager;

    /**
     * If someone wants to untangle this be my guest.
     *
     * @return bool
     */
    protected static function useCommonBootstrap(): bool
    {
        return false;
    }

    /**
     * Prepare the container.
     */
    public function configureContainer(): void
    {
        // Fresh container.
        parent::configureContainer();

        self::container()
            // Instances.
            ->rule(AddonManager::class)
            ->setShared(true)
            ->setClass(MockAddonManager::class)
            ->setInstance(\Gdn_Cache::class, new \Gdn_Dirtycache());

        $this->addonManager = self::container()->get(AddonManager::class);
        $this->mockThemeProvider = self::container()->get(MockThemeProvider::class);

        self::container()
            ->rule(ThemeService::class)
            ->addCall("addThemeProvider", [$this->mockThemeProvider]);
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
