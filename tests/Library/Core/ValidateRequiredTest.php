<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateRequired().
 */

class ValidateRequiredTest extends TestCase {

    /**
     * Test {@link validateRequired()} against several scenarios.
     *
     * @param mixed $testValue The value to validate.
     * @param object|array|null $testField The field object to validate the value against.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateRequiredArrays
     */
    public function testValidateRequired($testValue, $testField, $expected) {
        $actual = validateRequired($testValue, $testField);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateRequired()}
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateRequiredArrays() {
        $r = [
            'valueEmptyString' => [
                '',
                null,
                false,
            ],
            'emptyStringDefaultAlsoEmptyString' => [
                '',
                ['Default' => ''],
                true,
            ],
            'valueIsArray' => [
                ['foo', 'bar'],
                null,
                true,
            ],
            'valueIsInt' => [
                5,
                null,
                true,
            ],
            'valueIsString' => [
                'fooBar',
                null,
                true,
            ],
            'valueIsBool' => [
                true,
                null,
                false,
            ],
        ];

        return $r;
    }
}
