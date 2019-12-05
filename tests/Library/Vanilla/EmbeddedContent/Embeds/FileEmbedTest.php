<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\FileEmbed;
use VanillaTests\MinimalContainerTestCase;

/**
 * Validation logic test.
 */
class FileEmbedTest extends MinimalContainerTestCase {
    /**
     * Ensure we can create file embed embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDataFormat() {
        $oldDataJSON = <<<JSON
 {
    "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip",
    "type": "file",
    "attributes": {
        "mediaID": 62,
        "name": "___img_onload_prompt(1)_ (2).zip",
        "path": "150/LKE0S2FWLFUP.zip",
        "type": "application/zip",
        "size": 41,
        "active": 1,
        "insertUserID": 4,
        "dateInserted": "2019-06-14 14:09:38",
        "foreignID": 4,
        "foreignTable": "embed",
        "imageWidth": null,
        "imageHeight": null,
        "thumbWidth": null,
        "thumbHeight": null,
        "thumbPath": null,
        "foreignType": "embed",
        "url": "https://dev.vanilla.localhost/uploads/150/LKE0S2FWLFUP.zip"
    }
 }
JSON;

        $oldData = json_decode($oldDataJSON, true);
        $dataEmbed = new FileEmbed($oldData);
        $this->assertInstanceOf(FileEmbed::class, $dataEmbed);
    }
}
