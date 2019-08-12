<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\GiphyEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Validation logic test.
 */
class GiphyEmbedTest extends MinimalContainerTestCase {
    /**
     * Ensure we can create giphy embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://media.giphy.com/media/JIX9t2j0ZTN9S/giphy.gif",
    "type": "giphy",
    "name": "Funny Cat GIF - Find & Share on GIPHY",
    "body": null,
    "photoUrl": null,
    "height": 720,
    "width": 720,
    "attributes": { "postID": "JIX9t2j0ZTN9S" }
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = new GiphyEmbed($oldData);
        $this->assertInstanceOf(GiphyEmbed::class, $dataEmbed);
    }
}
