<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\LinkEmbed;
use VanillaTests\ContainerTestCase;

/**
 * Test for the individual linkembed.
 */
class LinkEmbedTest extends ContainerTestCase {
    /**
     * Ensure we can create giphy embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://vanillaforums.com/en/",
    "type": "link",
    "name": "Online Community Software and Customer Forum Software by Vanilla Forums",
    "body": "Engage your customers with a vibrant and modern online customer community forum.",
    "photoUrl": "https://vanillaforums.com/images/metaIcons/vanillaForums.png",
    "height": null,
    "width": null,
    "attributes": []
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        // This should not throw any exception.
        $dataEmbed = new LinkEmbed($oldData);
        $this->assertInstanceOf(LinkEmbed::class, $dataEmbed);
    }
}
