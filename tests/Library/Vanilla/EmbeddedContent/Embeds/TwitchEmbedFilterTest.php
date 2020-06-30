<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\TwitchEmbedFilter;
use Vanilla\EmbeddedContent\Factories\TwitchEmbedFactory;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\MinimalContainerTestCase;

/**
 * Test twitch embed filter.
 */
class TwitchEmbedFilterTest extends MinimalContainerTestCase {

    /** @var TwitchEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp(): void {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new TwitchEmbedFactory($this->httpClient);
    }

    /**
     * Test twitch filter embed.
     */
    public function testTwitchFilterEmbed() {
        // phpcs:disable Generic.Files.LineLength
        $data = [
            "height" => 180,
            "width" => 320,
            "twitchID" => 'video:441409883',
            "url" => "https://www.twitch.tv/videos/441409883",
            "embedType" => "twitch"
        ];
        /** @var TwitchEmbedFilter $filter */
        $filter = self::container()->get(TwitchEmbedFilter::class);
        $url = "https://www.twitch.tv/videos/441409883";
        $embed = $this->factory->createEmbedForUrl($url);
        $filter->filterEmbed($embed);
        $this->assertEquals(\Gdn::request()->getHost(), $embed->getHost());
        $this->assertEquals($data, $embed->getData());
    }
}
