<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for offsetLimit().
 */

class OffsetLimitTest extends TestCase {

    /**
     * Tests {@link offsetLimit()} against several scenarios.
     *
     * @param string $testOffsetOrPage The page query in one of the following formats:
     * - p<x>: Get page x.
     * - <x>-<y>: This is a range of viewing records x through y.
     * - <x>lim<n>: This is a limit/offset pair.
     * - <x>: This is a limit where offset is given in the next parameter.
     * @param int $testLimitOrPageSize The page size or limit.
     * @param bool $testThrow Whether or not to throw an error if the {@link $testOffsetOrPage} is too high.
     * @param array $expected The expected result.
     * @dataProvider provideTestOffsetLimitArrays
     * @throws |Exception Throws a 404 exception if the {@link $testOffsetOrPage} is too high and {@link $throw} is true.
     */
    public function testOffsetLimit($testOffsetOrPage, $testLimitOrPageSize, $testThrow, $expected) {
        $actual = offsetLimit($testOffsetOrPage, $testLimitOrPageSize, $testThrow, $expected);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testOffsetLimit}
     *
     * @return array Returns an array of test data.
     */
    public function provideTestOffsetLimitArrays() {
        $r = [
            'getPageFormat' => [
                'p<2>',
                '25',
                false,
                [0, 25],
            ],
            'rangeQueryFormat' => [
                '<2>-<20>',
                '25',
                false,
                [0, 25],
            ],
            'limitOffsetPairFormat' => [
                '<5>lim<2>',
                '2',
                false,
                [0, 2],
            ],
            'justLimitFormat' => [
                '<5>',
                '2',
                false,
                [0, 2],
            ],
            'negativeValues' => [
                '<-5>',
                '-5',
                false,
                [0, 50],
            ],
        ];

        return $r;
    }
}
