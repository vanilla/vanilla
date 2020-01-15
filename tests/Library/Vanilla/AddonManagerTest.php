<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use Test\OldApplication\Controllers\Api\NewApiController;
use Test\OldApplication\Controllers\ArchiveController;
use Test\OldApplication\Controllers\HiddenController;
use Test\OldApplication\Controllers\OldApiController;
use Test\OldApplication\ArbitraryUppercase;
use Test\OldApplication\arbitraryLowercase;
use Vanilla\AddonManager;
use Vanilla\Addon;
use VanillaTests\Fixtures\TestAddonManager;
use VanillaTests\SharedBootstrapTestCase;

/**
 * Tests for the AddonManager
 */
class AddonManagerTest extends SharedBootstrapTestCase {

    const FIXTURE_ROOT = '/tests/fixtures';

    private static $types = [Addon::TYPE_ADDON, Addon::TYPE_THEME, Addon::TYPE_LOCALE];

    /**
     * Clear the cache before doing tests.
     */
    public static function setUpBeforeClass(): void {
        \Gdn_FileSystem::removeFolder(PATH_ROOT.'/tests/cache/am');
        parent::setUpBeforeClass();
    }

    /**
     * Test basic addon scanning and caching.
     */
    public function testScanAndCache() {
        $manager = $this->createTestManager();

        foreach (static::$types as $type) {
            $manager->scan($type, true);
        }

        // No exception so we are cool!
        $this->assertTrue(true);
    }

    /**
     * Create an {@link AddonManager} against the test fixtures.
     *
     * @return AddonManager Returns the manager.
     */
    private static function createTestManager() {
        $root = self::FIXTURE_ROOT;

        $manager = new AddonManager(
            [
                Addon::TYPE_ADDON => ["$root/addons/addons", "$root/applications", "$root/plugins"],
                Addon::TYPE_THEME => ["$root/addons/themes", "$root/themes"],
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

        // No exception so we are cool!
        $this->assertTrue(true);
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
     *
     * @param string $addonKey
     * @param string $addonType
     * @param string $addonSubDir
     *
     * @dataProvider provideAddonExistsTest
     */
    public function testAddonExists(string $addonKey, string $addonType, string $addonSubDir = '') {
        $tm = $this->createTestManager();

        $addon = $tm->lookupByType($addonKey, $addonType);
        $this->assertNotNull($addon);
        $this->assertInstanceOf('\Vanilla\Addon', $addon);
        $this->assertNotEmpty($addon->getPluginClass());

        if ($addonSubDir) {
            $this->assertEquals($addonSubDir, $addon->getSubdir());
        }
    }

    /**
     * @return array
     */
    public function provideAddonExistsTest(): array {
        return [
            'test-old-application' => [
                'test-old-application',
                Addon::TYPE_ADDON,
            ],
            'test-old-plugin' => [
                'test-old-plugin',
                Addon::TYPE_ADDON,
            ],
            'test-old' => [
                'test-old',
                Addon::TYPE_THEME,
            ],
            'theme-in-addons' => [
                'theme-in-addons',
                Addon::TYPE_THEME,
                self::FIXTURE_ROOT.'/addons/themes/theme-in-addons',
            ],
            'plugin-in-addons' => [
                'plugin-in-addons',
                ADDON::TYPE_ADDON,
                self::FIXTURE_ROOT.'/addons/addons/plugin-in-addons',
            ],
        ];
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
        $addon = $tm->lookupTheme('test-old');
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
        $addon = $tm->lookupTheme('test-old');
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
            $this->assertTrue(true);
            return;
        }

        $classInfo = Addon::parseFullyQualifiedClass($class);
        $classKey = strtolower($classInfo['className']);

        $classes = $addon->getClasses();
        $this->assertArrayHasKey($classKey, $classes);

        $subpath = reset($classes[$classKey])['path'];
        // Kludge: Check for the userPhoto() function.
        $path = $addon->path($subpath);
        $fileContents = file_get_contents($path);
        if (preg_match('`function userPhoto`i', $fileContents)) {
            $this->markTestIncomplete("We can't test classes that redeclare userPhoto(). $path");
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

        $locale = $manager->lookupLocale('test');
        $this->assertNotEmpty($locale);
        $this->assertTrue($locale instanceof Addon);
        $this->assertSame('test', $locale->getKey());
        $this->assertSame(Addon::TYPE_LOCALE, $locale->getType());

        $theme = $manager->lookupTheme('test-old');
        $this->assertNotEmpty($theme);
        $this->assertTrue($theme instanceof Addon);
        $this->assertSame('test-old', $theme->getKey());
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
     *
     * @param string $lookupClassName The classname to look for.
     * @param string|null $addonKeyFound The addon key you expect the class to be assosciated with. Null if it shouldn't be found.
     *
     * @dataProvider provideClassLookups
     */
    public function testClassDirectoryRecursion(string $lookupClassName, ?string $addonKeyFound) {
        $am = $this->createTestManager();

        $addon = $am->lookupByClassname($lookupClassName, true);
        if ($addonKeyFound) {
            $this->assertNotNull($addon);
            $this->assertEquals($addonKeyFound, $addon->getKey());
        } else {
            $this->assertNull($addon);
        }
    }

    /**
     * Provide data for testClassDirectoryRecursion.
     */
    public function provideClassLookups(): array {
        $oldApp = 'test-old-application';
        return [
            'Old Api Controller' => [ OldApiController::class, $oldApp],
            'APIv2 Controller' => [ NewApiController::class, $oldApp],
            'Nested Namespace uppercase' => [ ArbitraryUppercase\CustomDirNamespaceClass::class, $oldApp ],
            'Nested Namespace lowercase' => [ arbitraryLowercase\CustomDirNamespaceClass::class, null ],
        ];
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
     * @dataProvider provideMatchClassTests
     */
    public function testMatchClass($pattern, $class, $expected) {
        $am = new TestAddonManager();

        $r = $am->matchClass($pattern, $class);
        $this->assertSame($expected, $r);
    }

    /**
     * Provide tests for {@link testMatchClass()}.
     *
     * @return array Returns a data provider array.
     */
    public function provideMatchClassTests() {
        $data = [
            '*\DiscussionsController' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true
            ],
            'discussionsController' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => false,
                'API\DiscussionsController' => false
            ],
            '*\api\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => false,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true
            ],
            'Vanilla\*\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => false
            ],
            'vanilla\*\DiscussionsController' => [
                'DiscussionsController' => false,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => false
            ],
            '*\*Controller' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true
            ],
            '*Controller' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true
            ],
            '*' => [
                'DiscussionsController' => true,
                'Vanilla\DiscussionsController' => true,
                'Vanilla\API\DiscussionsController' => true,
                'API\DiscussionsController' => true
            ],
        ];

