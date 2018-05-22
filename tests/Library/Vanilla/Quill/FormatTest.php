<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Vanilla\Quill;

use PHPUnit\Framework\TestCase;
use Vanilla\Quill\Formats\Bold;
use Vanilla\Quill\Formats\Italic;
use Vanilla\Quill\Formats\Link;

class FormatTest extends TestCase {

    private $boldOperation = [
        "insert" => "bold",
        "attributes" => [
            "bold" => true,
        ],
    ];

    private $italicOperation = [
        "insert" => "italic",
        "attributes" => [
            "italic" => true,
        ],
    ];

    private $boldItalicOperation = [
        "insert" => "bold italic",
        "attributes" => [
            "italic" => true,
            "bold" => true,
        ]
    ];

    private $linkOperation = [
        "insert" => "link",
        "attributes" => [
            "link" => "https://google.com",
        ],
    ];

    private $emptyOperation = [];

    /**
     * Uses the BoldFormat as a implementation to test matches for all formats.
     */
    public function testMatches() {
        $this->assertTrue(Bold::matches([$this->boldOperation]), "Bold does not recognize a bold operation.");
        $this->assertTrue(Bold::matches([$this->boldItalicOperation]), "Bold does not recognize a bold, italic operation.");
        $this->assertTrue(Bold::matches([$this->boldOperation, $this->boldItalicOperation]), "Bold does not recognize multiple bold operations.");
        $this->assertTrue(Bold::matches([$this->emptyOperation, $this->boldItalicOperation]), "Bold does not recognize a bold operation if passed with a non-bold operation.");
        $this->assertNotTrue(Bold::matches([$this->emptyOperation]), "Bold recognizes an empty operation as a bold operation.");
    }

    private $boldOpeningTag = [
        "tag" => "strong",
        "attributes" => [],
    ];

    private $italicOpeningTag = [
        "tag" => "em",
        "attributes" => [],
    ];


    private $italicClosingTag = [
        "tag" => "em",
    ];

    private $boldClosingTag = [
        "tag" => "strong",
    ];

    private $linkOpeningTag = [
        "tag" => "a",
        "attributes" => [
            "href" => "https://google.com",
            "rel" => "nofollow",
        ],
    ];

    private $emptyTag = [];

    /**
     * Uses the BoldFormat as a implementation to test getOpeningTag for all formats.
     */
    public function testGetOpeningAndClosingTags() {
        $basicBoldBlot = new Bold(
            $this->boldOperation,
            $this->emptyOperation,
            $this->emptyOperation
        );
        $this->assertEquals($this->boldOpeningTag, $basicBoldBlot->getOpeningTag(), "Bold did not return a bold opening tag with a basic bold current operation.");
        $this->assertEquals($this->boldClosingTag, $basicBoldBlot->getClosingTag(), "Bold did not return a bold closing tag with a basic bold current operation.");

        $boldCurrentAndBoldBeforeBlot = new Bold(
            $this->boldOperation,
            $this->boldOperation,
            $this->emptyOperation
        );
        $this->assertEquals($this->emptyTag, $boldCurrentAndBoldBeforeBlot->getOpeningTag(), "Bold returned a bold opening tag with a bold current operation and a bold previous operation.");
        $this->assertEquals($this->boldClosingTag, $boldCurrentAndBoldBeforeBlot->getClosingTag(), "Bold did not return a bold closing tag with a bold current operation and a bold previous operation.");

        $boldCurrentAndBoldAfterBlot = new Bold(
            $this->boldOperation,
            $this->emptyOperation,
            $this->boldOperation
        );

        $this->assertEquals($this->boldOpeningTag, $boldCurrentAndBoldAfterBlot->getOpeningTag(), "Bold did not return a bold opening tag with a bold current operation and a bold after operation.");
        $this->assertEquals($this->emptyTag, $boldCurrentAndBoldAfterBlot->getClosingTag(), "Bold returned a bold closing tag with a bold current operation and a bold after operation.");

        $linkBlot = new Link(
            $this->linkOperation,
            $this->emptyOperation,
            $this->emptyOperation
        );

        $this->assertEquals($this->linkOpeningTag, $linkBlot->getOpeningTag(), "Link blot returned an incorrect opening tag.");
    }

    public function testNestingPriorityTags() {
        $boldThenBothBlot = new Bold(
            $this->boldItalicOperation,
            $this->boldOperation,
            $this->italicOperation
        );

        $this->assertEquals($this->emptyTag, $boldThenBothBlot->getOpeningTag(), "Bold blot followed by bold/italic blot opening tag is not getting optimized away.");

        $italicThenBothBlot = new Italic(
            $this->boldItalicOperation,
            $this->italicOperation,
            $this->boldOperation
        );

        $this->assertEquals($this->italicOpeningTag, $italicThenBothBlot->getOpeningTag(), "Italic blot followed by bold/italic blot opening tag is not getting optimized away.");

        $bothThenBoldBlot = new Bold(
            $this->boldOperation,
            $this->italicOperation,
            $this->boldItalicOperation
        );

        $this->assertEquals($this->emptyTag, $bothThenBoldBlot->getClosingTag(), "Bold blot followed by bold/italic blot closing tag is not getting optimized away.");

        $italicThenBothBlot = new Italic(
            $this->italicOperation,
            $this->boldOperation,
            $this->boldItalicOperation
        );

        $this->assertEquals($this->italicClosingTag, $italicThenBothBlot->getClosingTag(), "Italic blot followed by bold/italic blot closing tag is getting optimized away.");

    }
}
