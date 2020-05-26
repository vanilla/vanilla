<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Theme;

use Vanilla\Addon;
use Vanilla\Theme\ThemeService;
use VanillaTests\Fixtures\MockAddon;

/**
 * Tests for the theme cache.
 */
class ThemeCacheTest extends MockThemeTestCase {

    const MOCK_THEME_ID = 'mock-theme-id';

    /** @var ThemeService */
    private $service;

    /**
     * Configure the container.
     */
    public function setUp(): void {
        parent::setUp();

        self::container()->setInstance(\Gdn_Cache::class, new \Gdn_Dirtycache());
    }

    /**
     * Test simple caching.
     */
    public function testCacheSuccess() {
        $addon = new MockAddon(self::MOCK_THEME_ID);
        $this->addonManager->pushAddon($addon);

        $this->mockThemeProvider->addTheme([
            'themeID' => self::MOCK_THEME_ID,
        ], $addon);

        $this->service = $service = $this->themeService();
        $theme = $service->getTheme(self::MOCK_THEME_ID);
        $this->assertEquals(false, $theme->isCacheHit());

        // Get it again.
        $theme = $service->getTheme(self::MOCK_THEME_ID);
        $this->assertEquals(true, $theme->isCacheHit());

        // Make sure things got deserialize properly.
        $this->assertInstanceOf(Addon::class, $theme->getAddon());
    }

    /**
     * Test simple caching.
     */
    public function testCacheInvalidate() {
        $addon = new MockAddon(self::MOCK_THEME_ID);
        $this->addonManager->pushAddon($addon);

        $this->mockThemeProvider->addTheme([
            'themeID' => self::MOCK_THEME_ID,
            'assets' => [
                'variables' => [
                    'type' => 'json',
                    'data' => '{}',
                ],
            ],
        ], $addon);

        $this->service = $this->themeService();

        $this->assertInvalidatesCache(function () {
            $this->service->patchTheme(self::MOCK_THEME_ID, []);
        });

        $this->assertInvalidatesCache(function () {
            $this->service->postTheme([]);
        });

        $this->assertInvalidatesCache(function () {
            $this->service->setAsset(self::MOCK_THEME_ID, 'variables', '{ "hello": "world"}');
        });

        $this->assertInvalidatesCache(function () {
            $this->service->sparseUpdateAsset(self::MOCK_THEME_ID, 'variables', '{ "hello": "world"}');
        });

        $this->assertInvalidatesCache(function () {
            $this->service->deleteAsset(self::MOCK_THEME_ID, 'variables');
        });

        $newTheme = $this->mockThemeProvider->addTheme(['themeID' => 'mock-new-theme'], $addon);
        $this->assertInvalidatesCache(function () use ($newTheme) {
            $this->service->deleteTheme($newTheme->getThemeID());
        });
    }

    /**
     * Test that some action invalidates the theme cache.
     * @param callable $action
     */
    private function assertInvalidatesCache(callable $action) {
        // Prime the cache.
        $theme = $this->service->getTheme(self::MOCK_THEME_ID);
        // Fetch again make sure we're cached.
        $theme = $this->service->getTheme(self::MOCK_THEME_ID);
        $this->assertEquals(true, $theme->isCacheHit());
        $action();
        $theme = $this->service->getTheme(self::MOCK_THEME_ID);
        $this->assertEquals(false, $theme->isCacheHit());
    }
}
