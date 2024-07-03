<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Psr\Log\LogLevel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Resource event fired when a role is added/edited/deleted.
 */
class RoleEvent extends ResourceEvent implements LoggableEventInterface
{
    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);

        $log = new LogEntry(
            LogLevel::INFO,
            $this->makeLogMessage($this->getAction(), $this->getPayload()["role"]["name"]),
            $context
        );
        return $log;
    }

    /**
     * Make a nice log message.
     */
    protected function makeLogMessage(string $action, string $roleName): string
    {
        switch ($action) {
            case ResourceEvent::ACTION_INSERT:
                $message = "Role `{$roleName}` was created.";
                break;
            case ResourceEvent::ACTION_UPDATE:
                $message = "Role `{$roleName}` was updated.";
                break;
            case ResourceEvent::ACTION_DELETE:
                $replacementRoleName = $this->getPayload()["replacementRole"]["name"] ?? null;
                $countAffected = $this->getPayload()["countAffectedUsers"] ?? 0;
                if ($replacementRoleName) {
                    $message = "Role `{$roleName}` was deleted and replaced with `$replacementRoleName`, affecting `{$countAffected}` users.";
                } else {
                    $message = "Role `{$roleName}` was deleted, affecting `{$countAffected}` users.";
                }
                break;
            default:
                $message = LoggerUtils::resourceEventLogMessage($this);
                break;
        }
        return $message;
    }

    /**
     * Make sure these are always logged.
     *
     * @return bool
     */
    public function bypassLogFilters(): bool
    {
        return true;
    }
}
