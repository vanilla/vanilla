<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Core;
use VanillaTests\Fixtures\BaseClass;
use VanillaTests\Fixtures\BaseClassDecorator;
use VanillaTests\Fixtures\CountCallDecorator;

/**
 * Test AbstractDecorator implementations.
 */
class AbstractDecoratorTest extends \PHPUnit\Framework\TestCase {

    /**
     * Base decorator test.
     */
    public function testDecorated() {
        $baseClass = new BaseClass();
        $baseClassDecorator = new BaseClassDecorator($baseClass);
        $this->assertSame($baseClassDecorator->foobar(), 'Decorated '.$baseClass->foobar());
    }

    /**
     * Test that decorated decorator works.x
     */
    public function testDecoratedDecorator() {
        $baseClass = new BaseClass();
        $countClassDecorator = new CountCallDecorator($baseClass);
        $baseClassDecorator = new BaseClassDecorator($countClassDecorator);
        $this->assertSame($baseClassDecorator->foobar(), 'Decorated '.$baseClass->foobar());
    }

    /**
     * Test that custom defined function of a decorator works from another decorator.
     */
    public function testDecoratedDecoratorCustomMethod() {
        $baseClass = new BaseClass();
        $countClassDecorator = new CountCallDecorator($baseClass);
        $baseClassDecorator = new BaseClassDecorator($countClassDecorator);

        $countClassDecorator->foobar();
        $countClassDecorator->foobar();

        $this->assertSame($baseClassDecorator->getCallsCount(), ['foobar' => 2]);
    }
}
