<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Quill;

use Vanilla\Formatting\Formats\RichFormat;
use VanillaTests\ContainerTestCase;

/**
 * Tests for the format service.
 */
class FormatServiceTest extends ContainerTestCase {
    /**
     * Test using a rich-format array of operations with the quoteEmbed method.
     */
    public function testRichQuoteEmbedAsArray() {
        $richEmbed = [
            ["insert" => "Hello world."],
        ];

        $this->assertEquals(
            "<p>Hello world.</p>",
            \Gdn::formatService()->renderQuote($richEmbed, RichFormat::FORMAT_KEY)
        );
    }
}
