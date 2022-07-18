<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Schema\Schema;
use Vanilla\Events\SearchPlacesEvent;

/**
 * Provide places search events.
 */
class SearchPlacesEventProvider implements EventProviderInterface
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
                "description:s?",
                "indexes:a?" => [
                    "items" => ["type" => "string"],
                ],
            ])
        );
        $validatedBody = $schema->validate($body);
        return new SearchPlacesEvent($validatedBody);
    }

    /**
     * @inheritDoc
     */
    public function canHandleRequest(array $body): bool
    {
        $type = $body["type"] ?? null;
        $domain = $body["domain"] ?? null;
        return strtolower($type) === "search" && strtolower($domain) === "places";
    }
}
