<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event triggered when we send a password reset email.
 */
class PasswordResetEmailSentEvent extends BasicAuditLogEvent
{
    /**
     * @param string $userEmail
     * @param string $userName
     * @param string $userID
     */
    public function __construct(string $userEmail, string $userName, string $userID)
    {
        parent::__construct([
            "user" => [
                "email" => $userEmail,
                "name" => $userName,
                "userID" => $userID,
            ],
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "passwordReset_emailSent";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "A password reset email was sent for user `{$context["user"]["name"]}`";
    }
}
