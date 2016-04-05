<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class AddonManagerTest extends \PHPUnit_Framework_TestCase {

    private static $broadTypes = [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE];

    private static $cachePath;

    /**
     * Clear the cache before doing tests.
     */
    public static function setUpBeforeClass() {
        static::$cachePath = PATH_CACHE.'/addon-manager';
        \Gdn_FileSystem::removeFolder(static::$cachePath);
    }

    /**
     * Test basic addon scanning and caching.
     */
    public function testScanAndCache() {
        $manager = $this->createAddonManager();

        foreach (static::$broadTypes as $type) {
            $addons = $manager->scanAddons($type, true);
        }
    }

    /**
     * Test addon lookup when scanning isn't allowed.
     *
     * @depends testScanAndCache
     */
    public function testLookupCache() {
        // Create a manager that doesn't have the ability to scan.
        $manager = new AddonManager([], static::$cachePath);


        $coreAddonKeys = ['dashboard', 'Vanilla', 'facebook'];
        foreach ($coreAddonKeys as $addonKey) {
            $addon = $manager->lookupAddon($addonKey);
            $this->assertNotNull($addon);
            $this->assertInstanceOf('\\Vanilla\\Addon', $addon);
            $this->assertSame(strtolower($addonKey), strtolower($addon->getKey()));
            $this->assertTrue(in_array($addon->getType(), [Addon::TYPE_APPLICATION, Addon::TYPE_PLUGIN]));
        }

        $locale = $manager->lookupLocale('test-locale');
        $this->assertNotNull($locale);
        $this->assertInstanceOf('\\Vanilla\\Addon', $locale);
        $this->assertSame('test-locale', $locale->getKey());
        $this->assertSame(Addon::TYPE_LOCALE, $locale->getType());

        $theme = $manager->lookupTheme('2011Compatibility');
        $this->assertNotNull($theme);
        $this->assertInstanceOf('\\Vanilla\\Addon', $theme);
        $this->assertSame('2011Compatibility', $theme->getKey());
        $this->assertSame(Addon::TYPE_THEME, $theme->getType());
    }

    private function createAddonManager() {
        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/tests/Library/Vanilla/fixtures/locales'
            ],
            static::$cachePath
        );
        return $manager;
    }
}
