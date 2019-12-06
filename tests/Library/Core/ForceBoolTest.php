<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;


use PHPUnit\Framework\TestCase;

/**
 * Tests for forceBool().
 */
class ForceBoolTest extends TestCase {

    /**
     * Test {@link forceBool()} against several scenarios.
     *
     * @param mixed $expected The expected result.
     * @param mixed $testValue The value to force.
     * @param bool $testDefaultValue The default value to return if conversion to a boolean is not possible.
     * @param mixed $testTrue The value to return for true.
     * @param mixed $testFalse The value to return for false.
     * @dataProvider provideForceBoolArrays
     */

    public function testForceBool($expected, $testValue, bool $testDefaultValue, $testTrue, $testFalse) {
        $actual = forceBool($testValue, $testDefaultValue, $testTrue, $testFalse);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link forceBool()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideForceBoolArrays() {
        $r = [
            'trueBool' => [
                true,
                true,
                false,
                true,
                false,
            ],
            'falseBool' => [
              false,
              false,
              false,
              true,
              false,
            ],
            'trueStringToString' => [
                'true',
                'true',
                false,
                'true',
                'false',
            ],
            'trueStringToBool' => [
                true,
                'true',
                false,
                true,
                false,
            ],
            'falseStringToString' => [
                'false',
                'false',
                false,
                'true',
                'false',
            ],
            'falseStringToBool' => [
                false,
                'false',
                true,
                true,
                false,
            ],
            'cannotForce' => [
                true,
                null,
                true,
                true,
                false,
            ],
            'numberZero' => [
                false,
                0,
                true,
                true,
                false,
            ],
            'numberNonZero' => [
                true,
                3.14,
                false,
                true,
                false,
            ]
        ];
        return $r;
    }

}
