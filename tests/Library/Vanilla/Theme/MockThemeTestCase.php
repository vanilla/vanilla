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
abstract class MockThemeTestCase extends MinimalContainerTestCase {

    /** @var MockThemeProvider */
    protected $mockThemeProvider;

    /** @var MockAddonManager */
    protected $addonManager;

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
            ->setInstance(AddonManager::class, $this->addonManager)
        ;
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
    protected function themeService(): ThemeService {
        return self::container()->get(ThemeService::class);
    }
}
