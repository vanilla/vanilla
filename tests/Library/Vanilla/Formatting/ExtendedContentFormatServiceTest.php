<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting;

use Garden\Container\Container;
use PHPUnit\Framework\TestCase;
use Vanilla\EmbeddedContent\EmbeddedContentException;
use Vanilla\EmbeddedContent\Embeds\IFrameEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\ExtendedContentFormatService;
use Vanilla\Formatting\Formats\BBCodeFormat;
use Vanilla\Formatting\Formats\HtmlFormat;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Formatting\FormatService;
use VanillaTests\BootstrapTrait;
use VanillaTests\ExpectExceptionTrait;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the extended content.
 */
class ExtendedContentFormatServiceTest extends TestCase {

    use HtmlNormalizeTrait;
    use ExpectExceptionTrait;
    use BootstrapTrait;

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        self::container()->rule(EmbedService::class)
            ->addCall('registerEmbed', [IFrameEmbed::class, IFrameEmbed::TYPE]);
    }

    /**
     * @return FormatService
     */
    private function getFormatter(): FormatService {
        return self::container()->get(FormatService::class);
    }

    /**
     * @return ExtendedContentFormatService
     */
    private function getExtendedFormatter(): ExtendedContentFormatService {
        return self::container()->get(ExtendedContentFormatService::class);
    }

    /**
     * Test iframes in html based formats.
     *
     * @param string $format The format to run with.
     *
     * @dataProvider provideFrameHtmlFormats
     */
    public function testHtmlFormats(string $format) {
        $in = '<iframe src="http://example.com"></iframe>'
            . '<video width="320" height="240" controls="" autoplay="">'
            . '<source src="https://file-examples-com.github.io/uploads/2017/04/file_example_MP4_480_1_5MG.mp4" type="video/mp4"></source>'
            . '</video>';

        // Stripped out in the normal formatter. Iframes aren't allowed.
        $actualNormal = trim($this->getFormatter()->renderHTML($in, $format));
        $actualNormal = str_replace(["<p>", "</p>"], "", $actualNormal); // There may be an empty newline.
        $this->assertEquals("", $actualNormal);

        // Left alone in the extended formatter.
        $actualExtended = trim($this->getExtendedFormatter()->renderHTML($in, $format));
        $actualExtended = str_replace(["<p>", "</p>"], "", $actualExtended); // There may be an empty newline.
        $this->assertHtmlStringEqualsHtmlString($in, $actualExtended);
    }

    /**
     * @return array
     */
    public function provideFrameHtmlFormats() {
        return [
            [HtmlFormat::FORMAT_KEY],
            [WysiwygFormat::FORMAT_KEY],
            [MarkdownFormat::FORMAT_KEY],
        ];
    }

    /**
     * Test embeds in the rich editor with iframes.
     */
    public function testRichFrameEmbeds() {
        $ops = [[
            "insert" => [
                "embed-external" => [
                    'data' => [
                        "url" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                        "embedType" => "iframe",
                        "height" => 630,
                        "width" => 1200,
                    ],
                ],
            ],
        ]];
        $in = json_encode($ops);

        $normal = $this->getFormatter()->renderHTML($in, RichFormat::FORMAT_KEY);
        $extended = $this->getExtendedFormatter()->renderHTML($in, RichFormat::FORMAT_KEY);

        $this->assertTrue(str_contains($normal, "There was an error"));
        $this->assertFalse(str_contains($extended, "There was an error"));
    }
}
