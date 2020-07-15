<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Garden\Events;

use Vanilla\Events\EventAction;

/**
 * An event affecting a specific resource.
 */
class GenericResourceEvent extends ResourceEvent {

    /**
     * Create the event.
     *
     * @param string $type
     * @param string $action
     * @param array $payload
     * @param array $sender
     */
    public function __construct(string $type, string $action, array $payload, ?array $sender = null) {
        parent::__construct($action, $payload, $sender);
        $this->type = $type;
    }
}
