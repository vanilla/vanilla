<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\WistiaEmbed;
use VanillaTests\ContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class WistiaEmbedTest extends ContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://dave.wistia.com/medias/0k5h1g1chs",
    "type": "wistia",
    "name": "Lenny Delivers a Video - oEmbed glitch",
    "body": null,
    "photoUrl":
        "https://embed-ssl.wistia.com/deliveries/99f3aefb8d55eef2d16583886f610ebedd1c6734.jpg?image_crop_resized=960x540",
    "height": 540,
    "width": 960,
    "attributes": {
        "thumbnail_width": 960,
        "thumbnail_height": 540,
        "postID": "0k5h1g1chs",
        "embedUrl": "https://fast.wistia.net/embed/iframe/0k5h1g1chs"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new WistiaEmbed($data);
        $this->assertInstanceOf(WistiaEmbed::class, $embed);
    }
}
