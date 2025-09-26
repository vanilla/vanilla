<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when a user spoofs in as another user.
 */
class UserSpoofEvent extends BasicAuditLogEvent
{
    public function __construct(int $spoofedUserID, string $spoofedUserName, array $context = [])
    {
        parent::__construct(
            [
                "spoofedUserID" => $spoofedUserID,
                "spoofedUserName" => $spoofedUserName,
            ] + $context
        );
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "user_spoof";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "User spoofed in as `{$context["spoofedUserName"]}`";
    }
}
