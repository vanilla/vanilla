<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for pageNumber().
 */

class PageNumberTest extends TestCase {

    /**
     * Tests {@link pageNumber()} against several scenarios.
     *
     * @param int $testOffset The database offset, starting at zero.
     * @param int $testLimit The database limit, otherwise known as the page size.
     * @param bool|string $testUrlParam Whether or not the result should be formatted as a url parameter, suitable for OffsetLimit.
     * - bool: true means yes, false means no.
     * - string: The prefix for the page number.
     * @param bool $testFirst Whether or not to return the page number if it is the first page.
     * @param float|string $expected The expected result.
     * @dataProvider provideTestPageNumberArrays
     */
    public function testPageNumber($testOffset, $testLimit, $testUrlParam, $testFirst, $expected) {
        $actual = pageNumber($testOffset, $testLimit, $testUrlParam, $testFirst);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link testPageNumber()}
     *
     * @return array An array of test data.
     */
    public function provideTestPageNumberArrays() {
        $r = [
            'offsetZero' => [
              0,
              50,
              false,
              true,
              1.0
            ],
            'offsetTwentyFive' => [
                25,
                3,
                false,
                true,
                9.0,
            ],
            'firstIfCase' => [
                0,
                50,
                true,
                false,
                '',
            ],
            'secondIfCase' => [
                25,
                3,
                true,
                true,
                'p9',
            ],
            'thirdIfCase' => [
                25,
                3,
                'Page Number ',
                true,
                'Page Number 9',
            ],
        ];

        return $r;
    }
}
