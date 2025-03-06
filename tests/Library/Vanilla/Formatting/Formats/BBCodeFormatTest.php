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
                ]),
            true
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

    /**
     * Test a known bad post that trips up the BBCode lexer.
     *
     * @return void
     */
    public function testPostCrashingLexer()
    {
        $postContents = <<<BBCODE
[b]Test[/b]\nComplete for 4 points.\nI love walking [url=\"data:image/jpeg;base64,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
BBCODE;

        $result = $this->prepareFormatter()->renderHTML($postContents);
        $this->assertEquals(\BBCode::ERROR_HTML, $result);
    }
}
