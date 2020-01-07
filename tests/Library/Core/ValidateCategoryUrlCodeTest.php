<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for validateCategoryUrlCode().
 */

class ValidateCategoryUrlCodeTest extends TestCase {

    /**
     * Test {@link validateCategoryUrlCode()} against several scenarios.
     *
     * @param string $testUrlCode The URL code to validate.
     * @param bool $expected The expected result.
     * @dataProvider provideTestValidateCategoryUrlCodeArrays
     */
    public function testValidateCategoryUrlCode($testUrlCode, $expected) {
        $actual = validateCategoryUrlCode($testUrlCode);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testValidateCategoryUrlCode()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestValidateCategoryUrlCodeArrays() {
        $r = [
            'regularOldString' => [
                'foo-bar',
                true,
            ],
            'stringWithSpaces' => [
                'string     with    spaces',
                false,
            ],
            'numberString' => [
                '123123123',
                false,
            ],
            'reservedSlug' => [
                'archives',
                false,
            ],
        ];

        return $r;
    }
}
