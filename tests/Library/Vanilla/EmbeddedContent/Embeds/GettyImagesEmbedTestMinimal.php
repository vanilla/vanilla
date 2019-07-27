<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\GettyImagesEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class GettyImagesEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://www.gettyimages.ca/detail/photo/explosion-of-a-cloud-of-powder-of-particles-of-royalty-free-image/810147408",
    "type": "getty",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": 345,
    "width": 498,
    "attributes": {
        "id": "VPkxdgtCQFx-rEo96WtR_g",
        "sig": "Mb27fqjaYbaPPFANi1BffcYTEvCcNHg0My7qzCNDSHo=",
        "items": "810147408",
        "isCaptioned": "false",
        "is360": "false",
        "tld": "com",
        "postID": "810147408"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new GettyImagesEmbed($data);
        $this->assertInstanceOf(GettyImagesEmbed::class, $embed);
    }
}
