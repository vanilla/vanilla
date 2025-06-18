<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use PHPUnit\Framework\TestCase;
use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\Contracts\Formatting\FormatParsedInterface;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\IFrameEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\RichFormatParsed;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\EmbeddedContent\MockHeadingProviderEmbed;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the RichFormat.
 */
class RichFormatTest extends AbstractFormatTestCase
{
    /**
     * @inheritdoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        self::setConfig("Garden.TrustedDomains", "*.higherlogic.com");
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE])
            ->addCall("registerEmbed", [FileEmbed::class, FileEmbed::TYPE]);
        return self::container()->get(RichFormat::class);
    }

    /**
     * @inheritdoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("rich"))->getAllFixtures();
    }
}
