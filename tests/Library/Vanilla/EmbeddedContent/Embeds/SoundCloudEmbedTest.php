<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\SoundCloudEmbed;
use VanillaTests\ContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class SoundCloudEmbedTest extends ContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://soundcloud.com/uiceheidd/sets/juicewrld-the-mixtape",
    "type": "soundcloud",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": 450,
    "width": null,
    "attributes": {
        "visual": "true",
        "showArtwork": "true",
        "postID": "330864225",
        "embedUrl": "https://w.soundcloud.com/player/?url=https://api.soundcloud.com/playlists/"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new SoundCloudEmbed($data);
        $this->assertInstanceOf(SoundCloudEmbed::class, $embed);
    }
}
