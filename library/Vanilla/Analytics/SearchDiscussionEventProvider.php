<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use DiscussionModel;
use Garden\Schema\Schema;
use Vanilla\Community\Events\SearchDiscussionEvent;

/**
 * Provider for discussion search events.
 */
class SearchDiscussionEventProvider implements \Vanilla\Analytics\EventProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getEvent(array $body): object
    {
        $baseSchema = new \BaseTrackableSearchSchema();
        $schema = $baseSchema->merge(
            Schema::parse([
                "title:s?",
                "author:o?" => Schema::parse([
                    "authorID:a?" => ["items" => ["type" => "integer"]],
                    "authorName:a?" => ["items" => ["type" => "string"]],
                ]),
                "recordType:a?" => [
                    "items" => ["type" => "string", "enum" => DiscussionModel::apiDiscussionTypes()],
                ],
                "tag:o?" => Schema::parse([
                    "tagID:a?" => ["items" => ["type" => "integer"]],
                    "tagName:a?" => ["items" => ["type" => "string"]],
                ]),
                "category:o?" => Schema::parse([
                    "categoryID:a?" => ["items" => ["type" => "integer"]],
                    "categoryName:a?" => ["items" => ["type" => "string"]],
                ]),
            ])
        );

        $validatedBody = $schema->validate($body);
        return new SearchDiscussionEvent($validatedBody);
    }

    /**
     * @inheritDoc
     */
    public function canHandleRequest(array $body): bool
    {
        $type = $body["type"] ?? null;
        $domain = $body["domain"] ?? null;
        return strtolower($type) === "search" && strtolower($domain) === "discussions";
    }
}
