<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Schema\Schema;
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
    private function getLogPayloadSchema(): ?Schema {
        $result = Schema::parse([
            "discussion:o" => [
                "discussionID",
                "dateInserted",
                "dateUpdated",
                "updateUserID",
                "insertUserID",
                "url",
                "name",
            ]
        ]);

        return $result;
    }
}
