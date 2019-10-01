<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use Vanilla\EmbeddedContent\Factories\YouTubeEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class YouTubeEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var YouTubeEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new YouTubeEmbedFactory($this->httpClient);
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
            [ "https://www.youtube.com/watch?v=fy0fTFpqT48&t=2s" ],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://www.youtube.com/watch?v=fy0fTFpqT48&t=2s";
        $videoID = "fy0fTFpqT48";
        $frameSrc = "https://www.youtube.com/embed/fy0fTFpqT48?feature=oembed&autoplay=1&start=2";
        $start = 2;
        $showRelated = false;

        $oembedParams = http_build_query([ "url" => $url ]);
        $oembedUrl = YouTubeEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "html" => "<iframe width=\"459\" height=\"344\" src=\"https://www.youtube.com/embed/fy0fTFpqT48?feature=oembed\" frameborder=\"0\" allow=\"accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe>",
            "author_name" => "GravitasVODGlobal",
            "title" => "Attack of the Killer Tomatoes - Trailer",
            "version" => "1.0",
            "thumbnail_width" => 480,
            "author_url" => "https://www.youtube.com/user/GravitasVODGlobal",
            "height" => 344,
            "type" => "video",
            "provider_url" => "https://www.youtube.com/",
            "thumbnail_height" => 360,
            "thumbnail_url" => "https://i.ytimg.com/vi/fy0fTFpqT48/hqdefault.jpg",
            "width" => 459,
            "provider_name" => "YouTube",
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
                "photoUrl" => "https://i.ytimg.com/vi/fy0fTFpqT48/hqdefault.jpg",
                "videoID" => $videoID,
                "showRelated" => $showRelated,
                "start" => $start,
                "url" => $url,
                "embedType" => YouTubeEmbed::TYPE,
                "name" => $data["title"],
                "frameSrc" => $frameSrc,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new YouTubeEmbed($embedData);
        $this->assertInstanceOf(YouTubeEmbed::class, $dataEmbed);
    }
}
