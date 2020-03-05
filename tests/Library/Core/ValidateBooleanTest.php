<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateBoolean().
 */

class ValidateBooleanTest extends TestCase {

    /**
     * Test {@link validateBoolean()} against several scenarios.
     *
     * @param mixed $testValue The value to validate.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateBooleanArrays
     */
    public function testValidateBoolean($testValue, $expected) {
        $actual = validateBoolean($testValue);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateBoolean()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateBooleanArrays() {
        $r = [
            'boolean' => [
                false,
                true,
            ],
            'intTrue' => [
                1,
                true,
            ],
            'intFalse' => [
                100,
                false,
            ],
            'emptyString' => [
                '',
                true,
            ],
            'stringTrue' => [
                'false',
                true,
            ],
            'stringFalse' => [
                'notTrue',
                false,
            ],
        ];

        return $r;
    }
}
