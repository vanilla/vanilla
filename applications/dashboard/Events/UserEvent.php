<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\AliasLoader;
use Vanilla\Logger;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggableEventTrait;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface {
    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);

        $log = new LogEntry(
            LogLevel::INFO,
            $this->makeLogMessage($this->getAction(), $context['targetName'] ?? null, $context['username'] ?? null),
            $context
        );
        return $log;
    }

    /**
     * Make a nice log message depending on who the acting and target users are.
     *
     * @param string $action
     * @param string $targetName
     * @param string|null $username
     * @return string
     */
    private function makeLogMessage(string $action, string $targetName, ?string $username): string {
        switch ($action) {
            case ResourceEvent::ACTION_INSERT:
                return $username ?
                    "User {targetName} was added by {username}." :
                    "User {targetName} registered.";
            case ResourceEvent::ACTION_UPDATE:
                return $username !== $targetName ?
                    "User {targetName} was updated by {username}." :
                    "User {targetName} was updated.";
            case ResourceEvent::ACTION_DELETE:
                return $username !== $targetName ?
                    "User {targetName} was deleted by {username}." :
                    "User {targetName} was deleted.";
        }
    }
}

AliasLoader::createAliases(UserEvent::class);
