<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when a user signs in.
 */
class UserSignInFailedEvent extends BasicAuditLogEvent
{
    public function __construct(string $emailOrUsername, string $failureMode)
    {
        parent::__construct([
            "emailOrUsername" => $emailOrUsername,
            "failureMode" => $failureMode,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "user_signinFailed";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Someone tried to sign in with the email or username `{$context["emailOrUsername"]}` but failed.";
    }
}
