<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Exception\FormattingException;
use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\HeadingTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\BlockquoteLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ListLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\SpoilerLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ParagraphLineTerminatorBlot;
use Vanilla\Formatting\Quill\Blots\NullBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;
use VanillaTests\Fixtures\EmbeddedContent\EmbedFixtures;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Parser;

/**
 * Tests for the Quill parser.
 */
class ParserTest extends SharedBootstrapTestCase {

    /**
     * Utility to make a single line in a paragraph's parsed representation.
     *
     * @param int $count The number of newlines ot make.
     *
     * @return array
     */
    private function makeParagraphLine(int $count = 1) {
        $content = str_repeat("\n", $count);
        return ["class" => ParagraphLineTerminatorBlot::class, "content" => $content];
    }

    /**
     * Assert that some operations turn into some expected parsing results.
     *
     * @param array $ops The actual operations.
     * @param array $expected The expected parse results.
     */
    public function assertParseResults(array $ops, array $expected) {
        $error = "The parser failed to instantiate through the container";
        try {
            $parser = self::container()->get(Parser::class);
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

    /**
     * Test converting various content in JSON operations.
     *
     * @param string $input
     * @param array $expectedOps
     * @param string|null $expectedExceptionClass
     *
     * @dataProvider provideJsonToOps
     */
    public function testJsonToOperations(string $input, array $expectedOps, ?string $expectedExceptionClass = null) {
        if ($expectedExceptionClass) {
            $this->expectException($expectedExceptionClass);
        }
        $actual = Parser::jsonToOperations($input);
        $this->assertSame($actual, $expectedOps);
    }

    /**
     * @return array
     */
    public function provideJsonToOps(): array {
        return [
            'empty' => [
                '',
                Parser::SINGLE_NEWLINE_CONTENTS,
            ],
            'empty array' => [
                '[]',
                Parser::SINGLE_NEWLINE_CONTENTS,
            ],
            'mangled content' => [
                '[asdf%$}}}}',
                [],
                FormattingException::class
            ],
            'good content' => [
                json_encode(Parser::SINGLE_NEWLINE_CONTENTS),
                Parser::SINGLE_NEWLINE_CONTENTS,
            ],
            'not an array' => [
                '{ "insert": "\n" }',
                [],
                FormattingException::class,
            ],
            'not an array 2' => [
                'false',
                [],
                FormattingException::class,
            ],
        ];
    }

    /**
     * Test parsing of an external blot.
     */
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
        $result = [[[
            'class' => 'Vanilla\Formatting\Quill\Blots\Lines\ParagraphLineTerminatorBlot',
            'content' => "\n",
        ]]];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of normal text.
     */
    public function testTrailingNewlines() {
        $ops = [["insert" => "SomeText\n\n\n\n"]];
        $result = [[
            ["class" => TextBlot::class, "content" => "SomeText"],
            $this->makeParagraphLine(),
        ]];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of normal text.
     */
    public function testNormalText() {
        $ops = [["insert" => "SomeText\n"]];
        $result = [[
            ["class" => TextBlot::class, "content" => "SomeText"],
            $this->makeParagraphLine(),
        ]];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of newlines.
     */
    public function testNewlineParsing() {
        $ops = [["insert" => "Sometext\n\n\nAfter3lines\n\n"]];
        $result = [
            [
                ["class" => TextBlot::class, "content" => "Sometext"],
                $this->makeParagraphLine(3),
            ],
            [
                ["class" => TextBlot::class, "content" => "After3lines"],
                $this->makeParagraphLine(1),
            ],
        ];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Test the parsing of block level blots. Lines, headings, etc.
     */
    public function testBlockBlotNewlines() {
        $this->assertCorrectLineBlotParsing("list", "bullet", ListLineTerminatorBlot::class);
        $this->assertCorrectLineBlotParsing("list", "ordered", ListLineTerminatorBlot::class);
        $this->assertCorrectLineBlotParsing("spoiler-line", true, SpoilerLineTerminatorBlot::class);
        $this->assertCorrectLineBlotParsing("blockquote-line", true, BlockquoteLineTerminatorBlot::class);
        $this->assertCorrectNonLineBlockBlockParsing("header", 2, HeadingTerminatorBlot::class);
        $this->assertCorrectNonLineBlockBlockParsing("header", 3, HeadingTerminatorBlot::class);
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
            ["insert" => "Normal Text\nSome other text\n"],
        ];
        $result = [
            [
                ["class" => TextBlot::class, "content" => "Line 1"],
                ["class" => $className, "content" => "\n\n"],
                ["class" => TextBlot::class, "content" => "Line 3"],
                ["class" => $className, "content" => "\n\n\n\n"],
                ["class" => TextBlot::class, "content" => "Line 7"],
                ["class" => $className, "content" => "\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "Normal Text"],
                $this->makeParagraphLine(),
            ],
            [
                ["class" => TextBlot::class, "content" => "Some other text"],
                $this->makeParagraphLine(),
            ],
        ];
        $this->assertParseResults($ops, $result);

        $ops = [
            ["insert" => "Line Group 2 - 1"],
            ["attributes" => [$attrKey => $attrValue], "insert" => "\n"],
            ["insert" => "After Line"],
            ["insert" => "Bold", ["attributes" => ["bold" => true]]],
            ["insert" => "\n"],
        ];
        $result = [
            [
                ["class" => TextBlot::class, "content" => "Line Group 2 - 1"],
                ["class" => $className, "content" => "\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "After Line"],
                ["class" => TextBlot::class, "content" => "Bold"],
                $this->makeParagraphLine(),
            ],
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
            ["insert" => "Normal Text\nSome other text\n"],
        ];
        $result = [
            [
                ["class" => TextBlot::class, "content" => "Line 1"],
                ["class" => $className, "content" => "\n\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "Line 3"],
                ["class" => $className, "content" => "\n\n\n\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "Line 7"],
                ["class" => $className, "content" => "\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "Normal Text"],
                $this->makeParagraphLine(),
            ],
            [
                ["class" => TextBlot::class, "content" => "Some other text"],
                $this->makeParagraphLine(),
            ],
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
            [
                ["class" => TextBlot::class, "content" => "ogl"],
                $this->makeParagraphLine(2),
            ],
            [
                ["class" => TextBlot::class, "content" => "Text after line breaks."],
                ["class" => TextBlot::class, "content" => "strike"],
                $this->makeParagraphLine(4),
            ],
            [
                ["class" => TextBlot::class, "content" => "Mutliple more breaks."],
                $this->makeParagraphLine()
            ],
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
                ["class" => TextBlot::class, "content" => "Quote"],
                ["class" => BlockquoteLineTerminatorBlot::class, "content" => "\n"],
                ["class" => TextBlot::class, "content" => "Quote"],
                ["class" => BlockquoteLineTerminatorBlot::class, "content" => "\n"],
            ],
            [
                ["class" => TextBlot::class, "content" => "List"],
                ["class" => ListLineTerminatorBlot::class, "content" => "\n"],
                ["class" => TextBlot::class, "content" => "List"],
                ["class" => ListLineTerminatorBlot::class, "content" => "\n"],
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
        $result = [[["class" => HeadingTerminatorBlot::class, "content" => ""]]];
        $this->assertParseResults($ops, $result);

        $ops = [["attributes" => ["header" => 10], "content" => "\n"]];
        $result = [[["class" => NullBlot::class, "content" => ""]]];
        $this->assertParseResults($ops, $result);
    }

    /**
     * Ensure that a single heading can properly render when it contains multiple inline formats.
     */
    public function testInlineFormattedHeadings() {
        $ops = [
            ["attributes" => ["bold" => true], "insert" => "bold "],
            ["attributes" => ["italic" => true, "bold" => true], "insert" => "italic "],
            ["attributes" => ["strike" => true], "insert" => "strike"],
            ["attributes" => ["header" => 2], "insert" => "\n"],
        ];

        $result = [[
            ["class" => TextBlot::class, "content" => "bold "],
            ["class" => TextBlot::class, "content" => "italic "],
            ["class" => TextBlot::class, "content" => "strike"],
            ["class" => HeadingTerminatorBlot::class, "content" => "\n"],
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
            ["insert" => "\n"],

            // Comment and discussion quotes send a notification.
            EmbedFixtures::embedInsert(EmbedFixtures::discussion("discussionUser")),
            EmbedFixtures::embedInsert(EmbedFixtures::comment("commentUser")),

            // No duplication should happen.
            $this->makeMention("duplicate"),
            $this->makeMention("duplicate"),
        ];

        $expectedUsernames = [
            "realMention",
            "Some Other meénetioön 🙃",
            "realMention$$.asdf Number 2",
            "discussionUser",
            "commentUser",
            "duplicate"
        ];

        // Make sure the quote embed is registered.
        /** @var EmbedService $embedService */
        $embedService = \Gdn::getContainer()->get(EmbedService::class);
        $embedService->registerEmbed(QuoteEmbed::class, QuoteEmbed::TYPE);

        /** @var Parser $parser */
        $parser = \Gdn::getContainer()->get(Parser::class);
        $actualUsernames = $parser->parseMentionUsernames($ops);
        $this->assertSame($expectedUsernames, $actualUsernames);
    }

    /**
     * Make a mention of a user.
     *
     * @param string $name
     * @return array
     */
    private function makeMention(string $name) {
        return ["insert" => [
            "mention" => [
                "name" => $name,
            ],
        ]];
    }
}
