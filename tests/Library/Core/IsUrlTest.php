<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for isUrl().
 */
class IsUrlTest extends TestCase {

    /**
     * Test {@link isUrl()} against several scenarios.
     *
     * @param string $testStr The string to check.
     * @param bool $expected The expected result.
     * @dataProvider provideIsUrlArrays
     */
    public function testIsUrl($testStr, bool $expected) {
        $actual = isUrl($testStr);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link isUrl()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideIsUrlArrays() {
        $r = [
            'validUrl' => [
                'https://github.com/tburry/pquery/blob/master/pQuery.php',
                true,
            ],
            'extraCharInHttps' => [
                'htttps://github.com/tburry/pquery/blob/master/pQuery.php',
                false,
            ],
            'missingCharInHttps' => [
                'htps://github.com/tburry/pquery/blob/master/pQuery.php',
                false,
            ],
            'missingSlash' => [
                'https:/github.com/tburry/pquery/blob/master/pQuery.php',
                false,
            ],
            'missingColon' => [
                'https//github.com/tburry/pquery/blob/master/pQuery.php',
                false,
            ],
            'notAString' => [
                null,
                false,
            ],
            'startsWithSlashes' => [
                '//github.com/tburry/pquery/blob/master/pQuery.php',
                true,
            ]
        ];

        return $r;
    }
}
