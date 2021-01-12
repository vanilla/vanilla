<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\InstagramEmbed;
use Vanilla\EmbeddedContent\Factories\InstagramEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class InstagramEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var InstagramEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp(): void {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new InstagramEmbedFactory($this->httpClient);
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
            [
                "https://www.instagram.com/p/By_Et7NnKgL",
                "https://instagr.am/p/By_Et7NnKgL",
            ],
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://www.instagram.com/p/By_Et7NnKgL";
        $postID = "By_Et7NnKgL";

        $embed = $this->factory->createEmbedForUrl($url);

        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                "postID" => $postID,
                "url" => $url,
                "embedType" => InstagramEmbed::TYPE,
                "name" => InstagramEmbedFactory::NAME,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new InstagramEmbed($embedData);
        $this->assertInstanceOf(InstagramEmbed::class, $dataEmbed);
    }
}
