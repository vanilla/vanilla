<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;
use VanillaTests\Fixtures\Tuple;

/**
 * Tests for safePrint().
 */

class SafePrintTest extends TestCase {

    /**
     * Test {@link safePrint()} against several scenarios.
     *
     * @param mixed $testMixed The variable to return or echo.
     * @param bool $testReturnData Whether or not to return the data instead of echoing it.
     * @param string|void $expected The expected result.
     * @dataProvider provideTestSafePrintArrays
     */
    public function testSafePrint($testMixed, $testReturnData, $expected) {
        $actual = safePrint($testMixed, $testReturnData);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link safePrint()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSafePrintArrays() {
        $r = [
            'emptyString' => [
                '',
                true,
                'safePrint{empty string}',
            ],
            'testMixedTrue' => [
                true,
                true,
                'safePrint{true}',
            ],
            'testMixedFalse' => [
                false,
                true,
                'safePrint{false}',
            ],
            'testMixedNull' => [
                null,
                true,
                'safePrint{null}',
            ],
            'testMixedZero' => [
                0,
                true,
                'safePrint{0}',
            ],
            'testMixedArray' => [
                [1, 2],
                true,
                "Array\n(\n    [0] => 1\n    [1] => 2\n)\n",
            ],
            'testMixedObject' => [
                new Tuple('a', 'b'),
                true,
                "VanillaTests\Fixtures\Tuple Object\n(\n    [a] => a\n    [b] => b\n)\n",
            ],
        ];

        return $r;
    }
}
