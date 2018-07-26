<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Formatting\Quill\Blots\Lines\BlockquoteLineBlot;
use Vanilla\Formatting\Quill\Blots\Lines\ListLineBlot;
use Vanilla\Formatting\Quill\Blots\TextBlot;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Parser;

class ParserTest extends SharedBootstrapTestCase {
    /**
     * @dataProvider dataProvider
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function testParse($ops, $expected) {
        $parser = \Gdn::getContainer()->get(Parser::class);
        $actual = $parser->parseIntoTestData($ops);
        $this->assertSame($expected, $actual);
    }

    public function dataProvider() {
        return [
            [
                [[
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
                ]],
                [
                    [["class" => ExternalBlot::class, "content" => ""]],
                ],
            ],
            [
                [["insert" => "\n"]],
                [], // A plain newline is an empty editor.
            ],
            [
                [["insert" => "SomeText\n"]],
                [
                    [["class" => TextBlot::class, "content" => "SomeText"]],
                ],
            ],
            [
                [["insert" => "Sometext\n\n\nAfter3lines\n\n"]],
                [
                    [["class" => TextBlot::class, "content" => "Sometext"]],
                    [["class" => TextBlot::class, "content" => "\n"]],
                    [["class" => TextBlot::class, "content" => "\n"]],
                    [["class" => TextBlot::class, "content" => "After3lines"]],
                    [["class" => TextBlot::class, "content" => "\n"]],
                ],
            ],
            [
                [
                    ["insert" => "Line 1"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n\n"],
                    ["insert" => "Line 3"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n\n\n\n"],
                    ["insert" => "Line 7"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
                    ["insert" => "Normal Text\nSome other text"],
                ],
                [
                    [
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 1"],
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 3"],
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 7"],
                    ],
                    [["class" => TextBlot::class, "content" => "Normal Text"]],
                    [["class" => TextBlot::class, "content" => "Some other text"]],
                ],
            ],
            [
                [
                    ["insert" => "Line 1"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n\n"],
                    ["insert" => "Line 3"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n\n\n\n"],
                    ["insert" => "Line 7"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
                    ["insert" => "Normal Text\n"],
                ],
                [
                    [
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 1"],
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 3"],
                        ["class" => BlockquoteLineBlot::class, "content" => "Line 7"],
                    ],
                    [["class" => TextBlot::class, "content" => "Normal Text"]],
                ],
            ],
            [
                [
                    ["attributes" => ["link" => "https =>//google.com"], "insert" => "ogl"],
                    ["insert" => "\n\nText after line breaks."],
                    ["attributes" => ["strike" => true], "insert" => "strike"],
                    ["insert" => "\n\n\n\n"],
                    ["attributes" => ["strike" => true], "insert" => "Mutliple more breaks."],
                    ["insert" => "\n"],
                ],
                [
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
                ],
            ],
            [
                [
                    ["insert" => "Quote"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
                    ["insert" => "Quote"],
                    ["attributes" => ["blockquote-line" => true], "insert" => "\n"],
                    ["insert" => "List"],
                    ["attributes" => ["list" => "bullet"], "insert" => "\n"],
                    ["insert" => "List"],
                    ["attributes" => ["list" => "bullet"], "insert" => "\n"],
                ],
                [
                    [
                        ["class" => BlockquoteLineBlot::class, "content" => "Quote"],
                        ["class" => BlockquoteLineBlot::class, "content" => "Quote"],
                    ],
                    [
                        ["class" => ListLineBlot::class, "content" => "List"],
                        ["class" => ListLineBlot::class, "content" => "List"],
                    ],
                ],
            ],
        ];
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