        $r = [];
        foreach ($data as $pattern => $rows) {
            foreach ($rows as $class => $expected) {
                $r["$pattern $class"] = [$pattern, $class, $expected];
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
    public function testFindClassesNamespaceCaseMismatch() {
        $am = new TestAddonManager();

        $am->startAddonsByKey(['namespaced-plugin'], Addon::TYPE_ADDON);
        $classes = $am->findClasses('deeply\\NESTed\\NamesPaced\\Fixture\\TestClass');
        $this->assertSame(\Deeply\Nested\Namespaced\Fixture\TestClass::class, $classes[0]);
    }

    /**
     * Test a findClass with a namespace case mismatch
     */
    public function testFindClassNamespaceCaseMismatch() {
        $am = new TestAddonManager();

        $am->startAddonsByKey(['namespaced-plugin'], Addon::TYPE_ADDON);

        $addon = $am->lookupByClassname('deeply\\NESTed\\NamesPaced\\Fixture\\TestClass');
        $this->assertEquals('namespaced-plugin', $addon->getKey());
    }

    /**
     * Test a lookup with a namespace case mismatch
     */
    public function testLookupNamespaceCaseMismatch() {
        $am = new TestAddonManager();

        $am->startAddonsByKey(['namespaced-plugin'], Addon::TYPE_ADDON);

        $addon = $am->lookupByClassname('deeply\\NESTed\\NamesPaced\\Fixture\\TestClass');
        $this->assertEquals('namespaced-plugin', $addon->getKey());
    }

    /**
     * Looking up an addon by class name should give the right addon, even if there is another addon that has a class with the same basename.
     */
    public function testSameBasenameEdgeCase() {
        $am = new TestAddonManager();

        $am->startAddonsByKey(['multiclass-namespaced-plugin', 'namespaced-plugin'], Addon::TYPE_ADDON);

        $addon1 = $am->lookupByClassname(\Deeply\TestClass::class);
        $this->assertEquals('multiclass-namespaced-plugin', $addon1->getKey());

        $addon2 = $am->lookupByClassname(\Deeply\Nested\Namespaced\Fixture\TestClass::class);
        $this->assertEquals('namespaced-plugin', $addon2->getKey());
    }

    /**
     * Test looking up requirements that are not met.
     *
     * This test mimics the test from **AddonManager::checkRequirements()**.
     */
    public function testBadRequire() {
        $am = new TestAddonManager();

        $addon = $am->lookupAddon('bad-require');
        $r = $am->lookupRequirements($addon, AddonManager::REQ_MISSING | AddonManager::REQ_VERSION);

        $this->assertArrayHasKey('asd', $r);
        $this->assertArrayHasKey('namespaced-plugin', $r);

        $this->assertSame(AddonManager::REQ_MISSING, $r['asd']['status']);
        $this->assertSame(AddonManager::REQ_VERSION, $r['namespaced-plugin']['status']);
    }

    /**
     * Test an addon with an invalid require key.
     */
    public function testInvalidRequire() {
        $addon = Addon::__set_state(['info' => ['require' => []]]);
        $issues = $addon->check();
        $this->assertArrayNotHasKey('invalid-require', $issues);

        $addon = Addon::__set_state(['info' => ['require' => 'foo']]);
        $issues = $addon->check();
        $this->assertArrayHasKey('invalid-require', $issues);

        $this->assertEquals([], $addon->getRequirements());
    }

    /**
     * Test an addon with an invalid conflict key.
     */
    public function testInvalidConflict() {
        $addon = Addon::__set_state(['info' => ['conflict' => []]]);
        $issues = $addon->check();
        $this->assertArrayNotHasKey('invalid-conflict', $issues);

        $addon = Addon::__set_state(['info' => ['conflict' => 'foo']]);
        $issues = $addon->check();
        $this->assertArrayHasKey('invalid-conflict', $issues);

        $this->assertEquals([], $addon->getConflicts());
    }

    /**
     * Test **AddonManager::lookupConflicts()**.
     */
    public function testLookupConflicts() {
        $am = $this->makeConflictedAddonManager();
        $am->startAddon($am->lookupAddon('grand-parent'));

        $parent = $am->lookupAddon('parent');
        $parentConflicts = $am->lookupConflicts($parent);
        $this->assertArrayHasKey('grand-parent', $parentConflicts);

        $child = $am->lookupAddon('child');
        $childConflicts = $am->lookupConflicts($child);
        $this->assertArrayHasKey('grand-parent', $childConflicts);
    }

    /**
     * An addon should list enabled addons that conflict with it even if it doesn't list the conflict itself.
     */
    public function testLookupConflicts2() {
        $am = $this->makeConflictedAddonManager();
        $am->startAddon($am->lookupAddon('child'));

        $gp = $am->lookupAddon('grand-parent');
        $gpConflicts = $am->lookupConflicts($gp);
        $this->assertArrayHasKey('child', $gpConflicts);
    }

    /**
     * Test **AddonManager::checkConflicts()**.
     */
    public function testCheckConflicts() {
        $this->expectException(\Exception::class);
        $this->expectExceptionCode(409);
        $this->expectExceptionMessage('Parent conflicts with: Grandparent.');

        $am = $this->makeConflictedAddonManager();
        $am->startAddon($am->lookupAddon('grand-parent'));

        $parent = $am->lookupAddon('parent');
        $this->assertFalse($am->checkConflicts($parent, false));

        $am->checkConflicts($parent, true);
    }

    public function testLookupNonExistant() {
        $am = $this->createTestManager();

        $this->assertNull($am->lookupTheme('asdf'));
        $this->assertNull($am->lookupByType('asdf', Addon::TYPE_THEME));
        $this->assertNull($am->lookupByType('', Addon::TYPE_THEME));
    }

    /**
     * Test **AddonManager::getAddonInfoValue()**.
     */
    public function testGetAddonInfoValue() {
        $am = $this->createTestManager();

        $basic = $am->lookupTheme('basic');


        $this->assertEquals('a', $am->getAddonInfoValue($basic, 'a'));
        $this->assertEquals('b', $am->getAddonInfoValue($basic, 'b'));
        $this->assertEquals('no', $am->getAddonInfoValue($basic, 'c', 'no'));
    }

    /**
     * Test **AddonManager::lookup()**.
     */
    public function testGlobalLookup() {
        $am = $this->createTestManager();

        $addon = $am->lookup('test-plugin');
        $this->assertEquals('test-plugin', $addon->getKey());
        $this->assertEquals(Addon::TYPE_ADDON, $addon->getType());

        $theme = $am->lookup('basic-theme');
        $this->assertEquals('basic', $theme->getKey());
        $this->assertEquals(Addon::TYPE_THEME, $theme->getType());

        $locale = $am->lookup('test-locale');
        $this->assertEquals('test', $locale->getKey());
        $this->assertEquals(Addon::TYPE_LOCALE, $locale->getType());
    }

    /**
     * Test addon type checking.
     */
    public function testBadType() {
        $this->expectException(\InvalidArgumentException::class);
        $am = $this->createTestManager();

        $addons = $am->lookupAllByType('../../../fixtures/error');
    }

    /**
     * Test a bad theme key.
     */
    public function testBadThemeKey() {
        $this->expectNotice();

        $am = $this->createTestManager();

        $theme = $am->lookupTheme('../../../../fixtures/error-index');
    }

    /**
     * Looking up an empty addon key should return null, no error.
     */
    public function testEmptyKeyLookup() {
        $am = $this->createTestManager();

        $addon = $am->lookupAddon('');
        $this->assertNull($addon);
        $addon = $am->lookupTheme('');
        $this->assertNull($addon);
        $addon = $am->lookupLocale('');
        $this->assertNull($addon);
    }

    /**
     * Add-ons with bad keys should not be indexed.
     *
     * @param string $type
     * @dataProvider provideBadAddonKeyTypes
     */
    public function testBadAddonKeyScan($type) {
        $err = error_reporting(E_ALL & ~E_USER_NOTICE & ~E_USER_WARNING);

        try {
            $am = new AddonManager(
                [
                    Addon::TYPE_ADDON => "/tests/fixtures/bad-addons",
                    Addon::TYPE_THEME => "/tests/fixtures/bad-themes",
                ],
                PATH_ROOT.'/tests/cache/am/bad-manager'
            );

            $addons = $am->lookupAllByType($type);
            $this->assertEmpty($addons);
        } finally {
            error_reporting($err);
        }
    }

    /**
     * Provide data for `testBadAddonKeyScan`.
     *
     * @return array Returns a data provider.
     */
    public function provideBadAddonKeyTypes() {
        return [
            Addon::TYPE_ADDON => [Addon::TYPE_ADDON],
            Addon::TYPE_THEME => [Addon::TYPE_THEME],
        ];
    }

    /**
     * Make an addon manager that has conflicting addons..
     *
     * @return AddonManager
     */
    private function makeConflictedAddonManager() {
        $am = new AddonManager([], PATH_ROOT.'/tests/cache/cam');

        $am->add(Addon::__set_state(['info' => [
            'key' => 'grand-parent',
            'name' => 'Grandparent',
            'type' => Addon::TYPE_ADDON
        ]]), false);

        $am->add(Addon::__set_state(['info' => [
            'key' => 'parent',
            'name' => 'Parent',
            'type' => Addon::TYPE_ADDON,
            'require' => [
                'child' => '1'
            ]
        ]]), false);

        $am->add(Addon::__set_state(['info' => [
            'key' => 'child',
            'name' => 'Child',
            'type' => Addon::TYPE_ADDON,
            'version' => '1',
            'conflict' => [
                'grand-parent' => '*'
            ]
        ]]), false);

        return $am;
    }
}
