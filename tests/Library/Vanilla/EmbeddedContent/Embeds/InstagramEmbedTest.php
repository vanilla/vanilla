<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\InstagramEmbed;
use VanillaTests\ContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class InstagramEmbedTest extends ContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://www.instagram.com/p/BTjnolqg4po/?taken-by=vanillaforums",
    "type": "instagram",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": null,
    "width": null,
    "attributes": {
        "permaLink": "https://www.instagram.com/p/BTjnolqg4po",
        "isCaptioned": true,
        "versionNumber": "8"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new InstagramEmbed($data);
        $this->assertInstanceOf(InstagramEmbed::class, $embed);
    }
}
