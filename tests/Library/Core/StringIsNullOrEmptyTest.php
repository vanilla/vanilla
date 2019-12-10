<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for stringIsNullOrEmpty().
 */

class StringIsNullOrEmptyTest extends TestCase {

    /**
     * Test {@link stringIsNullOrEmpty()} against several scenarios.
     *
     * @param string $testString The string to check.
     * @param bool $expected The expected result.
     * @dataProvider provideTestStringIsNullOrEmptyArrays
     */
    public function testStringIsNullOrEmpty($testString, $expected) {
        $actual = stringIsNullOrEmpty($testString);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for {@link stringIsNullOrEmpty()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestStringIsNullOrEmptyArrays() {
        $r = [
            'stringIsNull' => [
                null,
                true,
            ],
            'stringIsEmpty' => [
                '',
                true,
            ],
            'stringIsNotNullOrEmpty' => [
                'sting',
                false,
            ],
        ];

        return $r;
    }
}
