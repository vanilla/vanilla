<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;
use Vanilla\EmbeddedContent\Factories\TwitchEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class TwitchEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var TwitchEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new TwitchEmbedFactory($this->httpClient);
    }

    /**
     * Test that all expected domains are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedDomainsProvider
     */
    public function testSupportedDomains(string $urlToTest) {
        $this->assertTrue($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * @return array
     */
    public function supportedDomainsProvider(): array {
        return [
            [ "https://www.twitch.tv/videos/441409883" ]
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://www.twitch.tv/videos/441409883";
        $videoID = "441409883";
        $frameSrc = "https://player.twitch.tv/?video=441409883";

        $oembedParams = http_build_query([ "url" => $url ]);
        $oembedUrl = TwitchEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "version" => 1,
            "type" => "video",
            "twitch_type" => "vod",
            "title" => "Movie Magic",
            "author_name" => "Jerma985",
            "author_url" => "https://www.twitch.tv/jerma985",
            "provider_name" => "Twitch",
            "provider_url" => "https://www.twitch.tv/",
            "thumbnail_url" => "https://static-cdn.jtvnw.net/s3_vods/aa1bb413e849cf63b446_jerma985_34594404336_1230815694/thumb/thumb0-640x360.jpg",
            "video_length" => 19593,
            "created_at" => "2019-06-19T21:22:59Z",
            "game" => "The Movies",
            "html" => "<iframe src=\"https://player.twitch.tv/?%21branding=&amp;autoplay=false&amp;video=v441409883\" width=\"500\" height=\"281\" frameborder=\"0\" scrolling=\"no\" allowfullscreen></iframe>",
            "width" => 500,
            "height" => 281,
            "request_url" => "https://www.twitch.tv/videos/441409883",
        ];
        // phpcs:enable Generic.Files.LineLength

        $this->httpClient->addMockResponse(
            $oembedUrl,
            new HttpResponse(
                200,
                "Content-Type: application/json",
                json_encode($data)
            )
        );

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($url);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                "height" => $data["height"],
                "width" => $data["width"],
                "photoUrl" => $data["thumbnail_url"],
                "twitchID" => "video:{$videoID}",
                "url" => $url,
                "embedType" => TwitchEmbed::TYPE,
                "name" => $data["title"],
                "frameSrc" => $frameSrc,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new TwitchEmbed($embedData);
        $this->assertInstanceOf(TwitchEmbed::class, $dataEmbed);
    }
}
