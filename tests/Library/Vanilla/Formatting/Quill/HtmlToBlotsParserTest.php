<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Quill\BlotGroupCollection;
use Vanilla\Formatting\Quill\HtmlToBlotsParser;
use Vanilla\Formatting\Quill\Parser;
use VanillaTests\BootstrapTrait;
use VanillaTests\SetupTraitsTrait;
use VanillaTests\VanillaTestCase;

/**
 * Assert basic functional requirements of HtmlToBlotsParser.
 */
class HtmlToBlotsParserTest extends VanillaTestCase
{
    use BootstrapTrait, SetupTraitsTrait;

    /** @var RichFormat */
    private $formatter;

    /** @var Parser */
    private $parser;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        $this->setUpTestTraits();
        $this->container()->call(function (RichFormat $formatter, Parser $parser) {
            $this->formatter = $formatter;
            $this->parser = $parser;
        });
    }

    /**
     * Verify parseInlineHtml can gracefully handle an empty value.
     */
    public function testParseInlineHtmlEmpty(): void
    {
        $content = json_encode([["insert" => "\n"]]);
        $operations = Parser::jsonToOperations($content);
        $blotGroup = $this->parser->parse($operations, Parser::PARSE_MODE_NORMAL)->getGroups()[0];

        $html = $blotGroup->renderPartialLineGroupContent();
        $parent = new BlotGroupCollection([], [], Parser::PARSE_MODE_NORMAL);
        $actual = HtmlToBlotsParser::parseInlineHtml($html, $parent);
        $this->assertSame([], $actual);
    }
}
