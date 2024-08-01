<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Utility;

use Vanilla\Utility\KeywordUtils;
use VanillaTests\VanillaTestCase;

class KeywordUtilsTest extends VanillaTestCase
{
    /**
     * Test replace() method in ContentFilter
     *
     * @param string|false $shouldMatch
     * @param string $text
     * @param array $words
     * @dataProvider provideContent
     */
    public function testCheckMatch(string|false $shouldMatch, string $text, array $words)
    {
        $doesMatch = KeywordUtils::checkMatch($text, $words);
        $this->assertSame($shouldMatch, $doesMatch);
    }

    /**
     * Provide data to be tested for a match.
     *
     * @return array Data provider.
     */
    public function provideContent(): array
    {
        $provider = [
            "No keywords" => [false, "This should not be blocked.", []],
            "Simple clean text " => [false, "this should not be blocked", ["word"]],
            "Simple blocked text" => ["blocked", "this should be blocked", ["blocked"]],
            "Unicode character ποο" => ["ποο", "unicode characters ποο", ["ποο"]],
            "Emoticon 🤓" => ["🤓", "unicode characters 🤓", ["🤓"]],
            "RTL unicode text" => ["بلوك", "unicode بلوك clean", ["بلوك"]],
            "Simple phrase" => ["best test phrase", "This is the best test phrase.", ["best test phrase"]],
            "Complex phrase 1" => [
                "best test phrase",
                "This test phrase is the best test phrase.",
                ["best test phrase"],
            ],
            "Complex phrase 2" => [
                "best test phrase",
                "The best test phrase is this test phrase.",
                ["best test phrase"],
            ],
            "Complex phrase 3" => [
                "best test foo",
                "The best test  phrase is this test phrase.",
                ["best test phrase", "best test foo"],
            ],
            "Complex phrase 4" => [
                "best test foo",
                "The best test foo is this test phrase.",
                ["best test phrase", "best test foo"],
            ],
            "Punctuation 1" => ["you suck", "You; suck", ["you suck"]],
            "Punctuation 2" => ["you suck", "You 'suck'", ["you suck"]],
            "Punctuation 3" => ["you suck", 'You "suck"', ["you suck"]],
            "Keyword includes boundary 1" => [false, "Have a nice day!", ['$a']],
            "Keyword includes boundary 2" => [false, "Have a nice day!", ['a$']],
            "Keyword includes boundary 3" => ["a$", "a$!", ['a$']],
            "Keyword includes boundary 4" => ["ass", "Around here, a$$ is a bad word.", ["ass"]],
            "Partial match, end of text" => [false, "this is the best", ["best test phrase"]],
            "Matching with different cases" => ["Silly", "siLLy goose", ["Silly"]],
            "Matching with different cases 2" => ["silly", "Silly goose", ["silly"]],
        ];
        return $provider;
    }
}
