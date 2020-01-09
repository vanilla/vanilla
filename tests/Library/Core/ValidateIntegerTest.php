<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateInteger().
 */

class ValidateIntegerTest extends TestCase {

    /**
     * Tests {@link validateInteger()} against several scenarios.
     *
     * @param mixed $testValue The value to validate.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateIntegerArrays
     */
    public function testValidateInteger($testValue, $expected) {
        $actual = validateInteger($testValue);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateInteger().
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateIntegerArrays() {
        $r = [
            'emptyString' => [
                '',
                true,
            ],
            'string' => [
                'string',
                false,
            ],
            'int' => [
                55,
                true,
            ],
            'float' => [
                3.14,
                false,
            ],
            'array' => [
                ['f00' => 'bar'],
                false,
            ],
            'emptyArray' => [
                [],
                true,
            ],
        ];

        return $r;
    }
}
