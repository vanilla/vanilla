<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\TextFragments;

use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\TextFragmentCollectionInterface;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;
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
    public function testBasic(): void {
        $text = /** @lang JSON */
        <<<'JSON'
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
    "insert": ".\nQuote "
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
    "insert": "\n"
  },
  {
    "insert": "This is a test.\n"
  }
]
JSON;

        $dom = $this->formatter->parseDOM($text);
        $fragments = $dom->getFragments();

        foreach ($fragments as $i => $fragment) {
            $fragment->setInnerContent("Line <b>$i</b>");
        }

        $expected = /** @lang HTML */
        <<<'HTML'
<p>Line <strong>0</strong></p>
<div class=blockquote>
    <div class=blockquote-content>
        <p class=blockquote-line>Line <strong>1</strong></p>
    </div>
</div>
<p>Line <strong>2</strong></p>
HTML;

        $actual = $dom->renderHTML();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Establish canonical handling of rich text fragment generation.
     */
    public function testCanonical(): void {
        $text = /** @lang JSON */
        <<<'JSON'
[
  {
    "insert": "Heading"
  },
  {
    "attributes": {
      "header": {
        "level": 2,
        "ref": ""
      }
    },
    "insert": "\n"
  },
  {
    "attributes": {
      "bold": true
    },
    "insert": "bold"
  },
  {
    "insert": " "
  },
  {
    "attributes": {
      "italic": true
    },
    "insert": "italic"
  },
  {
    "insert": " "
  },
  {
    "attributes": {
      "strike": true
    },
    "insert": "strike"
  },
  {
    "insert": " "
  },
  {
    "attributes": {
      "code": true
    },
    "insert": "code"
  },
  {
    "insert": " "
  },
  {
    "attributes": {
      "link": "https:\\/\\/example.com"
    },
    "insert": "link"
  },
  {
    "insert": "\nQuote 1"
  },
  {
    "attributes": {
      "blockquote-line": true
    },
    "insert": "\n"
  },
  {
    "insert": "Quote 2"
  },
  {
    "attributes": {
      "blockquote-line": true
    },
    "insert": "\n"
  },
  {
    "insert": "Code Block 1"
  },
  {
    "attributes": {
      "code-block": true
    },
    "insert": "\n"
  },
  {
    "insert": "Code Block 2"
  },
  {
    "attributes": {
      "code-block": true
    },
    "insert": "\n"
  },
  {
    "insert": "Spoiler 1"
  },
  {
    "attributes": {
      "spoiler-line": true
    },
    "insert": "\n"
  },
  {
    "insert": "Spoiler 2"
  },
  {
    "attributes": {
      "spoiler-line": true
    },
    "insert": "\n"
  }
]
JSON;

        $dom = $this->formatter->parseDOM($text);
        $fragments = $dom->getFragments();
        $debug = $this->debugFragments($fragments);

        foreach ($fragments as $i => $fragment) {
            $content = $fragment->getInnerContent();
            if ($fragment->getFragmentType() === TextFragmentType::HTML) {
                $fragment->setInnerContent("Line <b>$i</b>");
            }
        }

        // TODO: Fix bug in code block when we fix our HtmlNormalizeTrait.
        $expected = /** @lang HTML */
        <<<'HTML'
<h2>Line <strong>0</strong></h2>
<p>Line <strong>1</strong></p>
<div class=blockquote>
    <div class=blockquote-content>
        <p class=blockquote-line>Line <strong>2</strong></p>
        <p class=blockquote-line>Line <strong>3</strong></p>
    </div>
</div>
<pre class="code codeBlock" spellcheck=false tabindex=0>Code Block 1Code Block 2</pre>
<div class=spoiler>
    <div class=spoiler-buttonContainer contenteditable=false>
    <button class="button-spoiler iconButton js-toggleSpoiler" title="Toggle Spoiler">
        <span class=spoiler-warning>
            <span class=spoiler-warningMain>
                <SVG />
                <span class=spoiler-warningLabel> Spoiler Warning </span>
            </span>
            <span class=spoiler-chevron>
                <SVG />
                <SVG />
            </span>
        </span>
    </button>
    </div>
    <div class=spoiler-content>
        <p class=spoiler-line>Line <strong>6</strong></p>
        <p class=spoiler-line>Line <strong>7</strong></p>
    </div>
</div>
HTML;

        $actual = $dom->renderHTML();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Test inline formatting cases.
     *
     * @param string $html
     * @param ?string $expected
     * @dataProvider provideInlineFormattingTests
     */
    public function testInlineFormatting(string $html, string $expected = null): void {
        $text = /** @lang JSON */
        <<<'JSON'
[
  {
    "insert": "test\n"
  }
]
JSON;

        $dom = $this->formatter->parseDOM($text);
        $debug = $dom->renderHTML();
        $fragments = $dom->getFragments();

        $this->assertCount(1, $fragments);
        $fragments[0]->setInnerContent($html);

        $expected = $expected ?? $html;
        $expected = /** @lang HTML */
        <<<HTML
<p>$expected</p>
HTML;

        $actual = $dom->renderHTML();
        $this->assertHtmlStringEqualsHtmlString($expected, $actual);
    }

    /**
     * Provide tests for inline HTML parsing.
     *
     * @return array<string, string[]>
     */
    public function provideInlineFormattingTests(): array {
        $r = [
            'bold italic' => ['hey <strong>bold</strong> and <em>italic</em>.'],
            'b' => ['<b>foo</b>', '<strong>foo</strong>'],
            'i' => ['<i>foo</i>', '<em>foo</em>'],
            'link' => ['<a href="http://example.com">link</a>', '<a href="http://example.com" rel="nofollow noreferrer ugc">link</a>'],
            'strike' => ['<s>strike</s>'],
            'code' => ['<code>foo</code>', '<code class="code codeInline" spellcheck="false" tabindex="0">foo</code>'],
            'nested' => ['<em><s>foo</s></em>'],
            'nested 2' => ['<em><s>foo</s> bar</em>'],
            'nested 3' => ['<em><s>foo</s> <strong>bar</strong></em>', '<em><s>foo</s> </em><strong><em>bar</em></strong>'],

        ];

        return $r;
    }

    /**
     * Rich nested lists should properly break their items up.
     */
    public function testRichLists(): void {
        $json = /** @lang json */
            <<<'JSON'
[
  {
    "insert": "foo 1"
  },
  {
    "attributes": {
      "list": {
        "depth": 0,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 1.1"
  },
  {
    "attributes": {
      "list": {
        "depth": 1,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 2"
  },
  {
    "attributes": {
      "list": {
        "depth": 0,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 2.1"
  },
  {
    "attributes": {
      "list": {
        "depth": 1,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 2.1.1"
  },
  {
    "attributes": {
      "list": {
        "depth": 2,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 2.2"
  },
  {
    "attributes": {
      "list": {
        "depth": 1,
        "type": "bullet"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 3"
  },
  {
    "attributes": {
      "list": {
        "depth": 0,
        "type": "ordered"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 3.1"
  },
  {
    "attributes": {
      "list": {
        "depth": 1,
        "type": "ordered"
      }
    },
    "insert": "\n"
  },
  {
    "insert": "foo 4"
  },
  {
    "attributes": {
      "list": {
        "depth": 0,
        "type": "ordered"
      }
    },
    "insert": "\n"
  }
]
JSON;
            $this->assertFooSmoke($json);
    }

    /**
     * A basic assertion that replaces the word "foo" with "bar" through the rich DOM and then makes sure the changes serialize.
     *
     * @param string $rich A rich JSON string to test.
     */
    private function assertFooSmoke(string $rich): void {
        $expected = json_decode($rich, true);
        $dom = $this->formatter->parseDOM($rich);

        $stringified = json_decode($dom->stringify()->text, true);
        $this->assertArraySubsetRecursive($expected, $stringified);

        $fn = function (TextFragmentInterface $text) {
            $content = $text->getInnerContent();
            $new = str_replace('foo', 'bar', $content);
            $text->setInnerContent($new);
        };

        $fragments = $dom->getFragments();
        $this->debugFragments($fragments);
        foreach ($fragments as $fragment) {
            if ($fragment instanceof TextFragmentInterface) {
                $fn($fragment);
            } elseif ($fragment instanceof TextFragmentCollectionInterface) {
                foreach ($fragment->getFragments() as $subFragment) {
                    $fn($subFragment);
                }
            }
        }

        array_walk_recursive($expected, function (&$str) {
            if (is_string($str)) {
                $str = str_replace('foo', 'bar', $str);
            }
        });

        $actual = json_decode($dom->stringify()->text, true);
        self::assertArraySubsetRecursive($expected, $actual);
    }

    /**
     * List all of the fragment content in an array to aid debugging.
     *
     * @param array $fragments
     * @return array
     */
    private function debugFragments(array $fragments): array {
        $debug = array_map(function (TextFragmentInterface $f) {
            return $f->getInnerContent();
        }, $fragments);
        return $debug;
    }
}
