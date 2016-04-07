<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class AddonManagerTest extends \PHPUnit_Framework_TestCase {

    private static $broadTypes = [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE];

    /**
     * Clear the cache before doing tests.
     */
    public static function setUpBeforeClass() {
        $tm = static::createTestManager();
        $r = $tm->clearCache();
        if (!$r) {
            throw new \Exception("Could not clear the test manager cache.");
        }

        $vm = static::createVanillaManager();
        $r = $vm->clearCache();
        if (!$r) {
            throw new \Exception("Could not clear the vanilla manager cache.");
        }
    }

    /**
     * Test basic addon scanning and caching.
     */
    public function testScanAndCache() {
        $manager = $this->createTestManager();

        foreach (static::$broadTypes as $type) {
            $addons = $manager->scan($type, true);
        }
    }

    /**
     * Test addon lookup when scanning isn't allowed.
     *
     * @depends testScanAndCache
     */
    public function testLookupCache() {
        $managerBase = static::createTestManager();

        // Create a manager that doesn't have the ability to scan.
        $manager = new AddonManager([], $managerBase->getCacheDir());

        $coreAddonKeys = ['test-old-application', 'test-old-plugin'];
        foreach ($coreAddonKeys as $addonKey) {
            $addon = $manager->lookupAddon($addonKey);
            $this->assertNotNull($addon);
            $this->assertInstanceOf('\\Vanilla\\Addon', $addon);
            $this->assertSame(strtolower($addonKey), strtolower($addon->getKey()));
            $this->assertTrue(in_array($addon->getType(), [Addon::TYPE_ADDON]));
        }

        $locale = $manager->lookupLocale('test-locale');
        $this->assertNotNull($locale);
        $this->assertInstanceOf('\\Vanilla\\Addon', $locale);
        $this->assertSame('test-locale', $locale->getKey());
        $this->assertSame(Addon::TYPE_LOCALE, $locale->getType());

        $theme = $manager->lookupTheme('test-old-theme');
        $this->assertNotNull($theme);
        $this->assertInstanceOf('\\Vanilla\\Addon', $theme);
        $this->assertSame('test-old-theme', $theme->getKey());
        $this->assertSame(Addon::TYPE_THEME, $theme->getType());
    }

    private static function createVanillaManager() {
        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/locales'
            ],
            PATH_CACHE.'/vanilla-manager'
        );
        return $manager;
    }

    private static function createTestManager() {
        $root = '/tests/Library/Vanilla/fixtures';

        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ["$root/addons", "$root/applications", "$root/plugins"],
                Addon::TYPE_THEME => "$root/themes",
                Addon::TYPE_LOCALE => "$root/locales"
            ],
            PATH_CACHE.'/test-manager'
        );
        return $manager;
    }
}
