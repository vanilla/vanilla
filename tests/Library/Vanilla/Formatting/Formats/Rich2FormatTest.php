<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\IFrameEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\Rich2Format;
use Vanilla\Formatting\Formats\Rich2FormatParsed;
use VanillaTests\Fixtures\EmbeddedContent\MockHeadingProviderEmbed;
use Vanilla\Formatting\FormatText;
use Vanilla\Formatting\Rich2\NodeList;

use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;

class Rich2FormatTest extends AbstractFormatTestCase
{
    protected function prepareFormatter(): FormatInterface
    {
        self::setConfig("Garden.TrustedDomains", "*.higherlogic.com");
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE])
            ->addCall("registerEmbed", [FileEmbed::class, FileEmbed::TYPE])
            ->addCall("registerEmbed", [YouTubeEmbed::class, YouTubeEmbed::TYPE])
            ->addCall("registerEmbed", [LinkEmbed::class, LinkEmbed::TYPE])
            ->addCall("registerEmbed", [MockHeadingProviderEmbed::class, MockHeadingProviderEmbed::TYPE])
            ->addCall("registerEmbed", [IFrameEmbed::class, IFrameEmbed::TYPE])
            ->addCall("registerEmbed", [QuoteEmbed::class, QuoteEmbed::TYPE]);
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
        $input = '[{"type":"p","children":[{"text":"good input"}]}]';
        $expected = $input;

        /** @var Rich2FormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        // Test NodeList Stringify
        $stringified = $output->getNodeList()->stringify();
        $this->assertInstanceOf(Rich2FormatParsed::class, $output);
        $this->assertInstanceOf(FormatParsedInterface::class, $output);
        $this->assertSame($expected, $output->getRawContent());
        $this->assertSame($expected, json_encode($output->getNodeList()));
        $this->assertEquals(new FormatText($input, "rich2"), $stringified);
    }

    /**
     * Test parse method with good input
     * @return void
     */
    public function testParseDOMAndStringify()
    {
        $input = '[{"type":"p","children":[{"text":"good input"}]}]';
        $expected = '[{"text":"good input"}]';

        /** @var NodeList $output */
        $output = $this->prepareFormatter()->parseDOM($input);
        $textFragments = $output->getFragments();
        $this->assertSame($expected, json_encode($textFragments));
        foreach ($textFragments as $textFragment) {
            $this->assertSame("good input", $textFragment->getInnerContent());
            $textFragment->setInnerContent("new Text");
            $this->assertSame("new Text", $textFragment->getInnerContent());
        }
    }

    /**
     * Test getTextNodes method with good input for translation. Should skip code block and at Mention.
     * @return void
     */
    public function testGetTextNodesSkipCodeBlock()
    {
        $input =
            '[{"type":"ul","children":[{"type":"li","children":[{"type":"lic","children":[{"text":"test"}]}]},' .
            '{"type":"li","children":[{"type":"lic","children":[{"text":"sdgsg"}]}]},' .
            '{"type":"li","children":[{"type":"lic","children":[{"text":"something"}]}]},' .
            '{"type":"li","children":[{"type":"lic","children":[{"text":"else"}]}]}]},' .
            '{"type":"code_block","children":[{"type":"code_line","children":[{"text":"Some Code Block","type":"code_line"}]}],' .
            '"lang":"ada"},{"type":"h4","children":[{"text":"Header"}]},{"type":"p","children":[{"text":""}]}]';
        $expected = [
            ["text" => "test"],
            ["text" => "sdgsg"],
            ["text" => "something"],
            ["text" => "else"],
            ["text" => "Header"],
            ["text" => ""],
        ];

        /** @var NodeList $output */
        $output = $this->prepareFormatter()->parseDOM($input);
        $textFragments = $output->getFragments();
        $this->assertSame(json_encode($expected), json_encode($textFragments));
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

    /**
     * Test rendering of embed links when `Garden.Format.DisableUrlEmbeds` is set to true.
     */
    public function testLinkEmbedRenderWithDisableUrlEmbeds()
    {
        $config = \Gdn::config();
        $config->set("Garden.Format.DisableUrlEmbeds", true, true, false);

        $input =
            '[{"children":[{"text":"Something here?"}],"type":"p"},{"type":"p","children":[{"text":"An inline link! "},' .
            '{"type":"a","url":"https://www.lipsum.com/","children":[{"text":"Hey"}]},{"text":"!"}]},' .
            '{"type":"p","children":[{"text":""},' .
            '{"type":"rich_embed_inline","dataSourceType":"url","url":"https://www.lipsum.com/",' .
            '"embedData":{"body":"Reference site about Lorem Ipsum.","url":"https://www.lipsum.com/",' .
            '"embedType":"link","name":"Lorem Ipsum","faviconUrl":"/favicon.ico"},' .
            '"children":[{"text":"https://www.lipsum.com/"}]},{"text":""}]}]';
        $expectedOutput = <<<HTML
<p>Something here?</p><p>An inline link! <a  href="https://www.lipsum.com/">Hey</a>!</p><p><a href="https://www.lipsum.com/" rel="nofollow noopener ugc">
    https://www.lipsum.com/
</a>
</p>
HTML;
        /** @var Rich2FormatParsed $output */
        $output = $this->prepareFormatter()->parse($input);
        $actualOutput = $output->getNodeList()->render();

        $this->assertSame($expectedOutput, $actualOutput);
    }
}
