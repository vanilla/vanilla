<?php
/**
 * Created by PhpStorm.
 * User: chris
 * Date: 2018-07-10
 * Time: 11:54 AM
 */

namespace VanillaTests\Library\Vanilla\Embeds;


/**
 */
namespace VanillaTests\Library\Vanilla\Embeds;
use VanillaTests\SharedBootstrapTestCase;
;
use Vanilla\Embeds\TwitchEmbed;

/**
 * Class TwitchEmbedTest
 *
 * @package VanillaTests\Library\Vanilla\Embeds
 */
class TwitchEmbedTest extends SharedBootstrapTestCase {

    /**
     * Test the getUrlInformation method.
     *
     * @array $data Url and urlType for the embed.
     * @array $expected The expected video ID.
     * @dataProvider urlProvider
     */
    public function testGetUrlInformation($data, $expected) {
        $twitchEmbed = new TwitchEmbed();
        $urlInfo = $twitchEmbed->getUrlInformation($data['url']);

        $this->assertEquals($expected['id'], $urlInfo);
        $this->assertEquals($data['urlType'], $twitchEmbed->urlType);
    }

    /**
     * @param $data
     * @param $expected
     *  @dataProvider urlProvider
     */
    public function testGetEmbedUrl($data, $expected) {
        $twitchEmbed = new TwitchEmbed();
        $twitchEmbed->urlType = $data['urlType'];
        $embedURL = $twitchEmbed->getEmbedUrl($data['videoID'], $data['query']);
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
                    "query" => null,
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
                ],
                [
                    "id" => "278205026",
                ],
            ],
            [
                [
                    "url" => "https://clips.twitch.tv/MoistWonderfulMooseRalpherZ",
                    "urlType" => "clip",
                ],
                [
                    "id" => "MoistWonderfulMooseRalpherZ",
                ],
            ],
            [
                [
                    "url" => "https://www.twitch.tv/ninja",
                    "urlType" => "channel",
                ],
                [
                    "id" => "ninja",
                ],
            ],
        ];
        return $data;
    }
}