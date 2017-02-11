<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Garden;

use Garden\ClassLocator;

class ClassLocatorTest extends \PHPUnit_Framework_TestCase {

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
