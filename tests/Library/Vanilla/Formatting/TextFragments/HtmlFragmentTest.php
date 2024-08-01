<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\TextFragments;

use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\Html\HtmlDocument;
use Vanilla\Formatting\TextFragmentType;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Text fragment tests for the WYSIWYG format.
 */
class HtmlFragmentTest extends BootstrapTestCase
{
    use HtmlNormalizeTrait;

    /**
     * @var WysiwygFormat
     */
    private $formatter;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->container()
            ->rule(EmbedService::class)
            ->addCall("addCoreEmbeds");
        $this->container()->call(function (FormatService $formatService) {
            $this->formatter = $formatService->getFormatter(WysiwygFormat::FORMAT_KEY);
        });
    }

    /**
     * Test some basic HTML fragment use cases.
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideBasicTests
     */
    public function testBasic(string $input, string $expected): void
    {
        /** @var HtmlDocument $dom */
        $dom = $this->formatter->parseDOM($input);
        $this->assertBasicDom($dom, $input, $expected);
    }

    /**
     * Run basic tests on raw HTML that doesn't go through the format pipeline.
     *
     * @param string $input
     * @param string $expected
     * @dataProvider provideBasicRawTests
     */
    public function testBasicRaw(string $input, string $expected): void
    {
        $dom = new HtmlDocument($input);
        $this->assertBasicDom($dom, $input, $expected);
    }

    /**
     * Provide basic tests from the fixtures directory.
     *
     * @return array
     */
    public function provideBasicTests(): array
    {
        $r = static::provideFileTests(PATH_FIXTURES . "/fragments/html", "-input.html", "-expected.html");
        return $r;
    }

    /**
     * Provide basic tests from the fixtures directory.
     *
     * @return array
     */
    public function provideBasicRawTests(): array
    {
        $r = static::provideFileTests(PATH_FIXTURES . "/fragments/html-raw", "-input.html", "-expected.html");
        return $r;
    }

    /**
     * Do some basic DOM manipulation with HTML fragments and then assert against an expected output.
     *
     * @param HtmlDocument $dom
     * @param string $input
     * @param string $expected
     */
    private function assertBasicDom(HtmlDocument $dom, string $input, string $expected): void
    {
        $string = $dom->stringify();
        $this->assertHtmlStringEqualsHtmlString($input, $string->text, "Sanity test for Html string failed.");

        $fragments = $dom->getFragments();

        foreach ($fragments as $i => $fragment) {
            switch ($fragment->getFragmentType()) {
                case TextFragmentType::HTML:
                    $fragment->setInnerContent("Line <b>$i</b>");
                    break;
                case TextFragmentType::TEXT:
                    $fragment->setInnerContent("Line $i");
                    break;
                case TextFragmentType::CODE:
                    $fragment->setInnerContent("code($i);");
                    break;
            }
        }

        $actual = $dom->stringify();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual->text);
    }
}
