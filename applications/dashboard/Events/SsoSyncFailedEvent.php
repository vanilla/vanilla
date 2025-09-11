<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when SSO sync fails.
 */
class SsoSyncFailedEvent extends BasicAuditLogEvent
{
    public function __construct(array $errors)
    {
        parent::__construct([
            "errors" => $errors,
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "ssoSync_failed";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Failed to synchronize user profile data.";
    }
}
