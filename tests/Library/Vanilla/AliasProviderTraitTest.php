<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use VanillaTests\Fixtures\Aliases\ExtendsNewClass;
use VanillaTests\Fixtures\Aliases\NewClass;
use VanillaTests\Fixtures\Aliases\TestAliasLoader;
use VanillaTests\IsolatedTestCase;

/**
 * Tests for the alias provider trait.
 */
class AliasProviderTraitTest extends IsolatedTestCase {
    /**
     * Prepare each instance of the test.
     *
     * - Disable deprecated notices for the process.
     * - Register the autoloader for our process.
     * - Verify that the we are starting with a fresh set of classes. Eg. The classes we're testing have not been
     * autoloaded yet. $runTestInSeparateProcess should handle this.
     */
    public static function setUpBeforeClass(): void {

        // Our alias autoloader throws deprecated notices. Disable the error reporting for these tests.
        // Since this test runs in a separate process it shouldn't affect the rest of the tests.
        error_reporting(E_ALL ^ E_USER_DEPRECATED);
        spl_autoload_register([TestAliasLoader::class, 'autoload']);
        self::assertFreshAutoload(NewClass::class);
        self::assertFreshAutoload(ExtendsNewClass::class);
    }

    /**
     * Verify that loading the old class loads the new class through the autoloader.
     */
    public function testSimpleAliasAutoload() {
        new \VanillaTests\OldClass();
        $this->assertCompleteAutoload(NewClass::class);
    }

    /**
     * Tests for various permutations of old classname + new classnames with inheritance.
     */
    public function testNewExtendsNew() {
        $this->assertClassExtendsClass(ExtendsNewClass::class, NewClass::class);
        $this->assertCompleteAutoload(NewClass::class);
        $this->assertCompleteAutoload(ExtendsNewClass::class);
    }

    /**
     * Tests for various permutations of old classname + new classnames with inheritance.
     */
    public function testOldExtendsNew() {
        $this->assertClassExtendsClass(\VanillaTests\ExtendsOldClass::class, NewClass::class);
        $this->assertCompleteAutoload(NewClass::class);
        $this->assertCompleteAutoload(ExtendsNewClass::class);
    }

    /**
     * Tests for various permutations of old classname + new classnames with inheritance.
     */
    public function testNewExtendsOld() {
        $this->assertClassExtendsClass(ExtendsNewClass::class, \VanillaTests\OldClass::class);
        $this->assertCompleteAutoload(NewClass::class);
        $this->assertCompleteAutoload(ExtendsNewClass::class);
    }

    /**
     * Tests for various permutations of old classname + new classnames with inheritance.
     */
    public function testOldExtendsOld() {
        $this->assertClassExtendsClass(\VanillaTests\ExtendsOldClass::class, \VanillaTests\OldClass::class);
        $this->assertCompleteAutoload(NewClass::class);
        $this->assertCompleteAutoload(ExtendsNewClass::class);
    }

    /**
     * Assert that one class is a subclass of another.
     *
     * @param string $childClass
     * @param string $parentClass
     */
    private static function assertClassExtendsClass(string $childClass, string $parentClass) {
        $child = new $childClass();
        self::assertTrue(is_subclass_of($child, $parentClass));
    }

    /**
     * Assert that a class has been loaded.
     *
     * @param string $className
     */
    private static function assertClassLoaded(string $className) {
        self::assertTrue(class_exists($className, false));
    }

    /**
     * Assert that a class has not been loaded.
     *
     * This is a good sanity check to ensure each test starts with a fresh set of classes that haven't been loaded yet.
     *
     * @param string $className
     */
    private static function assertClassNotLoaded(string $className) {
        self::assertTrue(class_exists($className, false) === false);
    }

    /**
     * Assert that no aliases are loaded yet for a given class.
     *
     * This is a good sanity check to ensure each test starts with a fresh set of classes that haven't been loaded yet.
     *
     * @param string $className
     */
    private static function assertFreshAutoload(string $className) {
        self::assertClassNotLoaded($className);

        foreach (TestAliasLoader::getAliases($className) as $alias) {
            self::assertClassNotLoaded($alias);
        }
    }

    /**
     * Assert that a class and its aliases are loaded.
     *
     * @param string $className
     */
    private static function assertCompleteAutoload(string $className) {
        self::assertClassLoaded($className);
        foreach (TestAliasLoader::getAliases($className) as $alias) {
            self::assertClassLoaded($alias);
        }
    }
}
