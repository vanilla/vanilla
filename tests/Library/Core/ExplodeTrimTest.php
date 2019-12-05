<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;


use PHPUnit\Framework\TestCase;

/**
 * Tests for explodeTrim().
 */
class ExplodeTrimTest extends TestCase {

    /**
     * Test {@link explodeTrim()} against several scenarios.
     *
     * @param string $testDelimiter The boundary string.
     * @param string $testString The input string to be trimmed.
     * @param bool $testImplode Whether to implode exploded input string before returning.
     * @param string|array $expected Expected result.
     * @dataProvider provideExplodeTrimArrays
     */
    public function testExplodeTrim(string $testDelimiter, string $testString, bool $testImplode, $expected) {
        $actual = explodeTrim($testDelimiter, $testString, $testImplode);
        if (is_array($actual)) {
            $this->assertEqualsCanonicalizing($expected, $actual);
        } else {
            $this->assertSame($expected, $actual);
        }
    }

    /**
     * Provide test data for {@link explodeTrim()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideExplodeTrimArrays() {
        $r = [
            'stringWithExtraSpacesExploded' => [
                ' ',
                'this is a  string  with some  extra        spaces',
                false,
                ['this', 'is', 'a', 'string', 'with', 'some', 'extra', 'spaces',],
            ],
            'stringWithExtraSpacesImploded' => [
                ' ',
                'this is a  string  with some  extra        spaces',
                true,
                'this is a string with some extra spaces',
            ],
            'stringWithFours' => [
                '4',
                'this4is4   a    4 string   4   split 4on44    4444fours',
                false,
                ['this', 'is', 'a', 'string', 'split', 'on', 'fours'],
            ],
            'stringWithFoursImploded' => [
                '4',
                'this4is4   a    4 string   4   split 4on44    4444fours',
                true,
                'this4is4a4string4split4on4fours',
            ]
        ];

        return $r;
    }
}
