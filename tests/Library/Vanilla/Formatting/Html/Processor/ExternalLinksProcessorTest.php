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
            "https://" .
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
    <a href="$expectedHref" rel="nofollow noopener ugc">$url</a>
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
                "attributes" => ["link" => "https://vanilla.test/discussions"],
                "insert" => "Discussions",
            ],
            ["insert" => "\n"],
        ]);

        $document = new HtmlDocument($this->formatService->renderHTML($content, RichFormat::FORMAT_KEY));
        $actual = $this->processor->processDocument($document)->getInnerHtml();

        $expected = <<<HTML
<p>
    <a href="https://vanilla.test/discussions" rel="nofollow noopener ugc">Discussions</a>
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

    /**
     * Test ExternalLinksProcessor with js-embed components. The url within the `data-embedjson` attributes should be
     * changed in the same manner the anchor's href attribute would be.
     *
     * @param string $inputHtml
     * @param string $expectedOutputHtml
     * @dataProvider provideJsEmbedActualAndExpectedHtml
     */
    public function testJsEmbedRedirections($inputHtml, $expectedOutputHtml)
    {
        $this->processor->setWarnLeaving(true);
        $this->runWithConfig(["Garden.Format.WarnLeaving" => true], function () use ($inputHtml, $expectedOutputHtml) {
            $document = new HtmlDocument($inputHtml);
            $this->processor->processDocument($document);
            $actual = $document->renderHTML();
            $this->assertSame($expectedOutputHtml, $actual);
        });
    }

    /**
     * Provide pairs of HTML pre & post ExternalLinksProcessor processing for `testJsEmbedRedirections()`.
     *
     * @return array Returns a data provider array.
     */
    public function provideJsEmbedActualAndExpectedHtml(): array
    {
        return [
            "Using a span" => [
                /** @lang HTML */ <<<HTML
<p>
    <span class='js-embed embedResponsive inlineEmbed' data-embedjson='{"body":"This is the body","photoUrl":"https:\/\/example.com\/image.png","url":"https:\/\/example.com","embedType":"link","name":"Some name","faviconUrl":"https:\/\/example.com\/favicon.png","embedStyle":"rich_embed_inline"}'>
        <a href='https://example.com' rel='nofollow noopener ugc'>
            https://example.com
        </a>
    </span>
</p>
HTML
                ,
                /** @lang HTML */ <<<HTML
<p>
    <span class="js-embed embedResponsive inlineEmbed" data-embedjson="{&quot;body&quot;:&quot;This is the body&quot;,&quot;photoUrl&quot;:&quot;https:\/\/example.com\/image.png&quot;,&quot;url&quot;:&quot;https:\/\/vanilla.test\/externallinksprocessortest\/home\/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fexample.com&quot;,&quot;embedType&quot;:&quot;link&quot;,&quot;name&quot;:&quot;Some name&quot;,&quot;faviconUrl&quot;:&quot;https:\/\/example.com\/favicon.png&quot;,&quot;embedStyle&quot;:&quot;rich_embed_inline&quot;}">
        <a href="https://vanilla.test/externallinksprocessortest/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fexample.com" rel="nofollow noopener ugc">
            https://example.com
        </a>
    </span>
</p>
HTML
            ,
            ],
            "Using a div" => [
                /** @lang HTML */ <<<HTML
<div class='js-embed embedResponsive' data-embedjson='{"body":"This is the body","photoUrl":"https:\/\/example.com\/image.png","url":"https:\/\/example.com","embedType":"link","name":"Some name","faviconUrl":"https:\/\/example.com\/favicon.png"}'>
    <a href='https://example.com' rel='nofollow noopener ugc'>
        https://example.com
    </a>
</div>
HTML
                ,
                /** @lang HTML */ <<<HTML
<div class="js-embed embedResponsive" data-embedjson="{&quot;body&quot;:&quot;This is the body&quot;,&quot;photoUrl&quot;:&quot;https:\/\/example.com\/image.png&quot;,&quot;url&quot;:&quot;https:\/\/vanilla.test\/externallinksprocessortest\/home\/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fexample.com&quot;,&quot;embedType&quot;:&quot;link&quot;,&quot;name&quot;:&quot;Some name&quot;,&quot;faviconUrl&quot;:&quot;https:\/\/example.com\/favicon.png&quot;}">
    <a href="https://vanilla.test/externallinksprocessortest/home/leaving?allowTrusted=1&amp;target=https%3A%2F%2Fexample.com" rel="nofollow noopener ugc">
        https://example.com
    </a>
</div>
HTML
            ,
            ],
        ];
    }
}
