<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use PHPUnit\Framework\TestCase;
use Vanilla\EmbeddedContent\EmbeddedContentException;
use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;
use Vanilla\EmbeddedContent\Embeds\TwitchEmbedFilter;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use VanillaTests\Fixtures\Request;

/**
 * Test twitch embed filter.
 */
class TwitchEmbedFilterTest extends TestCase {
    /** @var \Gdn_Request */
    private $request;

    /** @var TwitchEmbedFilter */
    private $twitchEmbedFilter;

    /**
     * Setup
     */
    public function setUp(): void {
        parent::setUp();
        $this->request = new Request();
        $this->twitchEmbedFilter = new TwitchEmbedFilter($this->request);
    }

    /**
     * Verify ability to property detect supported embed types.
     *
     * @param string $embedType
     * @param bool $expected
     * @dataProvider provideEmbedTypes
     */
    public function testCanHandleEmbedType(string $embedType, bool $expected): void {
        $this->assertSame($expected, $this->twitchEmbedFilter->canHandleEmbedType($embedType));
    }

    /**
     * Provide data for testing embed type support.
     *
     * @return array
     */
    public function provideEmbedTypes(): array {
        $result = [
            "Twitch" => [TwitchEmbed::TYPE, true],
            "YouTube" => [YouTubeEmbed::TYPE, false],
        ];
        return $result;
    }

    /**
     * Verify ability to recognize an invalid embed when filtering.
     */
    public function testFilterInvalidEmbed(): void {
        $this->expectException(EmbeddedContentException::class);
        $this->getExpectedExceptionMessage("Expected a twitch embed.");

        $invalidEmbed = new YouTubeEmbed([
            "url" => "https://youtu.be/dQw4w9WgXcQ",
            "embedType" => YouTubeEmbed::TYPE,
            "videoID" => "dQw4w9WgXcQ",
        ]);
        $this->twitchEmbedFilter->filterEmbed($invalidEmbed);
    }

    /**
     * Test twitch filter embed.
     */
    public function testFilterValidEmbed(): void {
        $data = [
            "height" => 180,
            "width" => 320,
            "twitchID" => "video:441409883",
            "url" => "https://www.twitch.tv/videos/441409883",
            "embedType" => TwitchEmbed::TYPE,
            "name" => 'Movie Magic'
        ];
        $dataEmbed = new TwitchEmbed($data);
        $this->twitchEmbedFilter->filterEmbed($dataEmbed);
        $this->assertEquals($this->request->getHost(), $dataEmbed->getHost());
        $this->assertEquals($data, $dataEmbed->getData());
    }
}
