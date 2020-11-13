<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use PHPUnit\Framework\TestCase;

/**
 * Allows a test case to expect an error to occur.
 */
trait ExpectExceptionTrait {

    /**
     * Run code the expects and exception and continue.
     *
     * @param string $expectedClass
     * @param callable $callable
     */
    protected function runWithExpectedException(string $expectedClass, callable $callable) {
        $caught = null;
        try {
            call_user_func($callable);
        } catch (\Exception $e) {
            $caught = $e;
        }

        $this->assertInstanceOf($expectedClass, $caught, 'Expected an ' . $expectedClass . ' to occur.');
    }

    /**
     * Run code the expects and exception and continue.
     *
     * @param int $expectedCode
     * @param callable $callable
     */
    protected function runWithExpectedExceptionCode(int $expectedCode, callable $callable) {
        $caught = null;
        try {
            call_user_func($callable);
        } catch (\Exception $e) {
            $caught = $e;
        }

        TestCase::assertInstanceOf(\Exception::class, $caught);
        TestCase::assertEquals($expectedCode, $caught->getCode());
    }
}
