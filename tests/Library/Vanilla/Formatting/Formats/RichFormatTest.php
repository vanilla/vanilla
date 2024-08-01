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
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\RichFormat;
use Vanilla\Formatting\Formats\RichFormatParsed;
use VanillaTests\EventSpyTestTrait;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;
use VanillaTests\Library\Vanilla\Formatting\UserMentionTestTraits;
use VanillaTests\SiteTestTrait;

/**
 * Tests for the RichFormat.
 */
class RichFormatTest extends AbstractFormatTestCase
{
    /**
     * @inheritDoc
     */
    protected function prepareFormatter(): FormatInterface
    {
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE]);
        return self::container()->get(RichFormat::class);
    }

    /**
     * @inheritDoc
     */
    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("rich"))->getAllFixtures();
    }
}
