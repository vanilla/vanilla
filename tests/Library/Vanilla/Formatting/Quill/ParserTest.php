<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingBlot;
use Vanilla\Formatting\Quill\Blots\Lines\BlockquoteLineBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ListLineBlot;
use Vanilla\Formatting\Quill\Blots\Lines\SpoilerLineBlot;
use Vanilla\Formatting\Quill\Blots\NullBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Parser;

class ParserTest extends SharedBootstrapTestCase {

    /**
     * @param array $ops
     * @param array $expected
     */
    public function assertParseResults(array $ops, array $expected) {
        $error = "The parser failed to instantiate through the container";
        try {
            $parser = \Gdn::getContainer()->get(Parser::class);
        } catch (\Exception $e) {
            $this->fail($error);
        }

        if ($parser) {
            $actual = $parser->parseIntoTestData($ops);
            $this->assertSame($expected, $actual);
        } else {
            $this->fail($error);
        }
    }

    public function testParseExternalBlot() {
        $ops = [[
            "insert" => [
                "embed-external" => [
                    "url" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "type" => "image",
                    "name" => null,
                    "body" => null,
                    "photoUrl" => "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
                    "height" => 630,
                    "width" => 1200,
                    "attributes" => [],
                ],
            ],
        ]];

        $results = [[["class" => ExternalBlot::class, "content" => ""]]];
        $this->assertParseResults($ops, $results);
    }

