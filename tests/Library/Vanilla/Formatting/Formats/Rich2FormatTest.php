<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Formatting\Formats;

use Vanilla\Contracts\Formatting\FormatInterface;
use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use Vanilla\EmbeddedContent\Embeds\ImageEmbed;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\EmbedService;
use Vanilla\Formatting\Formats\Rich2Format;
use VanillaTests\Fixtures\Formatting\FormatFixtureFactory;

class Rich2FormatTest extends AbstractFormatTestCase
{
    protected function prepareFormatter(): FormatInterface
    {
        self::container()
            ->rule(EmbedService::class)
            ->addCall("registerEmbed", [ImageEmbed::class, ImageEmbed::TYPE])
            ->addCall("registerEmbed", [FileEmbed::class, FileEmbed::TYPE])
            ->addCall("registerEmbed", [YouTubeEmbed::class, YouTubeEmbed::TYPE])
            ->addCall("registerEmbed", [LinkEmbed::class, LinkEmbed::TYPE]);
        return self::container()->get(Rich2Format::class);
    }

    protected function prepareFixtures(): array
    {
        return (new FormatFixtureFactory("rich2"))->getAllFixtures();
    }

    public function testParseImageUrlsExcludeEmojis()
    {
        $this->markTestSkipped();
    }
}
