<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Formatting\Formats\Rich2FormatParsed;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;

class Rich2FormatTest extends AbstractFormatTestCase
{
    protected function prepareFormatter(): FormatInterface
    {
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE])
            ->addCall("registerEmbed", [FileEmbed::class, FileEmbed::TYPE])
            ->addCall("registerEmbed", [YouTubeEmbed::class, YouTubeEmbed::TYPE])
            ->addCall("registerEmbed", [LinkEmbed::class, LinkEmbed::TYPE]);
        return self::container()->get(Rich2Format::class);
    }

    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("rich2"))->getAllFixtures();
    }

    public function testParseImageUrlsExcludeEmojis()
    {
        $this->markTestSkipped();
    }

    public function badInputProvider(): array
    {
        return [
            ["renderHTML", "Abc", "<p>There was an error rendering this rich post.</p>"],
            ["renderPlainText", "Abc", "There was an error rendering this rich post."],
            ["renderQuote", "Abc", "<p>There was an error rendering this rich post.</p>"],
            ["parseAttachments", "Abc", []],
            ["parseHeadings", "Abc", []],
            ["parseImageUrls", "Abc", []],
            ["parseImages", "Abc", []],
            ["parseAllMentions", "Abc", []],
        ];
    }

    /**
     * Test that rich2 handles bad input appropriately
     *
     * @param string $method
     * @param string $input
     * @param mixed $expected
     * @return void
     * @dataProvider badInputProvider
     */
    public function testHandlingForBadInput(string $method, string $input, $expected = null)
    {
        $output = $this->prepareFormatter()->$method("abc");
        $this->assertSame($expected, $output);
    }

    /**
     * Test parse method with good input
     * @return void
     */
    public function testParseWithGoodInput()
    {
        $input = '[{"type":"paragraph","children":[{"text":"good input"}]}]';
        $expected = $input;

        /** @var Rich2FormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        $this->assertInstanceOf(Rich2FormatParsed::class, $output);
        $this->assertInstanceOf(FormatParsedInterface::class, $output);
        $this->assertSame($expected, $output->getRawContent());
        $this->assertSame($expected, json_encode($output->getNodeList()));
    }

    /**
     * Test parse method with bad input
     * @return void
     */
    public function testParseWithBadInput()
    {
        $input = "invalid input";
        $expected = '[{"type":"paragraph","children":[{"text":"' . Rich2Format::RENDER_ERROR_MESSAGE . '"}]}]';

        /** @var Rich2FormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        $this->assertInstanceOf(Rich2FormatParsed::class, $output);
        $this->assertInstanceOf(FormatParsedInterface::class, $output);
        $this->assertSame($expected, $output->getRawContent());
        $this->assertSame($expected, json_encode($output->getNodeList()));
    }
}
