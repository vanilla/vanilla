<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for stringEndsWith().
 */

class StringEndsWithTest extends TestCase {

    /**
     * Tests {@link stringEndsWith()} against several scenarios.
     *
     * @param string $testHaystack The string to check.
     * @param string $testNeedle The substring to check for.
     * @param bool $testCaseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $testTrim Whether or not to trim the needle off the haystack.
     * @param bool|string $expected The expeted resul.
     * @dataProvider provideTestStringEndsWithArrays
     */
    public function testStringEndsWith($testHaystack, $testNeedle, $testCaseInsensitive, $testTrim, $expected) {
        $actual = stringEndsWith($testHaystack, $testNeedle, $testCaseInsensitive, $testTrim);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link stringEndsWith()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestStringEndsWithArrays() {
        $r = [
            'twoEmptyStringsTrimTrue' => [
                '',
                '',
                false,
                true,
                '',
            ],
            'twoEmptyStringsTrimFalse' => [
                '',
                '',
                false,
                false,
                true,
            ],
            'emptyHaystackTrimTrue' => [
                '',
                'needle',
                false,
                true,
                '',
            ],
            'emptyHaystackTrimFalse' => [
                '',
                'needle',
                false,
                false,
                false,
            ],
            'needlePresentTrimTrue' => [
                'haystackEndsWithANeedle',
                'Needle',
                false,
                true,
                'haystackEndsWithA',
            ],
            'needlePresentTrimFalse' => [
                'haystackEndsWithANeedle',
                'Needle',
                false,
                false,
                true,
            ],
            'needleComesEarlier' => [
                'the needle does not end this haystack',
                'needle',
                false,
                false,
                false,
            ],
            'caseInsensitive' => [
                'haystackEndsWithANeEdLe',
                'needle',
                true,
                false,
                true,
            ],
        ];

        return $r;
    }
}
