<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden;

use VanillaTests\SharedBootstrapTestCase;
use Garden\ClassLocator;

class ClassLocatorTest extends SharedBootstrapTestCase {

    public function testFindClass() {
        $classLocator = new ClassLocator();
        $validClass = ClassLocator::class;

        $this->assertEquals($validClass, $classLocator->findClass($validClass));

        $this->assertNull($classLocator->findClass('Invalid\\Class'));
    }

    public function testFindMethod() {
        $classLocator = new ClassLocator();

        $this->assertTrue(is_callable($classLocator->findMethod($classLocator, 'findmethod')));

        $this->assertNull($classLocator->findMethod($classLocator, 'invalidMethod'));

    }
}
