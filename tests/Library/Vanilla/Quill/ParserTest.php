<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license https://opensource.org/licenses/GPL-2.0 GPL-2.0
 */

namespace VanillaTests\Library\Vanilla\Quill;

use Vanilla\Quill\Blots\Embeds\ExternalBlot;
use Vanilla\Quill\Blots\TextBlot;
use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Quill\Parser;

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
                [
                    [["class" => TextBlot::class, "content" => ""]],
                ],
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
                    [["class" => TextBlot::class, "content" => ""]],
                    [["class" => TextBlot::class, "content" => ""]],
                    [["class" => TextBlot::class, "content" => "After3lines"]],
                    [["class" => TextBlot::class, "content" => ""]],
                ]
            ],
            [
                [
                    [ "insert" => "Line 1" ],
                    [ "attributes" => [ "blockquote-line" => true ], "insert" => "\n\n" ],
                    [ "insert" => "Line 3" ],
                    [ "attributes" => [ "blockquote-line" => true ], "insert" => "\n\n\n\n" ],
                    [ "insert" => "Line 7" ],
                    [ "attributes" => [ "blockquote-line" => true ], "insert" => "\n" ]
                ],
                [
                    [["class" => BlockquoteLineBlot::class, "content" => "Line 1"]],
                    [["class" => BlockquoteLineBlot::class, "content" => "Line 3"]],
                    [["class" => BlockquoteLineBlot::class, "content" => "Line 7"]],
                ]
            ]
        ];
    }
}
