<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla;

use Test\OldApplication\Controllers\Api\NewApiController;
use Test\OldApplication\Controllers\ArchiveController;
use Test\OldApplication\Controllers\HiddenController;
use Test\OldApplication\Controllers\OldApiController;
use Vanilla\AddonManager;
use Vanilla\Addon;
use VanillaTests\Fixtures\TestAddonManager;


class AddonManagerTest extends \PHPUnit_Framework_TestCase {

    private static $types = [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE];

    /**
     * Clear the cache before doing tests.
     */
    public static function setUpBeforeClass() {
        \Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache/am');
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
            PATH_ROOT.'/tests/cache/am/test-manager'
        );
        return $manager;
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
     * Creates an {@link AddonManager} against Vanilla.
     *
     * @return AddonManager Returns the manager.
     */
    private static function createVanillaManager($singleton = false) {
        static $instance;

        if ($singleton && $instance !== null) {
            return $instance;
        }
        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ['/applications', '/plugins'],
                Addon::TYPE_THEME => '/themes',
                Addon::TYPE_LOCALE => '/locales'
            ],
            PATH_ROOT.'/tests/cache/am/vanilla-manager'
        );
        if ($singleton) {
            $instance = $manager;
        }

        return $manager;
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
     * Test {@link AddonManager::isEnabled()}.
     */
    public function testIsEnabled() {
        $tm = $this->createTestManager();
        $addons = $tm->lookupAllByType(Addon::TYPE_ADDON);

        $this->assertNotEmpty($addons);
        /* @var Addon $addon */
        foreach ($addons as $addon) {
            $this->assertFalse($tm->isEnabled($addon->getKey(), $addon->getType()));
            $tm->startAddon($addon);
            $this->assertTrue($tm->isEnabled($addon->getKey(), $addon->getType()));
        }
    }

    /**
     * Test {@link AddonManager::lookupRequirements()}.
     */
    public function testLookupRequirements() {
        $tm = $this->createTestManager();

        // Test a requirement with transitive requirements.
        $addon = $tm->lookupTheme('test-old-theme');
        $reqs = $tm->lookupRequirements($addon);
        $this->assertArrayHasKey('test-old-plugin', $reqs);
        $this->assertArrayHasKey('test-old-application', $reqs);

        foreach ($reqs as $req) {
            $this->assertSame(AddonManager::REQ_DISABLED, $req['status']);
        }
    }

    /**
     * Test looking up requirements when one of the requirements is enabled.
     */
    public function testLookupRequirementsEnabledOne() {
        $tm = $this->createTestManager();

        $tm->startAddonsByKey(['test-old-application'], Addon::TYPE_ADDON);

        // Test a requirement with transitive requirements.
        $addon = $tm->lookupTheme('test-old-theme');
        $reqs = $tm->lookupRequirements($addon, AddonManager::REQ_DISABLED);
        $this->assertArrayHasKey('test-old-plugin', $reqs);
        $this->assertArrayNotHasKey('test-old-application', $reqs);
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
        $class = $addon->getPluginClass();

        // Themes do not have a class
        if (empty($class)) {
            return;
        }

        $classInfo = Addon::parseFullyQualifiedClass($class);
        $classKey = strtolower($classInfo['className']);

        $classes = $addon->getClasses();
        $this->assertArrayHasKey($classKey, $classes);

        $subpath = reset($classes[$classKey])['path'];
        // Kludge: Check for the UserPhoto() function.
        $fileContents = file_get_contents($addon->path($subpath));
        if (preg_match('`function userPhoto`i', $fileContents)) {
            $this->markTestSkipped("We can't test classes with redeclarations.");
            return;
        }

        require_once $addon->path($subpath);

        $this->assertTrue(class_exists($class, false), "The $class class is not in the $subpath file.");
        $this->assertTrue(is_a($class, '\Gdn_IPlugin', true), "The $class doesn't implement \Gdn_IPlugin.");
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
     * Test that addon directories with no addons works okay.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @dataProvider provideAddonTypes
     */
    public function testEmptyScans($type) {
        $em = static::createEmptyManager();

        $addons = $em->lookupAllByType($type);
        $this->assertTrue(is_array($addons));
        $this->assertEmpty($addons);

        $em2 = new AddonManager([], $em->getCacheDir());
        $addons2 = $em2->lookupAllByType($type);
        $this->assertTrue(is_array($addons2));
        $this->assertEmpty($addons2);
    }

    /**
     * Create an addon manager that won't have any addons.
     *
     * @return AddonManager Returns the empty addon manager.
     */
    private static function createEmptyManager() {
        $root = '/tests/fixtures';
        $em = new AddonManager(
            [
                Addon::TYPE_ADDON => "$root/empty",
                Addon::TYPE_THEME => "$root/empty",
                Addon::TYPE_LOCALE => "$root/empty"
            ],
            PATH_ROOT.'/tests/cache/am/empty-manager'
        );

        return $em;
    }

    /**
     * Test that addon directories with no addons works okay.
     *
     * @param string $type One of the **Addon::TYPE_*** constants.
     * @dataProvider provideAddonTypes
     */
    public function testNoScans($type) {
        $em = new AddonManager([], PATH_ROOT.'/tests/cache/am/no-scans');
        $addons = $em->lookupAllByType($type);
        $this->assertTrue(is_array($addons));
        $this->assertEmpty($addons);
    }

    /**
     * Test that {@link Gdn_PluginManager::calcOldInfoArray()} works.
     *
     * @param array $oldInfoArray The old info array.
     * @dataProvider provideVanillaPluginInfo
     */
    public function testCalcOldInfoArray(array $oldInfoArray) {
        $vm = self::createVanillaManager(true);
        $addon = $vm->lookupAddon($oldInfoArray['Index']);
        $this->assertNotNull($addon);
        $info = \Gdn_PluginManager::calcOldInfoArray($addon);
        $this->assertTrue(is_array($info));

        // Can't test requirements so just unset them.
        unset($info['Require'], $oldInfoArray['RequiredApplications'], $oldInfoArray['RequiredPlugins']);

        // Namespaced plugins were not supported.
        if (strpos($info['ClassName'], '\\') !== false) {
            unset($info['ClassName'], $oldInfoArray['ClassName']);
        }

        $this->assertArraySubsetRecursive($oldInfoArray, $info);
    }

    /**
     * Test that {@link Gdn_PluginManager::calcOldInfoArray()} works for themes.
     *
     * @param array $oldInfoArray The old info array.
     * @dataProvider provideVanillaThemeInfo
     */
    public function testCalcOldThemeInfoArray(array $oldInfoArray) {
        $vm = self::createVanillaManager(true);
        $addon = $vm->lookupTheme($oldInfoArray['Index']);
        $this->assertNotNull($addon);
        $info = \Gdn_PluginManager::calcOldInfoArray($addon);
        $this->assertTrue(is_array($info));

        // Can't test requirements so just unset them.
        unset($info['Require'], $oldInfoArray['RequiredApplications'], $oldInfoArray['RequiredPlugins']);

        $this->assertArraySubsetRecursive($oldInfoArray, $info);
    }

    /**
     * Create a Vanilla's plugin manager to compare functionality.
     *
     * @return \Gdn_PluginManager
     */
    private static function createPluginManager() {
        $pm = new \Gdn_PluginManager(static::createVanillaManager());
        return $pm;
    }

    /**
     * Provide all of plugin info currently in Vanilla.
     *
     * @return array Returns a data provider array.
     */
    public function provideVanillaPluginInfo() {
        $pm = static::createPluginManager();
        $infoArrays = [];
        $classInfo = [];
        $pm->indexSearchPath(PATH_PLUGINS, $infoArrays, $classInfo);

        return $this->makeProvider($infoArrays);
    }

    /**
     * Provide all of the theme info currently in Vanilla.
     *
     * @return array Returns a data provider array.
     */
    public function provideVanillaThemeInfo() {
        $tm = new \Gdn_ThemeManager(static::createVanillaManager(), false);
        $infoArrays = [];
        $tm->indexSearchPath(PATH_THEMES, $infoArrays);

        return $this->makeProvider($infoArrays);
    }

    /**
     * Assert that a deep array is a subset of another deep array.
     *
     * @param array $subset The subset to test.
     * @param array $array The array to test against.
     * @param bool $strict Whether or not to use strict comparison.
     * @param string $message A message to display on the test.
     */
    protected function assertArraySubsetRecursive($subset, $array, $strict = false, $message = '') {
        if (!is_array($subset)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(
                1,
                'array or ArrayAccess'
            );
        }

        if (!is_array($array)) {
            throw \PHPUnit_Util_InvalidArgumentHelper::factory(
                2,
                'array or ArrayAccess'
            );
        }

        $this->filterArraySubset($array, $subset);

        $strSubset = var_export($subset, true);
        $strArray = var_export($array, true);
        $this->assertSame($strArray, $strSubset, $message);
    }

    /**
     * Filter a parent array so that it doesn't include any keys that the child doesn't have.
     *
     * This also sorts the arrays by key so they can be compared.
     *
     * @param array &$parent The subset to filter.
     * @param array &$subset The parent array.
     */
    private function filterArraySubset(&$parent, &$subset) {
        $parent = array_intersect_key($parent, $subset);

        ksort($parent);
        ksort($subset);

        foreach ($parent as $key => &$value) {
            if (is_array($value) && isset($subset[$key]) && is_array($subset[$key])) {
                // Recurse into the array.
                $this->filterArraySubset($value, $subset[$key]);
            }
        }
    }

    /**
     * Wrap each element of an array in an array so that it can be used as a data provider.
     *
     * @param array $array The array to massage.
     * @return array
     */
    protected function makeProvider($array) {
        $result = array_map(function ($arr) {
            return [$arr];
        }, $array);
        return $result;
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

    /**
     * Provide the addon types for tests that rely on them.
     *
     * @return array Returns a data provider array.
     */
    public function provideAddonTypes() {
        $result = [];
        foreach (static::$types as $type) {
            $result[$type] = [$type];
        }
        return $result;
    }

    /**
     * Addons should be able to nest classes within specific directories.
     */
    public function testClassDirectoryRecursion() {
        $am = $this->createTestManager();

        $addon = $am->lookupByClassname(OldApiController::class, true);
        $this->assertNotNull($addon);
        $this->assertEquals('test-old-application', $addon->getKey());

        $addon = $am->lookupByClassname(NewApiController::class, true);
        $this->assertNotNull($addon);
        $this->assertEquals('test-old-application', $addon->getKey());
    }

    /**
     * Hidden files and directories should not be scanned.
     */
    public function testHiddenClassDirectories() {
        $am = $this->createTestManager();

        $addon = $am->lookupByClassname(ArchiveController::class);
        $this->assertNull($addon);

        $addon = $am->lookupByClassname(HiddenController::class);
        $this->assertNull($addon);
    }

    /**
     * Test {link AddonManager::matchClass()}.
     *
     * @param string $pattern The pattern to test.
     * @param string $class The class name to match.
     * @param bool $expected Whether the match should pass or fail.
     * @dataProvider provideFindPatternInClassCollectionTests
     */
    public function testFindPatternInClassCollection($pattern, $fqClassName, $expected) {
        $am = new TestAddonManager();

        $classInfo = Addon::parseFullyQualifiedClass($fqClassName);
        $results = $am->findPatternInClassCollection($pattern, [
            strtolower($classInfo['className']) => [
                $classInfo['namespace'] => $classInfo
            ]
        ]);

        return $expected === (count($results) === 1);
    }

    /**
     * Provide tests for {@link testFindPatternInClassCollection()}.
     *
     * @return array Returns a data provider array.
     */
    public function provideFindPatternInClassCollectionTests() {
        $data = [
            '*\DiscussionsController' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true,
                'VanillaAPI\DiscussionsController' => true,
            ],
            'discussionsController' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => false,
                'API\DiscussionsController' => false,
                'VanillaAPI\DiscussionsController' => false,
            ],
            '*\api\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => false,
                'VanillaAPI\DiscussionsController' => false,
            ],
            '*api\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true,
                'VanillaAPI\DiscussionsController' => true,
            ],
            'Vanilla\*\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => false,
                'VanillaAPI\DiscussionsController' => false,
            ],
            'Vanilla*\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => false,
                'VanillaAPI\DiscussionsController' => true,
            ],
            '*Controller' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true,
                'VanillaAPI\DiscussionsController' => true,
            ],
            '*\*Controller' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true,
                'VanillaAPI\DiscussionsController' => true,
            ],
            '\*Controller' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => false,
                'API\DiscussionsController' => false,
                'VanillaAPI\DiscussionsController' => false,
            ],
            '*' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true,
                'VanillaAPI\DiscussionsController' => true,
            ],
        ];

        $r = [];
        foreach ($data as $pattern => $rows) {
            foreach ($rows as $fqClassName => $expected) {
                $r["$pattern $fqClassName"] = [$pattern, $fqClassName, $expected];
            }
        }
        return $r;
    }

    /**
     * An addon manager that hasn't started any addons will not find any classes.
     */
    public function testFindClassesNone() {
        $am = new TestAddonManager();

        $classes = $am->findClasses('*');
        $this->assertEmpty($classes);
    }

    /**
     * Test finding classes when addons haven't had to start.
     */
    public function testFindClassesAll() {
        $am = new TestAddonManager();
        $classes = $am->findClasses('*', true);
        $this->assertNotEmpty($classes);
    }

    /**
     * Test finding classes on a started addon.
     */
    public function testFindClassesStarted() {
        $am = new TestAddonManager();

        $am->startAddonsByKey(['test-old-plugin'], Addon::TYPE_ADDON);
        $classes = $am->findClasses('TestOldPluginPlugin');
        $this->assertSame(\TestOldPluginPlugin::class, $classes[0]);
    }
}
