<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\TextFragments;

use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Formatting\TextFragmentCollectionInterface;
use Vanilla\Formatting\TextFragmentInterface;
use Vanilla\Formatting\TextFragmentType;
use VanillaTests\BootstrapTestCase;
use VanillaTests\Fixtures\Html\TestHtmlDocument;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;

/**
 * Tests for rich text splitting and fragments.
 */
class RichTextFragmentTest extends BootstrapTestCase
{
    use HtmlNormalizeTrait;

    /**
     * @var RichFormat
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
            $this->formatter = $formatService->getFormatter(RichFormat::FORMAT_KEY);
        });
    }

    /**
     * A basic assertion that replaces the word "foo" with "bar" through the rich DOM and then makes sure the changes serialize.
     *
     * @param string $rich A rich JSON string to test.
     * @param int $expectedReplacements The expected total of replacements to be made.
     */
    private function assertFooSmoke(string $rich, int $expectedReplacements): void
    {
        $expected = json_decode($rich, true);
        $dom = $this->formatter->parseDOM($rich);

        $stringified = json_decode($dom->stringify()->text, true);
        $this->assertArraySubsetRecursive($expected, $stringified);

        $actualReplacements = 0;
        $fn = function (TextFragmentInterface $text) use (&$actualReplacements) {
            $content = $text->getInnerContent();
            $replacementCount = 0;
            $new = str_replace("foo", "bar", $content, $replacementCount);
            $actualReplacements += $replacementCount;
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
                $str = str_replace("foo", "bar", $str);
            }
        });

        $actual = json_decode($dom->stringify()->text, true);
        self::assertArraySubsetRecursive($expected, $actual);
        $this->assertSame($expectedReplacements, $actualReplacements);
    }

    /**
     * List all of the fragment content in an array to aid debugging.
     *
     * @param array $fragments
     * @return array
     */
    private function debugFragments(array $fragments): array
    {
        $debug = array_map(function (TextFragmentInterface $f) {
            return $f->getInnerContent();
        }, $fragments);
        return $debug;
    }

    /**
     * Smoke test the canonical rich string.
     */
    public function testBasic(): void
    {
        $text =
            /** @lang JSON */
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

        $expected =
            /** @lang HTML */
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
    public function testCanonical(): void
    {
        $text =
            /** @lang JSON */
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
        $expected =
            /** @lang HTML */
            <<<'HTML'
<h2>Line <strong>0</strong></h2>
<p>Line <strong>1</strong></p>
<div class=blockquote>
    <div class=blockquote-content>
        <p class=blockquote-line>Line <strong>2</strong></p>
        <p class=blockquote-line>Line <strong>3</strong></p>
    </div>
</div>
<pre class="code codeBlock" spellcheck=false tabindex=0>Code Block 1
Code Block 2
</pre>
<div class=spoiler>
    <div class=spoiler-buttonContainer contenteditable=false>
    <button class="button-spoiler iconButton js-toggleSpoiler" title="Toggle Spoiler">
        <span class=spoiler-warning>
            <span class=spoiler-warningMain>
                <svg class=spoiler-icon viewbox="0 0 24 24">
                    <title>Spoiler</title>
                    <path d="M11.469 15.47c-2.795-.313-4.73-3.017-4.06-5.8l4.06 5.8zM12 16.611a9.65 
                9.65 0 0 1-8.333-4.722 9.569 9.569 0 0 1 3.067-3.183L5.778 7.34a11.235 11.235 0 0 0-3.547 3.703 1.667 
                1.667 0 0 0 0 1.692A11.318 11.318 0 0 0 12 18.278c.46 0 .92-.028 1.377-.082l-1.112-1.589a9.867 9.867 
                0 0 1-.265.004zm9.77-3.876a11.267 11.267 0 0 1-4.985 4.496l1.67 2.387a.417.417 0 0 1-.102.58l-.72.504a.417.417 
                0 0 1-.58-.102L5.545 4.16a.417.417 0 0 1 .102-.58l.72-.505a.417.417 0 0 1 .58.103l1.928 2.754A11.453 11.453 0 
                0 1 12 5.5c4.162 0 7.812 2.222 9.77 5.543.307.522.307 1.17 0 1.692zm-1.437-.846A9.638 9.638 0 0 0 12.828 
                7.2a1.944 1.944 0 1 0 3.339 1.354 4.722 4.722 0 0 1-1.283 5.962l.927 1.324a9.602 9.602 0 0 0 4.522-3.952z" fill=currentColor>                
</path>
</svg>
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
    public function testInlineFormatting(string $html, string $expected = null): void
    {
        $text =
            /** @lang JSON */
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
        $expected =
            /** @lang HTML */
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
    public function provideInlineFormattingTests(): array
    {
        $r = [
            "bold italic" => ["hey <strong>bold</strong> and <em>italic</em>."],
            "b" => ["<b>foo</b>", "<strong>foo</strong>"],
            "i" => ["<i>foo</i>", "<em>foo</em>"],
            "link" => [
                '<a href="http://example.com">link</a>',
                '<a href="http://example.com" rel="nofollow noopener ugc">link</a>',
            ],
            "strike" => ["<s>strike</s>"],
            "code" => ["<code>foo</code>", '<code class="code codeInline" spellcheck="false" tabindex="0">foo</code>'],
            "nested" => ["<em><s>foo</s></em>"],
            "nested 2" => ["<em><s>foo</s> bar</em>"],
            "nested 3" => [
                "<em><s>foo</s> <strong>bar</strong></em>",
                "<em><s>foo</s> </em><strong><em>bar</em></strong>",
            ],
        ];

        return $r;
    }

    /**
     * Rich nested lists should properly break their items up.
     */
    public function testRichLists(): void
    {
        $json =
            /** @lang json */
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
        $this->assertFooSmoke($json, 9);
    }

    /**
     * Verify ability to modify text attributes of an image.
     */
    public function testImageFragment(): void
    {
        $text = $this->getExampleWithImage();
        $dom = $this->formatter->parseDOM($text);

        $fragments = $dom->getFragments();

        $expectedName = __FUNCTION__ . "-name";
        $expectedUrl = "https://example.com/" . strtolower(__FUNCTION__);

        $fragment = $fragments[0];
        $fragment["name"]->setInnerContent($expectedName);
        $fragment["url"]->setInnerContent($expectedUrl);

        // Re-run everything through formatting to verify potential changes persist.
        $actual = $this->formatter->parseImages($dom->stringify()->text);
        $this->assertSame($expectedName, $actual[0]["alt"]);
        $this->assertSame($expectedUrl, $actual[0]["url"]);
    }

    /**
     * Verify ability to modify text attributes of an image.
     */
    public function testEmbedFragment(): void
    {
        $text = $this->getExampleWithEmbed();
        $dom = $this->formatter->parseDOM($text);

        $fragments = $dom->getFragments();

        $expectedName = __FUNCTION__ . "-name";
        $expectedBody = __FUNCTION__ . "-body";
        $expectedUrl = "https://example.com/" . strtolower(__FUNCTION__);

        $fragment = $fragments[0];
        $fragment["body"]->setInnerContent($expectedBody);
        $fragment["name"]->setInnerContent($expectedName);
        $fragment["url"]->setInnerContent($expectedUrl);

        // Re-run everything through formatting to verify potential changes persist.
        $doc = new TestHtmlDocument($this->formatter->renderHTML($dom->stringify()->text));
        $doc->assertCssSelectorExists("a[href=\"$expectedUrl\"]");

        $actual = json_decode(
            $doc
                ->queryCssSelector("div.js-embed")
                ->item(0)
                ->getAttribute("data-embedjson"),
            true
        );
        $this->assertSame($expectedBody, $actual["body"]);
        $this->assertSame($expectedName, $actual["name"]);
        $this->assertSame($expectedUrl, $actual["url"]);
    }

    /**
     * Get an example rich post with an image.
     *
     * @return string
     */
    private function getExampleWithImage(): string
    {
        return /** @lang JSON */ <<<'JSON'
[
  {
    "insert": {
      "embed-external": {
        "data": {
          "url": "https://example.com/foo.png",
          "name": "foo text here",
          "type": "image/png",
          "size": 26734,
          "width": 290,
          "height": 290,
          "displaySize": "medium",
          "float": "none",
          "mediaID": 136016,
          "dateInserted": "2021-04-08T18:24:13+00:00",
          "insertUserID": 1,
          "foreignType": "embed",
          "foreignID": 1,
          "embedType": "image"
        },
        "loaderData": {
          "type": "image"
        }
      }
    }
  },
  {
    "insert": "\n"
  }
]
JSON;
    }

    /**
     * Get an example rich post with an external site embed.
     *
     * @return string
     */
    private function getExampleWithEmbed(): string
    {
        return /** @lang JSON */ <<<'RICH'
[
  {
    "insert": {
      "embed-external": {
        "data": {
          "body": "I am an example of an external embed.",
          "photoUrl": "https:\/\/example.com\/photo.jpg",
          "url": "https:\/\/example.com\/embed.htm",
          "embedType": "link",
          "name": "Hello world!"
        },
        "loaderData": {
          "type": "link",
          "link": "https:\/\/example.com\/embed.htm"
        }
      }
    }
  },
  {
    "insert": "\n"
  }
]
RICH;
    }
}
