<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Factories;

use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use Vanilla\EmbeddedContent\Factories\GiphyEmbedFactory;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for the giphy embed and factory.
 */
class GiphyEmbedFactoryTest extends ContainerTestCase {

    /** @var GiphyEmbedFactory */
    private $factory;

    /** @var MockHttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->httpClient = new MockHttpClient();
        $this->factory = new GiphyEmbedFactory($this->httpClient);
    }


    /**
     * Test that all giphy domain types are supported.
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
            ['https://gph.is/291u1MC'],
            ['https://giphy.com/gifs/howtogiphygifs-how-to-XatG8bioEwwVO'],
            ['https://media.giphy.com/media/kW8mnYSNkUYKc/giphy.gif']
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $urlToCheck = 'https://giphy.com/gifs/howtogiphygifs-how-to-XatG8bioEwwVO';
        $endpoint = GiphyEmbedFactory::OEMBED_URL_BASE . '?url=' . urlencode($urlToCheck);

        $title = 'Hello title';
        $width = 500;
        $height = 400;
        $finalUrl = 'https://media.giphy.com/media/kW8mnYSNkUYKc/giphy.gif';

        $this->httpClient->addMockResponse(
            $endpoint,
            new HttpResponse(
                200,
                'Content-Type: application/json',
                json_encode([
                    'width' => $width,
                    'title' => $title,
                    'height' => $height,
                    'url' => $finalUrl,
                ])
            )
        );

        // Check over the network.
        $giphyEmbed = $this->factory->createEmbedForUrl($urlToCheck);
        $embedData = $giphyEmbed->jsonSerialize();
        $this->assertEquals(
            [
                'width' => $width,
                'name' => $title,
                'height' => $height,
                'url' => $urlToCheck, // The original URL.
                'embedType' => GiphyEmbed::TYPE,
                'giphyID' => 'kW8mnYSNkUYKc',
            ],
            $embedData,
            'Data cna be fetched over the network to create the embed from a URL.'
        );

        // Just verify that this doesn't throw an exception.
        $dataEmbed = new GiphyEmbed($embedData);
        $this->assertInstanceOf(GiphyEmbed::class, $dataEmbed);
    }
}
