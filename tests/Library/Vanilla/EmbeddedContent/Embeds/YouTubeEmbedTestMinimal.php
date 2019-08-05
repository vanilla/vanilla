<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\YouTubeEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class YouTubeEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://www.youtube.com/watch?v=fy0fTFpqT48&t=2s",
    "type": "youtube",
    "name": "Attack of the Killer Tomatoes - Trailer",
    "body": null,
    "photoUrl": "https://i.ytimg.com/vi/fy0fTFpqT48/hqdefault.jpg",
    "height": 344,
    "width": 459,
    "attributes": {
        "thumbnail_width": 480,
        "thumbnail_height": 360,
        "videoID": "fy0fTFpqT48",
        "start": 2,
        "embedUrl": "https://www.youtube.com/embed/fy0fTFpqT48?feature=oembed&autoplay=1&start=2"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new YouTubeEmbed($data);
        $this->assertInstanceOf(YouTubeEmbed::class, $embed);
    }
}
