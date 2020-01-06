<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateString().
 */

class ValidateStringTest extends TestCase {

    /**
     * Test {@link validateString()} against various scenarios.
     *
     * @param mixed $testValue The value to validate.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateStringArrays
     */
    public function testValidateString($testValue, $expected) {
        $actual = validateString($testValue, []);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateStringArrays() {
        $r = [
            'basicString' => [
                'string',
                true,
            ],
            'emptyString' => [
                '',
                true,
            ],
            'nullCase' => [
                null,
                true,
            ],
            'array' => [
                ['not a' => 'scalar'],
                false,
            ],
        ];

        return $r;
    }
}
