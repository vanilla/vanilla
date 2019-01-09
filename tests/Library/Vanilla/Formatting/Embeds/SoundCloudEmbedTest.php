<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Embeds;

use Vanilla\Formatting\Embeds\SoundCloudEmbed;
use VanillaTests\SharedBootstrapTestCase;


class SoundCloudEmbedTest extends SharedBootstrapTestCase {

    /**
     * Test parsedHtmlResponse method.
     *
     * @param array $data HTML response from SoundCloud Embed.
     * @param array $expected The expected result from the parsed html.
     * @dataProvider htmlDataProvider
     */
    public function testParseHtmlResponse($data, $expected) {
        $soundCloudEmbed = new SoundCloudEmbed();
        $parsedHtml = $soundCloudEmbed->parseResponseHtml($data["html"]);
        $this->assertEquals($expected, $parsedHtml);
    }

    /**
     * Data Provider for testParseHtmlResponse.
     *
     * @return array $data
     */
    public function htmlDataProvider() {
        $data = [
            [
                [
                    "html" => "<iframe width=\"100%\" height=\"400\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Ftracks%2F444968403&show_artwork=true\"></iframe>",
                ],
                [
                    "attributes" => [
                            "visual" => "true",
                            "showArtwork" => "true",
                            "postID" => "444968403",
                            "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/tracks/",
                    ],
                ],
            ],
            [
                [
                    "html" => "<iframe width=\"100%\" height=\"450\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Fusers%2F132790246&show_artwork=true\"></iframe>",
                ],
                [
                    "attributes" => [
                        "visual" => "true",
                        "showArtwork" => "true",
                        "postID" => "132790246",
                        "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/users/",
                    ],
                ],
            ],
            [
                [
                    "html" => "<iframe width=\"100%\" height=\"450\" scrolling=\"no\" frameborder=\"no\" src=\"https://w.soundcloud.com/player/?visual=true&url=https%3A%2F%2Fapi.soundcloud.com%2Fplaylists%2F347750682&show_artwork=true\"></iframe>",
                ],
                [
                    "attributes" => [
                        "visual" => "true",
                        "showArtwork" => "true",
                        "postID" => "347750682",
                        "embedUrl" => "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/",
                    ],
                ],
            ],
        ];
        return $data;
    }
}
