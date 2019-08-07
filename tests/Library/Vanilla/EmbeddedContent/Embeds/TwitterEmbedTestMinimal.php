<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\TwitterEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Verify embed class capabilities.
 */
class TwitterEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create an embed from legacy data that might still live in the DB.
     */
    public function testLegacyDataFormat() {
        $legacyJSON = <<<JSON
{
    "url": "https://twitter.com/hootsuite/status/1009883861617135617",
    "type": "twitter",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": null,
    "width": null,
    "attributes": {
        "statusID": "1009883861617135617"
    }
}
JSON;

        $data = json_decode($legacyJSON, true);
        $embed = new TwitterEmbed($data);
        $this->assertInstanceOf(TwitterEmbed::class, $embed);
    }
}
