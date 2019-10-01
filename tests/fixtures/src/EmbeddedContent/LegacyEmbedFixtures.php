<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

/**
 * Fixtures for the legacy embed system values.
 */
class LegacyEmbedFixtures {

    /**
     * @return string
     */
    public static function discussion(): string {
        return <<<JSON
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
        "bodyRaw": [{ "insert": "Testtes test\\n" }],
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
    }

    /**
     * @return string
     */
    public static function comment(): string {
        return <<<JSON
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
    }
}
