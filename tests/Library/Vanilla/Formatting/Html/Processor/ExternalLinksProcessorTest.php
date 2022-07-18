<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Html\Processor;

use Gdn_Request;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\Html\Processor\ExternalLinksProcessor;
use VanillaTests\BootstrapTrait;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Tests for verifying the basic behavior of the external-links processor.
 */
class ExternalLinksProcessorTest extends VanillaTestCase
{
    use BootstrapTrait, HtmlNormalizeTrait, SetupTraitsTrait;

    /** @var FormatService */
    private $formatService;

    /** @var ExternalLinksProcessor */
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
            ExternalLinksProcessor $processor,
            FormatService $formatService,
            Gdn_Request $request
        ) {
            $this->processor = $processor;
            $this->formatService = $formatService;
            $this->request = $request;
        });
    }

    /**
     * Test external links processing for external links.
     */
    public function testExternalLinksProcessingExternal(): void
    {
        $url = "https://example.com";
        $content = json_encode([
            [
                "attributes" => ["link" => $url],
                "insert" => $url,
            ],
            ["insert" => "\n"],
        ]);

        $document = new HtmlDocument($this->formatService->renderHTML($content, RichFormat::FORMAT_KEY));
        $actual = $this->processor->processDocument($document)->getInnerHtml();

        $expectedHref =
            "http://" .
            \Gdn::request()->getHost() .
            htmlspecialchars(
                $this->request->url(
                    "/home/leaving?" .
                        http_build_query([
                            "allowTrusted" => 1,
                            "target" => $url,
                        ])
                )
            );
        $expected = <<<HTML
<p>
    <a href="$expectedHref" rel="nofollow noreferrer ugc">$url</a>
</p>
HTML;

        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test external links processing for internal links.
     */
    public function testExternalLinksProcessingInternal(): void
    {
        $content = json_encode([
            [
                "attributes" => ["link" => "http://vanilla.test/discussions"],
                "insert" => "Discussions",
            ],
            ["insert" => "\n"],
        ]);

        $document = new HtmlDocument($this->formatService->renderHTML($content, RichFormat::FORMAT_KEY));
        $actual = $this->processor->processDocument($document)->getInnerHtml();

        $expected = <<<HTML
<p>
    <a href="http://vanilla.test/discussions" rel="nofollow noreferrer ugc">Discussions</a>
</p>
HTML;
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Verify ability to disable rewriting external link URLs through the leaving page.
     */
    public function testDisableWarnLeaving(): void
    {
        $this->processor->setWarnLeaving(false);
        $expected = "https://example.com";
        $html = /** @lang HTML */ <<<HTML
<p>
    <a href="{$expected}" id="a1">foo</a>
</p>
HTML;
        $document = new HtmlDocument($html);
        $this->processor->processDocument($document);
        $actual = $document
            ->getDom()
            ->getElementById("a1")
            ->getAttribute("href");
        $this->assertSame($expected, $actual);
    }
}
