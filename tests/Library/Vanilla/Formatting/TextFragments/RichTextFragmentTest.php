<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\TextFragments;

use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Tests for rich text splitting and fragments.
 */
class RichTextFragmentTest extends BootstrapTestCase {
    use HtmlNormalizeTrait;

    /**
     * @var RichFormat
     */
    private $formatter;

    /**
     * {@inheritDoc}
     */
    public function setUp(): void {
        parent::setUp();
        $this->container()->call(function (FormatService $formatService) {
            $this->formatter = $formatService->getFormatter(RichFormat::FORMAT_KEY);
        });
    }

    /**
     * Smoke test the canonical rich string.
     */
    public function testCanonical(): void {
        $text = <<<JSON
[
  {
    "insert": "Hello "
  },
  {
    "attributes": {
      "italic": true
    },
    "insert": "world"
  },
  {
    "insert": ".\\nQuote "
  },
  {
    "attributes": {
      "bold": true
    },
    "insert": "me"
  },
  {
    "insert": "."
  },
  {
    "attributes": {
      "blockquote-line": true
    },
    "insert": "\\n"
  },
  {
    "insert": "This is a test.\\n"
  }
]
JSON;

        $dom = $this->formatter->parseDOM($text);
        $fragments = $dom->getFragments();

        foreach ($fragments as $i => $fragment) {
            $fragment->setInnerContent("Line <b>$i</b>");
        }

        $expected = <<<HTML
<p>Line<strong>0</strong></p>
<div class=blockquote>
    <div class=blockquote-content>
        <p class=blockquote-line>Line<strong>1</strong></p>
    </div>
</div>
<p>Line<strong>2</strong></p>
HTML;

        $actual = $dom->renderHTML();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }
}
