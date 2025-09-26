<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\SamlSSO\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * JSConnect debugging audit events.
 */
class JsConnectAuditEvent extends BasicAuditLogEvent
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
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "not-used";
    }

    /**
     * @inheritdoc
     */
    public function getAuditEventType(): string
    {
        return "jsconnect_{$this->action}";
    }

    /**
     * @inheritdoc
     */
    public static function canFormatAuditMessage(string $eventType, array $context, array $meta): bool
    {
        return str_starts_with($eventType, "jsconnect");
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "JSConnect - " . $context["message"];
    }
}