    /**
     * A single newline should result in an empty rendered post.
     *
     * A plain newline represents the editor's default empty state.
     */
    public function testSingleNewline() {
        $ops = [["insert" => "\n"]];
        $result = [];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of normal text.
     */
    public function testNormalText() {
        $ops = [["insert" => "SomeText\n"]];
        $result = [[["class" => TextBlot::class, "content" => "SomeText"]]];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of newlines.
     */
    public function testNewlineParsing() {
        $ops = [["insert" => "Sometext\n\n\nAfter3lines\n\n"]];
        $result = [
            [["class" => TextBlot::class, "content" => "Sometext"]],
            [["class" => TextBlot::class, "content" => "\n"]],
            [["class" => TextBlot::class, "content" => "\n"]],
            [["class" => TextBlot::class, "content" => "After3lines"]],
            [["class" => TextBlot::class, "content" => "\n"]],
        ];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of block level blots. Lines, headings, etc.
     */
    public function testBlockBlotNewlines() {
        $this->assertCorrectLineBlotParsing("list", "bullet", ListLineBlot::class);
        $this->assertCorrectLineBlotParsing("list", "ordered", ListLineBlot::class);
        $this->assertCorrectLineBlotParsing("spoiler-line", true, SpoilerLineBlot::class);
        $this->assertCorrectLineBlotParsing("blockquote-line", true, BlockquoteLineBlot::class);
        $this->assertCorrectNonLineBlockBlockParsing("header", 2, HeadingBlot::class);
        $this->assertCorrectNonLineBlockBlockParsing("header", 3, HeadingBlot::class);
    }

    /**
     * Assert that a particular line blot class parses correctly.
     *
     * @param string $attrKey The attribute key for the operation.
     * @param $attrValue The attribute value for the operation.
     * @param string $className The Name of the line blot class.
     */
    private function assertCorrectLineBlotParsing(string $attrKey, $attrValue, string $className) {
        $ops = [
            ["insert" => "Line 1"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n\n"],
            ["insert" => "Line 3"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n\n\n\n"],
            ["insert" => "Line 7"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n"],
            ["insert" => "Normal Text\nSome other text"],
        ];
        $result = [
            [
                ["class" => $className, "content" => "Line 1"],
                ["class" => $className, "content" => "Line 3"],
                ["class" => $className, "content" => "Line 7"],
            ],
            [["class" => TextBlot::class, "content" => "Normal Text"]],
            [["class" => TextBlot::class, "content" => "Some other text"]],
        ];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Assert that a particular block blot (not a line blot) class parses correctly.
     *
     * Ex. Heading.
     *
     * @param string $attrKey The attribute key for the operation.
     * @param $attrValue The attribute value for the operation.
     * @param string $className The Name of the line blot class.
     */
    private function assertCorrectNonLineBlockBlockParsing(string $attrKey, $attrValue, string $className) {
        $ops = [
            ["insert" => "Line 1"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n\n"],
            ["insert" => "Line 3"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n\n\n\n"],
            ["insert" => "Line 7"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n"],
            ["insert" => "Normal Text\nSome other text"],
        ];
        $result = [
            [["class" => $className, "content" => "Line 1"]],
            [["class" => $className, "content" => "Line 3"]],
            [["class" => $className, "content" => "Line 7"]],
            [["class" => TextBlot::class, "content" => "Normal Text"]],
            [["class" => TextBlot::class, "content" => "Some other text"]],
        ];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test that line breaks with a lot of mixed in formatting parses correctly.
     */
    public function testFormattingWithLineBreaks() {
        $ops = [
            ["attributes" => ["link" => "https =>//google.com"], "insert" => "ogl"],
            ["insert" => "\n\nText after line breaks."],
            ["attributes" => ["strike" => true], "insert" => "strike"],
            ["insert" => "\n\n\n\n"],
            ["attributes" => ["strike" => true], "insert" => "Mutliple more breaks."],
            ["insert" => "\n"],
        ];

        $result = [
            [["class" => TextBlot::class, "content" => "ogl"]],
            [["class" => TextBlot::class, "content" => "\n"]],
            [
                ["class" => TextBlot::class, "content" => "Text after line breaks."],
                ["class" => TextBlot::class, "content" => "strike"],
            ],
            [["class" => TextBlot::class, "content" => "\n"]],
            [["class" => TextBlot::class, "content" => "\n"]],
            [["class" => TextBlot::class, "content" => "\n"]],
            [["class" => TextBlot::class, "content" => "Mutliple more breaks."]],
        ];

        $this->assertParseResults($ops, $result);
    }

    /**
     * Ensure that line blots clear the groups from different lines blots.
     * https://github.com/vanilla/vanilla/issues/7522
     */
    public function testLineBlotClearing() {
        $ops = [
            ["insert" => "Quote"],
            ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
            ["insert" => "Quote"],
            ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
            ["insert" => "List"],
            ["attributes" => ["list" => "bullet"], "insert" => "\n"],
            ["insert" => "List"],
            ["attributes" => ["list" => "bullet"], "insert" => "\n"],
        ];
        $result = [
            [
                ["class" => BlockquoteLineBlot::class, "content" => "Quote"],
                ["class" => BlockquoteLineBlot::class, "content" => "Quote"],
            ],
            [
                ["class" => ListLineBlot::class, "content" => "List"],
                ["class" => ListLineBlot::class, "content" => "List"],
            ],
        ];

        $this->assertParseResults($ops, $result);
    }

    /**
     * Verify rendering of empty heading blots.
     * https://github.com/vanilla/vanilla/issues/7522
     */
    public function testEmptyHeadingBlots() {
        $ops = [["attributes" => ["header" => 2], "content" => "\n"]];
        $result = [[["class" => HeadingBlot::class, "content" => ""]]];
        $this->assertParseResults($ops, $result);

        $ops = [["attributes" => ["header" => 5], "content" => "\n"]];
        $result = [[["class" => NullBlot::class, "content" => ""]]];
        $this->assertParseResults($ops, $result);
    }

    public function testInlineFormattedHeadings() {
        $ops = [
            [ "attributes" => [ "bold" => true ], "insert" => "bold " ],
            [ "attributes" => [ "italic" => true, "bold" => true ], "insert" => "italic " ],
            [ "attributes" => [ "strike" => true ], "insert" => "strike" ],
            [ "attributes" => [ "header" => 2 ], "insert" => "\n" ]
        ];

        $result = [[
            ["class" => TextBlot::class, "content" => "bold "],
            ["class" => TextBlot::class, "content" => "italic "],
            ["class" => HeadingBlot::class, "content" => "strike"],
        ]];

        $this->assertParseResults($ops, $result);
    }

    /**
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testParseMentions() {
        $ops = [
            ["insert" => "\n\n@totoallyNotAMention asdf @notAMention"],
            $this->makeMention("realMention"),
            $this->makeMention("Some Other meénetioön 🙃"),
            $this->makeMention("realMention$$.asdf Number 2"),
            ["insert" => "\n"],
            $this->makeMention("@mentionInABlockquote"),
            ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
        ];

        $expectedUsernames = [
            "realMention",
            "Some Other meénetioön 🙃",
            "realMention$$.asdf Number 2",
        ];

        /** @var Parser $parser */
        $parser = \Gdn::getContainer()->get(Parser::class);
        $actualUsernames = $parser->parseMentionUsernames($ops);
        $this->assertSame($expectedUsernames, $actualUsernames);
    }

    private function makeMention(string $name) {
        return ["insert" => [
            "mention" => [
                "name" => $name,
            ],
        ]];
    }
}
