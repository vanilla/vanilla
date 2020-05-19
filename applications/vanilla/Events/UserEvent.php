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
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface {
    use LoggableEventTrait;

    /**
     * Get an optionally customized version of the payload for a log entry..
     *
     * @return array
     */
    private function getLogPayload(): array {
        return array_intersect_key($this->getPayload(), ['userID' => 1, 'name' => 1]);
    }
}
