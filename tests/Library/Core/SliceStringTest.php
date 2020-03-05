<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for sliceString().
 */

class SliceStringTest extends TestCase {

    /**
     * Test {@link sliceString against several scenarios.
     *
     * @param string $testString The string to slice.
     * @param int $testLength The number of characters to slice at.
     * @param string $testSuffix The suffix to add to the string if it is longer than {@link $length}.
     * @param string $expected The expected result.
     * @dataProvider provideTestSliceStringArrays
     */
    public function testSliceString($testString, $testLength, $testSuffix, $expected) {
        $actual = sliceString($testString, $testLength, $testSuffix);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link sliceString()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestSliceStringArrays() {
        $r = [
            'emptyString' => [
                '',
                2,
                '…',
                '',
            ],
            'typicalString' => [
                'This is my string. There are other strings like it, but this one is mine.',
                20,
                '…',
                'This is my string. …',
            ],
            'notLengthCondition' => [
                'This is my string. There are other strings like it, but this one is mine.',
                0,
                '…',
                'This is my string. There are other strings like it, but this one is mine.',
            ],
        ];

        return $r;
    }
}
