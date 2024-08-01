<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Metadata;

use Garden\Schema\Schema;
use PHPUnit\Framework\TestCase;
use Vanilla\Metadata\Parser\AbstractXmlParser;

/**
 * Tests XML Parsing.
 *
 * @package VanillaTests\Library\Vanilla\Metadata
 */
class AbstractXmlParserTest extends TestCase
{
    /**
     * @return string[]
     */
    public function dataXML(): array
    {
        return [[""]];
    }

    /**
     * Test XML parsing.
     *
     */
    public function testParseDirectElement(): void
    {
        $xmlContent = '<Person>
                        <Name>Name1</Name>
                        <Something>Something1</Something>
                    </Person>';
        $xmlDOM = new \DOMDocument();
        $xmlDOM->loadXML($xmlContent);
        $schema = Schema::parse(["Name:s"]);
        $xmlParserStub = $this->getMockForAbstractClass(AbstractXmlParser::class);
        $xmlParserStub->method("getSchema")->willReturn($schema);
        $xmlParserStub->method("addDataToField")->willReturn(null);
        $results = $xmlParserStub->parse($xmlDOM);
        $this->assertCount(1, $results);
        $this->assertEquals("Name1", $results["Name"]);
    }

    /**
     * Test XML parsing: List items.
     *
     */
    public function testParseListOfData(): void
    {
        $xmlContent = '<Persons>
                    <Person>
                        <Name>Name1</Name>
                        <Something>Something1</Something>
                    </Person>
                    <Person>
                        <Name>Name2</Name>
                        <FirstName>FirstName2</FirstName>
                    </Person>
                </Persons>';
        $xmlDOM = new \DOMDocument();
        $xmlDOM->loadXML($xmlContent);
        $xmlParserStub = $this->getMockForAbstractClass(AbstractXmlParser::class);
        $schema = Schema::parse([
            "Person:a" => Schema::parse(["Name:s", "FirstName:s?"]),
        ]);
        $xmlParserStub->method("getSchema")->willReturn($schema);
        $xmlParserStub->method("addDataToField")->willReturn(null);
        $results = $xmlParserStub->parse($xmlDOM);
        $this->assertCount(2, $results["Person"]);
        $this->assertNotContains("Something", array_keys($results["Person"][0]));
    }
}
