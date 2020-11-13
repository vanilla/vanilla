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
use Vanilla\Utility\ModelUtils;

/**
 * Represent a user resource event.
 */
class UserEvent extends ResourceEvent implements LoggableEventInterface {

    /**
     * The users API needs expand all to be applied so certain fields work correctly.
     *
     * @return array
     */
    public function getApiParams(): ?array {
        return [
            'expand' => [ModelUtils::EXPAND_CRAWL, ModelUtils::EXPAND_ALL],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context[Logger::FIELD_TARGET_USERID] = $context['user']['userID'];
        $context[Logger::FIELD_TARGET_USERNAME] = $context['user']['name'];

        $log = new LogEntry(
            LogLevel::INFO,
            $this->makeLogMessage($this->getAction(), $context[Logger::FIELD_TARGET_USERNAME] ?? null, $context[Logger::FIELD_USERNAME] ?? null),
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
