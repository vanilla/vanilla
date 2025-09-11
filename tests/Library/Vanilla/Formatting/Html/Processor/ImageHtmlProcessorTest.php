<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Html\Processor;

use Gdn_Request;
use Vanilla\Formatting\Formats\MarkdownFormat;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\ImageHtmlProcessor;
use Vanilla\ImageSrcSet\ImageSrcSetService;
use VanillaTests\BootstrapTrait;
use VanillaTests\Fixtures\MockImageSrcSetProvider;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for verifying the basic behavior of the Image Html processor.
 */
class ImageHtmlProcessorTest extends VanillaTestCase
{
    use BootstrapTrait, HtmlNormalizeTrait, SetupTraitsTrait;

    /** @var FormatService */
    private $formatService;

    /** @var ImageHtmlProcessor */
    private $processor;

    /** @var Gdn_Request */
    private $request;

    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->setUpTestTraits();

        $this->container()->call(function (
            ImageHtmlProcessor $processor,
            FormatService $formatService,
            Gdn_Request $request
        ) {
            $this->processor = $processor;
            $this->formatService = $formatService;
            $this->request = $request;
        });

        // We'll be using the MockImageSrcSetProvider as these tests ImageSrcSetProvider.
        $srcSetProvider = new MockImageSrcSetProvider();
        $imageSrcSetService = new ImageSrcSetService();
        $imageSrcSetService->setImageResizeProvider($srcSetProvider);
        $this->container()->setInstance(ImageSrcSetService::class, $imageSrcSetService);
    }

    /**
     * Test generating `srcset`s on content's images.
     */
    public function testImagesSrcSet()
    {
        // Source content.
        $body = "There is an image here! <img src='/image/baconSlice.png' alt='This is a bacon slice'/>";
        // The expected output.
        $expectedOutput =
            "There is an image here! " .
            "<img " .
            'alt="This is a bacon slice" ' .
            'class="embedImage-img importedEmbed-img" ' .
            "src=/image/baconSlice.png " .
            'srcset="' .
            "https://loremflickr.com/g/10/600/baconSlice 10w, " .
            "https://loremflickr.com/g/300/600/baconSlice 300w, " .
            "https://loremflickr.com/g/800/600/baconSlice 800w, " .
            "https://loremflickr.com/g/1200/600/baconSlice 1200w, " .
            "https://loremflickr.com/g/1600/600/baconSlice 1600w, " .
            "/image/baconSlice.png" .
            '"' .
            ">";

        $document = new HtmlDocument(\Gdn::formatService()->renderHTML($body, WysiwygFormat::FORMAT_KEY));
        $actualOutput = $this->processor->processDocument($document)->getInnerHtml();

        // Confirm that the expected & actual results are equivalent.
        $this->assertHtmlStringEqualsHtmlString($expectedOutput, $actualOutput);
    }

    /**
     * Test rendering an empty image.
     *
     * @return void
     */
    public function testEmptyImage()
    {
        $expectedOutput =
            "404 image not found! <img src=\"\" alt=\"\" class=\"embedImage-img importedEmbed-img\"></img><br>";
        $body = "404 image not found! <img src=\"\" alt=\"\"><br>";
        $document = new HtmlDocument(\Gdn::formatService()->renderHTML($body, WysiwygFormat::FORMAT_KEY));
        $actualOutput = $this->processor->processDocument($document)->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expectedOutput, $actualOutput);
    }

    /**
     * Test that image height and width are preserved.
     */
    public function testImageSize()
    {
        $input = "<img src='https://somesite.com/some/url' height='50px' width='100px' />";
        $expected =
            "<img alt=image class=\"embedImage-img importedEmbed-img\" height=50px src=https://somesite.com/some/url srcset=\"https://loremflickr.com/g/10/600/url 10w, https://loremflickr.com/g/300/600/url 300w, https://loremflickr.com/g/800/600/url 800w, https://loremflickr.com/g/1200/600/url 1200w, https://loremflickr.com/g/1600/600/url 1600w, https://somesite.com/some/url\" style=\"height: 50px; width: 100px\" width=100px>";
        $document = new HtmlDocument(\Gdn::formatService()->renderHTML($input, WysiwygFormat::FORMAT_KEY));
        $actualOutput = $this->processor->processDocument($document)->getInnerHtml();
        $this->assertHtmlStringEqualsHtmlString($expected, $actualOutput);
    }
}
