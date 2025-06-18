<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event triggered when a user's roles are modified.
 */
class UserRoleModificationEvent extends BasicAuditLogEvent
{
    /**
     * @param string $userName
     * @param int $userID
     * @param array $rolesAdded
     * @param array $rolesRemoved
     */
    public function __construct(string $userName, int $userID, array $rolesAdded, array $rolesRemoved)
    {
        parent::__construct([
            "rolesAdded" => $rolesAdded,
            "rolesRemoved" => $rolesRemoved,
            "userID" => $userID,
            "userName" => $userName,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "user_roleModification";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        $result = "User's roles were modified: `{$context["userName"]}`";
        return $result;
    }
}
