<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for chr_utf8().
 */

class ChrUtf8Test extends TestCase {

    /**
     * Test {@link chr_utf8()} against several scenarios.
     *
     * @param mixed $testNum A UTF-8 character code.
     * @param string $expected The expected result.
     * @dataProvider provideChrUtf8Arrays
     */
    public function testChrUtf8($testNum, $expected) {
        $actual = chr_utf8($testNum);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link chr_utf8()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideChrUtf8Arrays() {
        $r = [
            'lessThan128' => [
                37,
                '%',
            ],
            'lessThan128Hex' => [
                0x25,
                '%',
            ],
            'lessThan2048' => [
                169,
                '©',
            ],
            'lessThan65536' => [
                2972,
                'ஜ',
            ],
            'lessThan2097152' => [
                524288,
                '򀀀',
            ],
            'badInputNum' => [
                30000000,
                '',
            ]
        ];

        return $r;
    }
}
