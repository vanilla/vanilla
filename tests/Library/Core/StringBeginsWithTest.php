<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for stringBeginsWith().
 */

class StringBeginsWithTest extends TestCase {

    /**
     * Test {@link stringBeginsWith()} against several scenarios.
     *
     * @param string $testHaystack The string to check.
     * @param string $testNeedle The substring to check for.
     * @param bool $testCaseInsensitive Whether or not the comparison should be case insensitive.
     * @param bool $testTrim Whether or not to trim the needle off the haystack if it is found.
     * @param bool|string $expected The expected result.
     * @dataProvider provideTestStringBeginsWithArrays
     */
    public function testStringBeginsWith($testHaystack, $testNeedle, $testCaseInsensitive, $testTrim, $expected) {
        $actual = stringBeginsWith($testHaystack, $testNeedle, $testCaseInsensitive, $testTrim);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link stringBeginsWith()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestStringBeginsWithArrays() {
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
                'needleBeginsTheHaystack',
                'needle',
                false,
                true,
                'BeginsTheHaystack',
            ],
            'needlePresentTrimFalse' => [
                'needleBeginsTheHaystack',
                'needle',
                false,
                false,
                true,
            ],
            'needleComesLater' => [
                'the needle comes later',
                'needle',
                false,
                false,
                false,
            ],
            'caseInsensitive' => [
                'NeEdLeBeginsHaystack',
                'needle',
                true,
                false,
                true,
            ],
        ];

        return $r;
    }
}
