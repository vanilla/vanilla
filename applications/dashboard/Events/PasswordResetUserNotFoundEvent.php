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
class PasswordResetUserNotFoundEvent extends BasicAuditLogEvent
{
    public function __construct(string $emailOrUsername)
    {
        parent::__construct([
            "emailOrUsername" => $emailOrUsername,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "passwordReset_userNotFound";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "A password reset was attempted for `{$context["emailOrUsername"]}` but no user was found with that email/username.";
    }
}
