<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggableEventTrait;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface {
    use LoggableEventTrait;

    /**
     * @inheritDoc
     */
    private function getLogPayload(): array {
        $payload = $this->getPayload();
        $payload["discussion"] = array_intersect_key($payload["discussion"] ?? [], [
            "discussionID" => true,
            "dateInserted" => true,
            "dateUpdated" => true,
            "updateUserID" => true,
            "insertUserID" => true,
            "url" => true,
            "name" => true,
        ]);
        return $payload;
    }
}
