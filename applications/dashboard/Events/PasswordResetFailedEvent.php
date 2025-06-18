<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event triggered when a user is not found during a password reset.
 */
class PasswordResetFailedEvent extends BasicAuditLogEvent
{
    public function __construct(string $userName, int $userID, string $reason)
    {
        parent::__construct([
            "reason" => $reason,
            "userName" => $userName,
            "userID" => $userID,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "passwordReset_failed";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "{$context["userName"]} tried to reset their password but the password reset failed.";
    }
}
