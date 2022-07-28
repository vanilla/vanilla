<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use BaseTrackableSearchSchema;
use Garden\Schema\Schema;
use Vanilla\Events\SearchAllEvent;

/**
 * Provider for search events.
 */
class SearchAllEventProvider implements EventProviderInterface
{
    /**
     * Get a search event.
     *
     * @param array $body
     * @return object
     */
    public function getEvent(array $body): object
    {
        $baseSchema = new BaseTrackableSearchSchema();
        $schema = $baseSchema->merge(
            Schema::parse([
                "title:s?",
                "author:o?" => Schema::parse([
                    "authorID:a?" => ["items" => ["type" => "integer"]],
                    "authorName:a?" => ["items" => ["type" => "string"]],
                ]),
            ])
        );
        $validatedBody = $schema->validate($body);
        return new SearchAllEvent($validatedBody);
    }

    /**
     * Determine whether this provider can handle the request.
     *
     * @param array $body
     * @return bool
     */
    public function canHandleRequest(array $body): bool
    {
        $type = $body["type"] ?? null;
        $domain = $body["domain"] ?? null;
        return strtolower($type) === "search" && strtolower($domain) === "all_content";
    }
}
