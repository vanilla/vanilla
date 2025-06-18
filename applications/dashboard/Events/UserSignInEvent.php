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
class UserSignInEvent extends BasicAuditLogEvent
{
    public function __construct(int $userID)
    {
        parent::__construct([
            "userID" => $userID,
        ]);

        $this->sessionUserID = $userID;
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "user_signin";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User signed in.";
    }
}
