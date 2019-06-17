<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Garden\Http\HttpClient;
use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use Vanilla\EmbeddedContent\Embeds\GiphyEmbedFactory;
use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use Vanilla\EmbeddedContent\Embeds\ScrapeEmbedFactory;
use VanillaTests\ContainerTestCase;
use VanillaTests\Fixtures\MockHttpClient;
use VanillaTests\Fixtures\MockPageScraper;

/**
 * Tests for the giphy embed and factory.
 */
class ScrapeEmbedFactoryTest extends ContainerTestCase {

    /** @var ScrapeEmbedFactory */
    private $factory;

    /** @var MockPageScraper */
    private $pageScraper;

    /** @var HttpClient */
    private $httpClient;

    /**
     * Set the factory and client.
     */
    public function setUp() {
        parent::setUp();
        $this->pageScraper = new MockPageScraper();
        $this->httpClient = new MockHttpClient();
        $this->factory = new ScrapeEmbedFactory($this->pageScraper, $this->httpClient);
    }


    /**
     * Test that all giphy domain types are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedDomains
     */
    public function testSupportedDomains(string $urlToTest) {
        $this->assertTrue(
            $this->factory->canHandleUrl($urlToTest),
            "LinkEmbedFactory should match every URL."
        );
    }

    /**
     * @return array
     */
    public function supportedDomains(): array {
        return [
            ['https://tasdfasdf.com/asdfasd4-23e1//asdf31/1324'],
            ['http://asd.com'], // Empty paths.
            ['https://testasd.com/.png']
        ];
    }

    /**
     * Test network request fetching and handling.
     */
    public function testCreateEmbedForUrl() {
        $urlToCheck = 'https://test.com';

        $name = "Hello Title";
        $description = "Hello description";
        $images = ["https://test.com/image.png", "https://other.com/pic.jpg"];
        $this->pageScraper->addMockResponse($urlToCheck, [
            'Title' => $name,
            'Description' => $description,
            'Images' => $images,
            'Url' => $urlToCheck,
        ]);

        // Check over the network.
        $embed = $this->factory->createEmbedForUrl($urlToCheck);
        $this->assertInstanceOf(LinkEmbed::class, $embed);
        $embedData = $embed->jsonSerialize();
        $this->assertEquals(
            [
                'name' => $name,
                'url' => $urlToCheck, // The original URL.
                'embedType' => LinkEmbed::TYPE,
                'body' => $description,
                'photoUrl' => $images[0],
            ],
            $embedData,
            'Data cna be fetched over the network to create the embed from a URL.'
        );
    }
}
