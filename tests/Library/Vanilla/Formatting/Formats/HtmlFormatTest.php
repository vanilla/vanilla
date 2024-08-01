<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\HtmlFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Tests for the HtmlFormat.
 */
class HtmlFormatTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(HtmlFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("html"))->getAllFixtures();
    }

    /**
     * @param string $body
     * @param array $expected
     * @dataProvider provideAtMention
     * @dataProvider provideProfileUrl
     * @dataProvider provideHtmlQuote
     */
    public function testAllUserMentionParsing(string $body, array $expected = ["UserNoSpace"])
    {
        $result = $this->prepareFormatter()->parseAllMentions($body);
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function provideHtmlQuote(): array
    {
        return [
            "validQuote" => [
                '<blockquote class="Quote" rel="UserNoSpace">UserNoSpace is an amazing human slash genius.</blockquote>',
            ],
        ];
    }

    /**
     * Esnure htmLawed is being run.
     *
     * @return void
     *
     * @link https://higherlogic.atlassian.net/browse/VNLA-5166
     */
    public function testSanitizeXss()
    {
        $input = <<<HTML
<a data-<a  <a data-%a0id='z <b onmouseover=self[&apos;con&apos;+&apos;firm&apos;](&apos;hehe&apos;) style=position:fixed;top:0;right:0;bottom:0;left:0;background:rgba(0, 0, 0, 0.0);z-index: 5000;'href="#xss">click here</a>
HTML;

        $output = $this->prepareFormatter()->renderHTML($input);
        $expected = "a data-click here";

        $this->assertEquals($expected, $output);
    }
}
