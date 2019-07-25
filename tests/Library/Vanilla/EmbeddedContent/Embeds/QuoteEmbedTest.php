<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\EmbeddedContent\LegacyEmbedFixtures;

/**
 * Test for the individual linkembed.
 */
class QuoteEmbedTest extends ContainerTestCase {

    /**
     * Setup.
     */
    public function setUp() {
        parent::setUp();
        $container = \Gdn::getContainer();
        $container->rule(FormatService::class)
            ->addCall('registerFormat', [RichFormat::FORMAT_KEY, RichFormat::class]);
    }

    /**
     * Ensure we can create discussion embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDiscussionFormat() {
        $oldData = json_decode(LegacyEmbedFixtures::discussion(), true);
        // This should not throw any exception.
        $dataEmbed = new QuoteEmbed($oldData);
        $this->assertInstanceOf(QuoteEmbed::class, $dataEmbed);
    }

    /**
     * Ensure we can create a comment embed from the old data format that might still
     * live in the DB.
     */
    public function testLegactCommentFormat() {

        $oldData = json_decode(LegacyEmbedFixtures::discussion(), true);
        // This should not throw any exception.
        $dataEmbed = new QuoteEmbed($oldData);
        $this->assertInstanceOf(QuoteEmbed::class, $dataEmbed);
    }
}
