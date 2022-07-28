<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\BBCodeFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Tests for the BBCodeFormat.
 */
class BBCodeFormatTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(BBCodeFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("bbcode"))->getAllFixtures();
    }

    /**
     * Umlauts should be allowed in URLs.
     */
    public function testUmlautLinks(): void
    {
        $bbcode = "[url=https://de.wikipedia.org/wiki/Prüfsumme]a[/url]";
        $actual = $this->prepareFormatter()->renderHTML($bbcode);
        $expectedHref = url(
            "/home/leaving?" .
                http_build_query([
                    "allowTrusted" => 1,
                    "target" => "https://de.wikipedia.org/wiki/Prüfsumme",
                ])
        );
        $expected = '<a href="' . htmlspecialchars($expectedHref) . '" rel="nofollow">a</a>';
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * @param string $body
     * @param array $expected
     * @dataProvider provideAtMention
     * @dataProvider provideProfileUrl
     * @dataProvider provideBBCodeQuote
     */
    public function testAllUserMentionParsing(string $body, array $expected = ["UserNoSpace"])
    {
        $result = $this->prepareFormatter()->parseAllMentions($body);
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function provideBBCodeQuote(): array
    {
        return [
            "validQuote" => ['[quote="UserNoSpace;d-999"]UserNoSpace is an amazing human slash genius.[/quote]'],
        ];
    }
}
