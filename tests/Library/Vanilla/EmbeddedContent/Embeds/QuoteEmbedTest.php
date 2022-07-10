<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedDisplayOptions;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbedFilter;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\FormatService;
use VanillaTests\Fixtures\EmbeddedContent\EmbedFixtures;
use VanillaTests\Library\Vanilla\Formatting\HtmlNormalizeTrait;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\EmbeddedContent\LegacyEmbedFixtures;

/**
 * Test for the individual linkembed.
 */
class QuoteEmbedTest extends MinimalContainerTestCase {
    use HtmlNormalizeTrait;

    /** @var \Gdn_Configuration */
    private static $config;

    /**
     * Setup.
     */
    public function setUp(): void {
        parent::setUp();
        $container = \Gdn::getContainer();
        $container->rule(FormatService::class)
            ->addCall('registerFormat', [RichFormat::FORMAT_KEY, $container->get(RichFormat::class)]);


        self::$config = $container->get(\Gdn_Configuration::class);
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

    /**
     * Test QuoteEmbed->normalizeData() with displayOptions set by the config
     */
    public function testQuoteEmbedNormalizeDataWithConfig(): void {
        $config = [
            'showCompactUserInfo' => true,
        ];

        //set config
        self::$config->set('embed.quote.displayOptions.comment', $config, true, false);

        //generate quote
        /** @var QuoteEmbedFilter $filter */
        $filter = self::container()->get(QuoteEmbedFilter::class);
        $quoteEmbed = new QuoteEmbed(EmbedFixtures::comment("commentUser"));
        $quoteEmbed = $filter->filterEmbed($quoteEmbed);
        /** @var QuoteEmbedDisplayOptions $displayOptions */
        $displayOptions = $quoteEmbed->getData()['displayOptions'];

        //assert that the displayOptions match the $config
        $this->assertTrue($displayOptions->isShowCompactUserInfo());
    }
}
