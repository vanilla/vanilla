<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla;

use VanillaTests\Fixtures\AliasAutoloader;
use VanillaTests\Fixtures\Aliases\ExtendsNewClass;
use VanillaTests\Fixtures\Aliases\NewClass;
use VanillaTests\Fixtures\Aliases\NewClassFromNamespace;
use VanillaTests\Fixtures\Aliases\NewClassWithContainer;
use VanillaTests\SharedBootstrapTestCase;

class AliasAutoloaderTest extends SharedBootstrapTestCase {
    protected $runTestInSeparateProcess = true;

    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        error_reporting(E_ALL ^ E_USER_DEPRECATED);
        spl_autoload_register([AliasAutoloader::class, 'autoload']);
    }

    /**
     * Verify that loading the old class loads the new class through the autoloader.
     */
    public function testSimpleAliasAutoload() {
        // Standard auto-loading behaviour.
        $this->assertClassNotLoaded(NewClass::class);
        new \OldClass();
        $this->assertClassLoaded(NewClass::CLASS_ALIAS);
        $this->assertClassLoaded(NewClass::class);
    }

    public function testNewExtendsNew() {
        $this->assertClassExtendsClass(ExtendsNewClass::class, NewClass::class);
    }

    public function testOldExtendsNew() {
        $this->assertClassExtendsClass(ExtendsNewClass::CLASS_ALIAS, NewClass::class);
    }

    public function testNewExtendsOld() {
        $this->assertClassExtendsClass(ExtendsNewClass::class, NewClass::CLASS_ALIAS);
    }

    public function testOldExtendsOld() {
        $this->assertClassExtendsClass(ExtendsNewClass::CLASS_ALIAS, NewClass::CLASS_ALIAS);
    }

    private function assertClassExtendsClass(string $childClass, string $parentClass) {
        $child = new $childClass();
        $this->assertTrue(is_subclass_of($child, $parentClass));
    }

    private function assertClassLoaded(string $className) {
        $this->assertTrue(class_exists($className, false));
    }

    private function assertClassNotLoaded(string $className) {
        $this->assertTrue(class_exists($className, false) === false);
    }
}
