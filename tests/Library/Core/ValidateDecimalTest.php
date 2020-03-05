<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateDecimal().
 */

class ValidateDecimalTest extends TestCase {

    /**
     * Tests {@link validateDecimal()} against several scenarios.
     *
     * @param mixed $testValue The value to validate.
     * @param object $testField The field information for the value.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateDecimalArrays
     */
    public function testValidateDecimal($testValue, $testField, $expected) {
        time();
        $actual = validateDecimal($testValue, $testField);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateDecimal()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateDecimalArrays() {
        $r = [
            'integerValue' => [
                4,
                ['number' => ''],
                true,
            ],
            'stringNumber' => [
                '4',
                ['number'],
                true,
            ],
            'stringText' => [
                'NaN',
                ['number'],
                false,
            ],
            'bool' => [
                true,
                ['number'],
                false,
            ],
        ];

        return $r;
    }

    /**
     * Test validateDecimal() with an object.
     */
    public function testValidateDecimalWithObject() {
        $testObject = new \stdClass();
        $testObject->AllowNull = true;
        $actual = validateDecimal(null, $testObject);
        $expected = true;
        $this->assertSame($expected, $actual);
    }
}
