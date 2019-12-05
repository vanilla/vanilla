<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Quill\Formats\Bold;
use Vanilla\Formatting\Quill\Formats\Italic;
use Vanilla\Formatting\Quill\Formats\Link;

class FormatTest extends SharedBootstrapTestCase {

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
        ],
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
        $this->assertTrue(
            Bold::matches([$this->boldOperation]),
            "Bold does not recognize a bold operation."
        );
        $this->assertTrue(
            Bold::matches([$this->boldItalicOperation]),
            "Bold does not recognize a bold, italic operation."
        );
        $this->assertTrue(
            Bold::matches([$this->boldOperation, $this->boldItalicOperation]),
            "Bold does not recognize multiple bold operations."
        );
        $this->assertTrue(
            Bold::matches([$this->emptyOperation, $this->boldItalicOperation]),
            "Bold does not recognize a bold operation if passed with a non-bold operation."
        );
        $this->assertNotTrue(
            Bold::matches([$this->emptyOperation]),
            "Bold recognizes an empty operation as a bold operation."
        );
    }

    private $boldOpeningTag = "<strong>";

    private $italicOpeningTag = "<em>";
    private $italicClosingTag = "</em>";
    private $boldClosingTag   = "</strong>";
    private $linkOpeningTag   = "<a href=\"https://google.com\" rel=\"nofollow noreferrer ugc\">";
    private $emptyTag         = "";

    /**
     * Uses the BoldFormat as a implementation to test renderOpeningTag for all formats.
     */
    public function testGetOpeningAndClosingTags() {
        $basicBoldBlot = new Bold(
            $this->boldOperation,
            $this->emptyOperation,
            $this->emptyOperation
        );
        $this->assertEquals(
            $this->boldOpeningTag,
            $basicBoldBlot->renderOpeningTag(),
            "Bold did not return a bold opening tag with a basic bold current operation."
        );
        $this->assertEquals(
            $this->boldClosingTag,
            $basicBoldBlot->renderClosingTag(),
            "Bold did not return a bold closing tag with a basic bold current operation."
        );

        $boldCurrentAndBoldBeforeBlot = new Bold(
            $this->boldOperation,
            $this->boldOperation,
            $this->emptyOperation
        );
        $this->assertEquals(
            $this->emptyTag,
            $boldCurrentAndBoldBeforeBlot->renderOpeningTag(),
            "Bold returned a bold opening tag with a bold current operation and a bold previous operation."
        );
        $this->assertEquals(
            $this->boldClosingTag,
            $boldCurrentAndBoldBeforeBlot->renderClosingTag(),
            "Bold did not return a bold closing tag with a bold current operation and a bold previous operation."
        );

        $boldCurrentAndBoldAfterBlot = new Bold(
            $this->boldOperation,
            $this->emptyOperation,
            $this->boldOperation
        );

        $this->assertEquals(
            $this->boldOpeningTag,
            $boldCurrentAndBoldAfterBlot->renderOpeningTag(),
            "Bold did not return a bold opening tag with a bold current operation and a bold after operation."
        );
        $this->assertEquals(
            $this->emptyTag,
            $boldCurrentAndBoldAfterBlot->renderClosingTag(),
            "Bold returned a bold closing tag with a bold current operation and a bold after operation."
        );

        $linkBlot = new Link(
            $this->linkOperation,
            $this->emptyOperation,
            $this->emptyOperation
        );

        $this->assertEquals(
            $this->linkOpeningTag,
            $linkBlot->renderOpeningTag(),
            "Link blot returned an incorrect opening tag."
        );
    }

    /**
     * Ensure that the format tags are nested in the correct order.
     */
    public function testNestingPriorityTags() {
        $boldThenBothBlot = new Bold(
            $this->boldItalicOperation,
            $this->boldOperation,
            $this->italicOperation
        );

        $this->assertEquals(
            $this->emptyTag,
            $boldThenBothBlot->renderOpeningTag(),
            "Bold blot followed by bold/italic blot opening tag is not getting optimized away."
        );

        $italicThenBothBlot = new Italic(
            $this->boldItalicOperation,
            $this->italicOperation,
            $this->boldOperation
        );

        $this->assertEquals(
            $this->italicOpeningTag,
            $italicThenBothBlot->renderOpeningTag(),
            "Italic blot followed by bold/italic blot opening tag is not getting optimized away."
        );

        $bothThenBoldBlot = new Bold(
            $this->boldOperation,
            $this->italicOperation,
            $this->boldItalicOperation
        );

        $this->assertEquals(
            $this->emptyTag,
            $bothThenBoldBlot->renderClosingTag(),
            "Bold blot followed by bold/italic blot closing tag is not getting optimized away."
        );

        $italicThenBothBlot = new Italic(
            $this->italicOperation,
            $this->boldOperation,
            $this->boldItalicOperation
        );

        $this->assertEquals(
            $this->italicClosingTag,
            $italicThenBothBlot->renderClosingTag(),
            "Italic blot followed by bold/italic blot closing tag is getting optimized away."
        );
    }
}
