<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for getAllMentions().
 */

class GetAllMentionsTest extends TestCase {

    /**
     * Test {@link getAllMentions()} against several scenarios.
     *
     * @param string $testStr The string to parse.
     * @param array $expected The expected result.
     * @dataProvider provideTestGetAllMentionsArrays
     */
    public function testGetAllMentions($testStr, $expected) {
        $actual = getAllMentions($testStr);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for {@link getAllMentions()}.
     *
     * @return array Returns an array of test data.
     */
    public function provideTestGetAllMentionsArrays() {
        $r = [
            'noMentions' => [
                'this string has no mentions at all',
                [],
            ],
            'oneMention' => [
                'this string has one mention: @blutarch',
                ['blutarch'],
            ],
            'multiple mentions' => [
                'this string mentions @blutarch and @herodotus and @gorgias and @pl@to',
                ['blutarch', 'herodotus', 'gorgias', 'pl@to'],
            ],
            'quoted mention' => [
                'this string has one @unquoted mention and one quoted "@mention in it"',
                ['unquoted', 'mention'],
            ],
        ];

        return $r;
    }
}
