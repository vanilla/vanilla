<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\VimeoEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class VimeoEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://vimeo.com/264197456",
    "type": "vimeo",
    "name": "Vimeo",
    "body": null,
    "photoUrl": "https://i.vimeocdn.com/video/694532899_640.jpg",
    "height": 272,
    "width": 640,
    "attributes": {
        "thumbnail_width": 640,
        "thumbnail_height": 272,
        "videoID": "264197456",
        "embedUrl": "https://player.vimeo.com/video/264197456?autoplay=1"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new VimeoEmbed($data);
        $this->assertInstanceOf(VimeoEmbed::class, $embed);
    }
}
