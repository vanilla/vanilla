<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\TwitchEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class TwitchEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "http://clips.twitch.tv/KnottyOddFishShazBotstix",
    "type": "twitch",
    "name": "Lights! Camera! Action!",
    "body": null,
    "photoUrl": "https://clips-media-assets2.twitch.tv/AT-cm%7C267415465-preview.jpg",
    "height": 351,
    "width": 620,
    "attributes": {
        "videoID": "KnottyOddFishShazBotstix",
        "embedUrl": "https://clips.twitch.tv/embed?clip=KnottyOddFishShazBotstix"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new TwitchEmbed($data);
        $this->assertInstanceOf(TwitchEmbed::class, $embed);
    }
}
