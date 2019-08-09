<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\SoundCloudEmbed;
use Vanilla\EmbeddedContent\Factories\SoundCloudEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class SoundCloudEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var SoundCloudEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new SoundCloudEmbedFactory($this->httpClient);
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
            [ "https://soundcloud.com/secret-service-862007284/old-town-road-remix-feat-billy" ]
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://soundcloud.com/secret-service-862007284/old-town-road-remix-feat-billy";
        $trackID = "600964365";
        $showArtwork = true;
        $useVisualPlayer = true;

        $oembedParams = http_build_query([
            "format" => "json",
            "url" => $url,
        ]);
        $oembedUrl = SoundCloudEmbedFactory::OEMBED_URL_BASE . "?" . $oembedParams;

        // phpcs:disable Generic.Files.LineLength
        $data = [
            "version" => 1,
            "type" => "rich",
            "provider_name" => "SoundCloud",
            "provider_url" => "http://soundcloud.com",
            "height" => 400,
            "width" => "100%",
            "title" => "Old Town Road (Remix) [feat. Billy Ray Cyrus] by Lil Nas X",
            "description" => null,
            "thumbnail_url" => "http://i1.sndcdn.com/artworks-7PqTQwTM5TmY-0-t500x500.jpg",
            "html" => "<iframe width=\"100%\" height=\"400\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F600964365&show_artwork=true\"></iframe>",
            "author_name" => "Lil Nas X",
            "author_url" => "https://soundcloud.com/secret-service-862007284",
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
        $libXMLUseInternalErrors = libxml_use_internal_errors();
        libxml_use_internal_errors(true); // Tell DOMDocument::loadHTML to shutup about invalid markup in the oEmbed response.
        $embed = $this->factory->createEmbedForUrl($url);
        libxml_use_internal_errors($libXMLUseInternalErrors);

        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                "trackID" => $trackID,
                "url" => $url,
                "embedType" => SoundCloudEmbed::TYPE,
                "name" => $data["title"],
                "showArtwork" => $showArtwork,
                "useVisualPlayer" => $useVisualPlayer,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new SoundCloudEmbed($embedData);
        $this->assertInstanceOf(SoundCloudEmbed::class, $dataEmbed);
    }
}
