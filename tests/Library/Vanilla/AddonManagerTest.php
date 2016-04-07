<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Vanilla;


class AddonManagerTest extends \PHPUnit_Framework_TestCase {

    private static $types = [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE];

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

        foreach (static::$types as $type) {
            $addons = $manager->scan($type, true);
        }
    }

    /**
     * Test basic addon scanning of all currently linked application addons.
     */
    public function testVanillaAddonsScanning() {
        $manager = $this->createVanillaManager();

        foreach (static::$types as $type) {
            $addons = $manager->scan($type, true);
        }
    }

    /**
     * Test some addons where we know that plugins exist.
     */
    public function testPluginExists() {
        $tm = $this->createTestManager();

        $keys = [
            'test-old-application' => Addon::TYPE_ADDON,
            'test-old-plugin' => Addon::TYPE_ADDON,
            'test-old-theme' => Addon::TYPE_THEME
        ];

        foreach ($keys as $key => $type) {
            $addon = $tm->lookupByType($key, $type);
            $this->assertNotNull($addon);
            $this->assertInstanceOf('\Vanilla\Addon', $addon);
            $this->assertNotEmpty($addon->getPluginClass());
        }
    }

    /**
     * Test that all reported plugins implement Gdn_IPlugin.
     *
     * The new addon manager just looks at class name so this test just makes sure we stick to our convention.
     *
     * @param Addon $addon The addon to test.
     * @dataProvider provideVanillaAddons
     */
    public function testVanillaPluginAndHookDefinition(Addon $addon) {
        $className = $addon->getPluginClass();
        $classKey = strtolower($className);
        if (empty($classKey)) {
            return;
        }
        $classes = $addon->getClasses();
        $this->assertArrayHasKey($classKey, $classes);
        $subpath = $classes[$classKey][1];

        // Kludge: Check for the UserPhoto() function.
        $fileContents = file_get_contents($addon->path($subpath));
        if (preg_match('`function userPhoto`i', $fileContents)) {
            $this->markTestSkipped("We can't test classes with redeclarations.");
            return;
        }

        require_once $addon->path($subpath);

        $this->assertTrue(class_exists($className, false), "The $className class is not in the $subpath file.");
        $this->assertTrue(is_a($className, '\Gdn_IPlugin', true), "The $className doesn't implement \Gdn_IPlugin.");
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
        $this->assertNotEmpty($locale);
        $this->assertTrue($locale instanceof Addon);
        $this->assertSame('test-locale', $locale->getKey());
        $this->assertSame(Addon::TYPE_LOCALE, $locale->getType());

        $theme = $manager->lookupTheme('test-old-theme');
        $this->assertNotEmpty($theme);
        $this->assertTrue($theme instanceof Addon);
        $this->assertSame('test-old-theme', $theme->getKey());
        $this->assertSame(Addon::TYPE_THEME, $theme->getType());
    }

    /**
     * Creates an {@link AddonManager} against Vanilla.
     *
     * @return AddonManager Returns the manager.
     */
    private static function createVanillaManager() {
        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/locales'
            ],
            PATH_ROOT.'/tests/cache/vanilla-manager'
        );
        return $manager;
    }

    /**
     * Create an {@link AddonManager} against the test fixtures.
     *
     * @return AddonManager Returns the manager.
     */
    private static function createTestManager() {
        $root = '/tests/fixtures';

        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ["$root/addons", "$root/applications", "$root/plugins"],
                Addon::TYPE_THEME => "$root/themes",
                Addon::TYPE_LOCALE => "$root/locales"
            ],
            PATH_ROOT.'/tests/cache/test-manager'
        );
        return $manager;
    }

    /**
     * Provide all of the addons of belonging to given types.
     *
     * @return array Returns an array of addon function args.
     */
    public function provideVanillaAddons() {
        $types = [Addon::TYPE_ADDON, Addon::TYPE_LOCALE, Addon::TYPE_THEME];
        $manager = $this->createVanillaManager();
        $result = [];
        foreach ($types as $type) {
            $addons = $manager->lookupAllByType($type);
            foreach ($addons as $addon) {
                /* @var Addon $addon */
                $result[$addon->getSubdir()] = [$addon];
            }
        }
        return $result;
    }
}
