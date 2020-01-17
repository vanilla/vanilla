<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for dateCompare().
 */
class DateCompareTest extends TestCase {

    /**
     * Test {@link dateCompare()} against several scenarios.
     *
     * @param int|string $testDate1 A timestamp or string representation of a date.
     * @param int|string $testDate2 A timestamp or string representation of a date.
     * @param int $expected Expected result.
     * @dataProvider provideDateCompareArrays
     */
    public function testDateCompare($testDate1, $testDate2, int $expected) {
        $actual = dateCompare($testDate1, $testDate2);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link dateCompare}.
     *
     * @return array Returns an array of test data.
     */
    public function provideDateCompareArrays() {
        $r = [
            'americanStringDateLess' => [
                '12/22/1978',
                '1/17/2006',
                -1,
            ],
            'dashedDayMonthYearEqual' => [
                '30-6-2008',
                '30-6-2008',
                0,
            ],
            'alphaMonthGreater' => [
                'June 2nd, 2001',
                'June 1st, 2001',
                1,
            ],
        ];

        return $r;
    }
}
