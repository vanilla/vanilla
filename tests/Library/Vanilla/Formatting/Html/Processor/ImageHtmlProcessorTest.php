<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Html\Processor;

use Gdn_Request;
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
     * @inheritDoc
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
     * Test generating srcsets on content's images.
     */
    public function testImagesSrcSet()
    {
        // Source content.
        $body = "There is an image here! <img src='/image/baconSlice.png' />";
        // The expected output.
        $expectedOutput =
            "There is an image here! " .
            "<img " .
            'src="/image/baconSlice.png" ' .
            'alt="image" ' .
            'class="embedImage-img importedEmbed-img" ' .
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

        // Confirm that the expected & actual results are euivalent.
        $this->assertHtmlStringEqualsHtmlString($expectedOutput, $actualOutput);
    }
}
