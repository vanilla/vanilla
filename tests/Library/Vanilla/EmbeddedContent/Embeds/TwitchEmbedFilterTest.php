<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;
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

    /** @var \Gdn_Request */
    private $request;
    
    /**
     * Set the factory and client.
     */
    public function setUp(): void {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new TwitchEmbedFactory($this->httpClient);
        $this->request = new \Gdn_Request();
    }

    /**
     * Test twitch filter embed.
     */
    public function testTwitchFilterEmbed() {
        $data = [

                "height" => 180,
                "width" => 320,
                "twitchID" => "video:441409883",
                "url" => "https://www.twitch.tv/videos/441409883",
                "embedType" => TwitchEmbed::TYPE,
                "name" => 'Movie Magic'
            ];
        $dataEmbed = new TwitchEmbed($data);
        /** @var TwitchEmbedFilter $filter */
        $filter = self::container()->get(TwitchEmbedFilter::class);
        $filter->filterEmbed($dataEmbed);
        $this->assertEquals($this->request->getHost(), $dataEmbed->getHost());
        $this->assertEquals($data, $dataEmbed->getData());
    }
}
