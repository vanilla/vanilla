<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\Embeds\TwitterEmbed;
use Vanilla\EmbeddedContent\Factories\TwitterEmbedFactory;
use VanillaTests\MinimalContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the embed and factory.
 */
class TwitterEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var TwitterEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new TwitterEmbedFactory($this->httpClient);
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
            [ "https://twitter.com/vanilla/status/2766219326" ]
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $url = "https://twitter.com/vanilla/status/2766219326";
        $statusID = "2766219326";

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($url);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                "statusID" => $statusID,
                "url" => $url,
                "embedType" => TwitterEmbed::TYPE,
            ],
            $embedData,
            "Data can be fetched over the network to create the embed from a URL."
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new TwitterEmbed($embedData);
        $this->assertInstanceOf(TwitterEmbed::class, $dataEmbed);
    }
}
