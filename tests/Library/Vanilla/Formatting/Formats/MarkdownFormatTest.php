<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\MarkdownFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Tests for the MarkdownFormat.
 */
class MarkdownFormatTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(MarkdownFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("markdown"))->getAllFixtures();
    }

    /**
     * Test disallowing spoilers within a quote.
     */
    public function testMarkdownSpoilerBug()
    {
        $md = <<<EOT
> [spoiler]
> 
> [/spoiler]
EOT;
        $expected = <<<EOT
<blockquote class="UserQuote blockquote"><div class="QuoteText blockquote-content">
  <p class="blockquote-line">[spoiler]</p>
  
  <p class="blockquote-line">[/spoiler]</p>
</div></blockquote>

EOT;

        $formatter = $this->prepareFormatter();
        $actual = $formatter->renderHTML($md);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test a multi-line spoiler.
     */
    public function testMultilineSpoiler()
    {
        $md = <<<EOT
[spoiler]
s
[/spoiler]
EOT;

        $expected = <<<EOT
<div class="Spoiler">
s
</div>

EOT;

        $formatter = $this->prepareFormatter();
        $actual = $formatter->renderHTML($md);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @param string $body
     * @param array $expected
     * @dataProvider provideAtMention
     * @dataProvider provideProfileUrl
     */
    public function testAllUserMentionParsing(string $body, array $expected = ["UserNoSpace"])
    {
        $result = $this->prepareFormatter()->parseAllMentions($body);
        $this->assertEqualsCanonicalizing($expected, $result);
    }
}
