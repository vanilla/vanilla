<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Formatting\Embeds;

use VanillaTests\SharedBootstrapTestCase;
use Vanilla\Formatting\Embeds\TwitchEmbed;

class TwitchEmbedTest extends SharedBootstrapTestCase {
    /**
     * Test the getUrlInformation method.
     *
     * @param array $data Url and urlType for the embed.
     * @param array $expected The expected video ID.
     * @dataProvider urlProvider
     */
    public function testGetUrlInformation($data, $expected) {
        $twitchEmbed = new TwitchEmbed();
        $urlInfo = $twitchEmbed->getUrlInformation($data['url']);

        $this->assertEquals($expected['id'], $urlInfo);
        $this->assertEquals($data['urlType'], $twitchEmbed->urlType);
    }

    /**
     * Test the getEmbedUrl method.
     *
     * @param array $data The embed urlType, videoID and queryInfo.
     * @param array $expected The expected embedUrl.
     * @dataProvider urlProvider
     */
    public function testGetEmbedUrl($data, $expected) {
        $twitchEmbed = new TwitchEmbed();
        $twitchEmbed->urlType = $data['urlType'];
        $embedURL = $twitchEmbed->getEmbedUrl($data['videoID'], $data['queryInfo']);
        $this->assertEquals($expected['embedUrl'], $embedURL);
    }

    /**
     * Data Provider for GetUrlInformation.
     *
     * @return array $data
     */
    public function urlProvider() {
        $data = [
            [
                [
                    "url" => "https://www.twitch.tv/collections/xV9NVtPzEhW8pg",
                    "urlType" => "collection",
                    "videoID" => "xV9NVtPzEhW8pg",
                    "queryInfo" => null,
                ],
                [
                    "id" => "xV9NVtPzEhW8pg",
                    "embedUrl" => "https://player.twitch.tv/?collection=xV9NVtPzEhW8pg",
                ],
            ],
            [
                [
                    "url" => "https://www.twitch.tv/videos/278205026",
                    "urlType" => "video",
                    "videoID" => "278205026",
                    "queryInfo" => null,
                ],
                [
                    "id" => "278205026",
                    "embedUrl" => "https://player.twitch.tv/?video=v278205026",
                ],
            ],
            [
                [
                    "url" => "https://www.twitch.tv/videos/278205026",
                    "urlType" => "video",
                    "videoID" => "278205026",
                    "queryInfo" => [
                        "t" => "t=01h15m50s",
                        "autoplay" => "true"
                    ],
                ],
                [
                    "id" => "278205026",
                    "embedUrl" => "https://player.twitch.tv/?video=v278205026&autoplay=true&t=01h15m50s",
                ],
            ],
            [
                [
                    "url" => "https://clips.twitch.tv/MoistWonderfulMooseRalpherZ",
                    "urlType" => "clip",
                    "videoID" => "MoistWonderfulMooseRalpherZ",
                    "queryInfo" => null,
                ],
                [
                    "id" => "MoistWonderfulMooseRalpherZ",
                    "embedUrl" => "https://clips.twitch.tv/embed?clip=MoistWonderfulMooseRalpherZ",
                ],
            ],
            [
                [
                    "url" => "https://www.twitch.tv/ninja",
                    "urlType" => "channel",
                    "videoID" => "ninja",
                    "queryInfo" => null,
                ],
                [
                    "id" => "ninja",
                    "embedUrl" => "https://player.twitch.tv/?channel=ninja",
                ],
            ],
        ];
        return $data;
    }
}
