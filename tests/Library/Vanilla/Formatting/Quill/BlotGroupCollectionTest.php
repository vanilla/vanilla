<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\Parser;
use VanillaTests\BootstrapTestCase;

/**
 * Verify basic functionality of BlotGroupCollection.
 */
class BlotGroupCollectionTest extends BootstrapTestCase {

    /** @var RichFormat */
    private $formatter;

    /** @var Parser */
    private $parser;

    /**
     * @inheritDoc
     */
    public function setUp(): void {
        $this->container()->call(function (RichFormat $formatter, Parser $parser) {
            $this->formatter = $formatter;
            $this->parser = $parser;
        });
    }

    /**
     * Verify ability to stringify a blot group into the equivalent rich-format JSON.
     *
     * @param string $content
     * @dataProvider provideStringifyContent
     */
    public function testStringify(string $content): void {
        $operations = Parser::jsonToOperations($content);
        $collection = $this->parser->parse($operations, Parser::PARSE_MODE_NORMAL);
        $result = $collection->stringify();
        $actual = $this->formatter->renderHTML($result->text);

        $expected = $this->formatter->renderHTML($content);
        $this->assertSame($expected, $actual);
    }

    /**
     * Provide data for testing the stringify method.
     *
     * @return array[]
     */
    public function provideStringifyContent(): array {
        return [
            "Basic inline and block" => [json_encode([
                ["insert" => "Hello "],
                [
                    "attributes" => ["italic" => true],
                    "insert" => "world",
                ],
                ["insert" => ".\nQuote "],
                [
                    "attributes" => ["bold" => true],
                    "insert" => "me",
                ],
                ["insert" => "."],
                [
                    "attributes" => ["blockquote-line" => true],
                    "insert" => "\n",
                ],
                ["insert" => "This is a test.\n"]
            ])],
        ];
    }
}
