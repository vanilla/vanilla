<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for calculateNumberOfPages().
 */
class CalculateNumberOfPagesTest extends TestCase {

    /**
     * Test {@link calculateNumberOfPages() against several scenarios.
     *
     * @param int $testItemCount Total number of items.
     * @param int $testItemsPerPage Number of items per page.
     * @param int $expected Expected result.
     * @dataProvider provideCalculateNumberOfPagesArrays
     */
    public function testCalculateNumberOfPages(int $testItemCount, int $testItemsPerPage, int $expected) {
        $actual = calculateNumberOfPages($testItemCount, $testItemsPerPage);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link calculateNumberOfPages()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideCalculateNumberOfPagesArrays() {
        $r = [
            'itemsLessThanItemsPerPage' => [
                4,
                5,
                1,
            ],
            'itemsSameAsItemsPerPage' => [
                5,
                5,
                1,
            ],
            'itemsMultipleOfItemsPerPage' => [
                9,
                3,
                3,
            ],
            'itemsNotMultipleOfItemsPerPage' => [
                10,
                3,
                4,
            ],
            'noItems' => [
                0,
                5,
                1,
            ],
        ];

        return $r;
    }
}
