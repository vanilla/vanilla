<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\CodePenEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Validation logic test.
 */
class CodePenEmbedTestMinimal extends MinimalContainerTestCase {
    /**
     * Ensure we can create code pen embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://codepen.io/hiroshi_m/pen/YoKYVv",
    "type": "codepen",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": 300,
    "width": null,
    "attributes": {
        "id": "cp_embed_YoKYVv",
        "embedUrl": "https://codepen.io/hiroshi_m/embed/preview/YoKYVv?theme-id=0",
        "style": { "width": " 100%", "overflow": "hidden" }
    }
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = new CodePenEmbed($oldData);
        $this->assertInstanceOf(CodePenEmbed::class, $dataEmbed);
    }
}
