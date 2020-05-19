<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Vanilla\AliasLoader;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggableEventTrait;

/**
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface {
    use LoggableEventTrait;

    /**
     * @inheritDoc
     */
    private function getLogPayload(): array {
        $payload = $this->getPayload();
        $payload["user"] = array_intersect_key($payload["user"] ?? [], [
            "userID" => true,
            "name" => true,
        ]);
        return $payload;
    }
}

AliasLoader::createAliases(UserEvent::class);
