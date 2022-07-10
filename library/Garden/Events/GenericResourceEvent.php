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
     * @param string $type
     */
    public function setType(string $type): void {
        $this->type = $type;
    }
}
