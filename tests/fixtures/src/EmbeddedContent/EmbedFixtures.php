<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures\EmbeddedContent;

use Vanilla\EmbeddedContent\Embeds\QuoteEmbed;

/**
 * Fixtures for the embed system values.
 */
class EmbedFixtures {

    /**
     * Wrap some embed data in a full insert.
     *
     * @param mixed $data The value of the embed.
     *
     * @return array
     */
    public static function embedInsert($data): array {
        return [
            "insert" => [
                "embed-external" => [
                    "data" => $data,
                ],
            ],
        ];
    }

    /**
     * Generate a discussion embed fixture.
     *
     * @param string $username
     *
     * @return array
     */
    public static function discussion(string $username = "DiscussionQuote Username"): array {
        return [
            "url" => "https://dev.vanilla.localhost/discussion/8/test-file-upload",
            "embedType" => QuoteEmbed::TYPE,
            "recordType" => "discussion",
            "recordID" => 8,
            "name" => "discussion embed fixture title",
            "bodyRaw" => [[ "insert" => "test test\\n" ]],
            "dateInserted" => "2019-06-14T14:09:45+00:00",
            "dateUpdated" => null,
            "insertUser" => [
                "userID" => 4,
                "name" => $username,
                "photoUrl" => "https://images.v-cdn.net/stubcontent/avatar_01.png",
                "dateLastActive" => "2019-06-14T18:32:27+00:00",
            ],
            "format" => "Rich"
        ];
    }
    /**
     * Generate a discussion embed fixture.
     *
     * @param string $username
     *
     * @return array
     */
    public static function comment(string $username = "CommentQuote Username"): array {
        return [
            "url" => "https://dev.vanilla.localhost/discussion/comment/5",
            "embedType" => QuoteEmbed::TYPE,
            "recordType" => "comment",
            "recordID" => 8,
            "bodyRaw" => [[ "insert" => "test test\\n" ]],
            "dateInserted" => "2019-06-14T14:09:45+00:00",
            "dateUpdated" => null,
            "insertUser" => [
                "userID" => 4,
                "name" => $username,
                "photoUrl" => "https://images.v-cdn.net/stubcontent/avatar_01.png",
                "dateLastActive" => "2019-06-14T18:32:27+00:00",
            ],
            "format" => "Rich"
        ];
    }
}
