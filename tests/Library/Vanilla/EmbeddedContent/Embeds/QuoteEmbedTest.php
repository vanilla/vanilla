<?php


/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\EmbeddedContent\Embeds;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;
use VanillaTests\ContainerTestCase;

/**
 * Test for the individual linkembed.
 */
class QuoteEmbedTest extends ContainerTestCase {
    /**
     * Ensure we can create discussion embed from the old data format that might still
     * live in the DB.
     */
    public function testLegacyDiscussionFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://dev.vanilla.localhost/discussion/8/test-file-upload",
    "type": "quote",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": null,
    "width": null,
    "attributes": {
        "discussionID": 8,
        "name": "test file upload",
        "bodyRaw": [
            {
                "insert": {
                    "embed-external": {
                        "data": {
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
                    }
                }
            }
        ],
        "dateInserted": "2019-06-14T14:09:45+00:00",
        "dateUpdated": null,
        "insertUser": {
            "userID": 4,
            "name": "Karen A. Thomas",
            "photoUrl": "https://images.v-cdn.net/stubcontent/avatar_01.png",
            "dateLastActive": "2019-06-14T18:32:27+00:00"
        },
        "url": "https://dev.vanilla.localhost/discussion/8/test-file-upload",
        "format": "Rich"
    }
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        // This should not throw any exception.
        $dataEmbed = new QuoteEmbed($oldData);
        $this->assertInstanceOf(QuoteEmbed::class, $dataEmbed);
    }

    /**
     * Ensure we can create a comment embed from the old data format that might still
     * live in the DB.
     */
    public function testLegactCommentFormat() {
        $oldDataJSON = <<<JSON
{
    "url": "https://dev.vanilla.localhost/discussion/comment/5#Comment_5",
    "type": "quote",
    "name": null,
    "body": null,
    "photoUrl": null,
    "height": null,
    "width": null,
    "attributes": {
        "commentID": 5,
        "bodyRaw": [{ "insert": "Testtes test\\n" }],
        "dateInserted": "2019-06-17T18:52:20+00:00",
        "dateUpdated": null,
        "insertUser": {
            "userID": 2,
            "name": "admin",
            "photoUrl": "https://dev.vanilla.localhost/uploads/userpics/022/nWZ7BPS4F5HHQ.png",
            "dateLastActive": "2019-06-17T15:09:52+00:00"
        },
        "url": "https://dev.vanilla.localhost/discussion/comment/5#Comment_5",
        "format": "Rich"
    }
}
JSON;

        $oldData = json_decode($oldDataJSON, true);
        // This should not throw any exception.
        $dataEmbed = new QuoteEmbed($oldData);
        $this->assertInstanceOf(QuoteEmbed::class, $dataEmbed);
    }
}
