<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Tests for the HtmlFormat.
 */
class WysiwygFormatTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(WysiwygFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("wysiwyg"))->getAllFixtures();
    }

    /**
     * @param string $body
     * @param array $expected
     * @dataProvider provideAtMention
     * @dataProvider provideProfileUrl
     * @dataProvider provideWysiwygQuote
     */
    public function testAllUserMentionParsing(string $body, array $expected = ["UserNoSpace"])
    {
        $result = $this->prepareFormatter()->parseAllMentions($body);
        $this->assertEqualsCanonicalizing($expected, $result);
    }

    public function provideWysiwygQuote(): array
    {
        return [
            "validQuote" => [
                '<blockquote class="Quote">
                  <div class="QuoteAuthor"><a href="/profile/user1" class="js-userCard" data-userid="15">UserNoSpace</a> said:</div>
                  <div class="QuoteText">test</div>
                </blockquote>',
            ],
            "alt (broken) quote format" => [
                '<blockquote class="Quote">
                  <div><a rel="nofollow">UserNoSpace</a> said:</div>
                  <div><p>adsf</p></div>
                </blockquote>',
            ],
        ];
    }
}
