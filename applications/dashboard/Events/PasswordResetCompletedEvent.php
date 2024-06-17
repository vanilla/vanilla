<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event triggered when a user completes a password reset.
 */
class PasswordResetCompletedEvent extends BasicAuditLogEvent
{
    public function __construct(int $userID)
    {
        parent::__construct();
        // The user isn't necessarily "sessioned" in. Instead we attribute it to the reset user.
        $this->sessionUserID = $userID;
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "passwordReset_completed";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User reset their password.";
    }
}
