<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Vanilla\Analytics\EventProviderInterface;
use Vanilla\Dashboard\Events\SearchMembersEvent;

/**
 * Provider for search members events.
 */
class SearchMembersEventProvider implements EventProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getEvent(array $body): object
    {
        $schema = new BaseTrackableSearchSchema();
        $validatedBody = $schema->validate($body);
        return new SearchMembersEvent($validatedBody);
    }

    /**
     * @inheritDoc
     */
    public function canHandleRequest(array $body): bool
    {
        $type = $body["type"] ?? null;
        $domain = $body["domain"] ?? null;
        return strtolower($type) === "search" && strtolower($domain) === "members";
    }
}
