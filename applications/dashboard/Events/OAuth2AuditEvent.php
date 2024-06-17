<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\SamlSSO\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * OAuth2 debugging audit events.
 */
class OAuth2AuditEvent extends BasicAuditLogEvent
{
    /**
     * @param string $action
     * @param string $message
     * @param array $context
     */
    public function __construct(protected string $action, string $message, array $context = [])
    {
        parent::__construct(["message" => $message] + $context);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "not-used";
    }

    /**
     * @inheritDoc
     */
    public function getAuditEventType(): string
    {
        return "oauth2_{$this->action}";
    }

    /**
     * @inheritDoc
     */
    public static function canFormatAuditMessage(string $eventType, array $context, array $meta): bool
    {
        return str_starts_with($eventType, "oauth2");
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "OAuth2 - " . $context["message"];
    }
}
