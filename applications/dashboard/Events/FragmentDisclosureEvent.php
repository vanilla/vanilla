<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Audit log event for if a user accepted or rejected the fragment disclosure.
 */
class FragmentDisclosureEvent extends BasicAuditLogEvent
{
    /**
     * @param bool $didAccept
     */
    public function __construct(bool $didAccept)
    {
        parent::__construct([
            "didAccept" => $didAccept,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "fragment_disclosure";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        $value = $context["didAccept"] ? "accepted" : "rejected";
        return "User {$value} the fragment disclosure";
    }
}
