<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\TextExFormat;
use Vanilla\Formatting\Formats\TextFormat;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;

/**
 * Tests for the TextExFormat.
 */
class TextFormatTest extends AbstractFormatTestCase
{
    use UserMentionTestTraits;

    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        return self::container()->get(TextFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("text"))->getAllFixtures();
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
