<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for isTimestamp().
 */

class IsTimestampTest extends TestCase {

    /**
     * Tests {@link isTimestamp()} against several scenarios.
     *
     * @param int $testStamp The timestamp to check.
     * @param bool $expected The expected result.
     * @dataProvider provideTestIsTimestamp
     */
    public function testIsTimestamp($testStamp, $expected) {
        $actual = isTimestamp($testStamp);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link isTimestamp}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestIsTimestamp() {
        $r = [
            'monthDayYear' => [
                10051998,
                true,
            ],
            'dayMonthYear' => [
                20112002,
                true,
            ],
            'twoDigitYear' => [
                060777,
                true,
            ],
            'noInitialZeros' => [
                6777,
                true,
            ],
            'tooManyDigits' => [
                12390617194092879,
                false,
            ],
        ];

        return $r;
    }
}
