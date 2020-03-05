<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for debugMethod().
 */

class DebugMethodTest extends TestCase {

    /**
     * Tests {@link debugMethod()} against several scenarios.
     *
     * @param string $testMethodName The name of the method.
     * @param array $testMethodArgs An array of arguments passed to the method.
     * @param string $expected The expected result.
     * @dataProvider provideTestDebugMethodArrays
     */
    public function testDebugMethod($testMethodName, $testMethodArgs, $expected) {
        $this->expectOutputString($expected);
        debugMethod($testMethodName, $testMethodArgs);
    }

    /**
     * Provide test data for {@link debugMethod()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestDebugMethodArrays() {
        $r = [
            'noArgs' => [
                'methodWithNoArgs',
                [],
                "methodWithNoArgs()\n",
            ],
            'oneArgNull' => [
                'methodWithOneArg',
                [null],
                "methodWithOneArg(null)\n",
            ],
            'twoArgs' => [
                'methodWithTwoArgs',
                [null, '10'],
                "methodWithTwoArgs(null, '10')\n",
            ],
            'withArray' => [
                'methodWithArrayArg',
                [[1, 2, 3]],
                "methodWithArrayArg('Array(3)')\n",
            ],
        ];

        return $r;
    }
}
