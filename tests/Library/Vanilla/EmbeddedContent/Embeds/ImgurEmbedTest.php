<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\ImgurEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class ImgurEmbedTest extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://imgur.com/gallery/arP2Otg",
    "type": "imgur",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": null,
    "width": null,
    "attributes": {
        "postID": "arP2Otg",
        "isAlbum": false
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new ImgurEmbed($data);
        $this->assertInstanceOf(ImgurEmbed::class, $embed);
    }
}
