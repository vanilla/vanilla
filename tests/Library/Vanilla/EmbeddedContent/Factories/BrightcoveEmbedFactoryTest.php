<?php
/**
 * @author Raphaël Bergina <raphael.bergina@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\EmbeddedContent\Factories;

use Vanilla\EmbeddedContent\Embeds\BrightcoveEmbed;
use Vanilla\EmbeddedContent\Factories\BrightcoveEmbedFactory;
use VanillaTests\MinimalContainerTestCase;

/**
 * Tests for the Brightcove embed factory.
 */
class BrightcoveEmbedFactoryTest extends MinimalContainerTestCase {

    /** @var BrightcoveEmbedFactory */
    private $factory;

    /**
     * Set the factory.
     */
    public function setUp(): void {
        parent::setUp();
        $this->factory = new BrightcoveEmbedFactory();
    }

    /**
     * Test that all domain types are supported.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testSupportedUrls(string $urlToTest) {
        $this->assertTrue($this->factory->canHandleUrl($urlToTest));
    }

    /**
     * Return an array of supported urls.
     *
     * @return array
     */
    public function supportedUrlsProvider(): array {
        return [
            ['https://players.brightcove.net/1160438696001/hUGC1VhwM_default/index.html?videoId=5842888344001']
        ];
    }

    /**
     * Test the Brightcove Embed instantiation.
     *
     * @param string $urlToTest
     * @dataProvider supportedUrlsProvider
     */
    public function testCreateEmbedForUrl(string $urlToTest) {
        $parameters = [];
        parse_str(
            parse_url($urlToTest, PHP_URL_QUERY) ?? "",
            $parameters
        );
        preg_match("`^/(?<account>[\w]+)\/(?<player>[\w]+)_(?<embed>[\w]+)\/index\.html$`", parse_url($urlToTest, PHP_URL_PATH), $matches);

        $data = [
            'embedType' => BrightcoveEmbed::TYPE,
            'url' => $urlToTest,
            'videoID' => $parameters['videoId'] ?? null,
            'account' => $matches['account'] ?? null,
            'playerID' => $matches['player'] ?? null,
            'playerEmbed' => $matches['embed'] ?? null,
        ];

        $BrightcoveEmbed = $this->factory->createEmbedForUrl($urlToTest);
        $embedData = $BrightcoveEmbed->jsonSerialize();

        $this->assertEquals($data, $embedData, 'Data can be used to render embed.');

        $embed = new BrightcoveEmbed($embedData);
        $this->assertInstanceOf(BrightcoveEmbed::class, $embed);
    }
}
