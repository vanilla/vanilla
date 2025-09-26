<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Premoderation;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Audit log event for when a post is rejected as such obvious spam that it skips the reporting/escalation queue altogether.
 */
class SuperSpamAuditLog extends BasicAuditLogEvent
{
    /**
     * Try to extract an audit log from a premoderation result.
     *
     * @param PremoderationItem $item
     * @param PremoderationResult $result
     *
     * @return SuperSpamAuditLog|null
     */
    public static function tryFromPremoderationResult(
        PremoderationItem $item,
        PremoderationResult $result
    ): ?SuperSpamAuditLog {
        // Let's iterate through the results looking for a super spam one.
        foreach ($result->getResponses() as $result) {
            if ($result->isSuperSpam() && ($modUserID = $result->getModeratorUserID())) {
                // This is the one.
                $log = new SuperSpamAuditLog([
                    "userName" => $item->userName,
                    "userEmail" => $item->userEmail,
                    "recordType" => $item->recordType,
                ]);
                $log->sessionUserID = $result->getModeratorUserID();
                return $log;
            }
        }

        return null;
    }

    /**
     * @return string
     */
    public static function eventType(): string
    {
        return "premoderation_superSpam";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Rejected `{$context["recordType"]}` by `{$context["userName"]}` as super spam.";
    }
}
