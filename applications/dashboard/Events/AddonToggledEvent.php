<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Addon;
use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event for when an addon is enabled or disabled.
 */
class AddonToggledEvent extends BasicAuditLogEvent
{
    /**
     * @param Addon $addon
     * @param bool $enabled
     */
    public function __construct(Addon $addon, bool $enabled)
    {
        parent::__construct([
            "addonType" => $addon->getType(),
            "addonKey" => $addon->getKey(),
            "addonName" => $addon->getName(),
            "enabled" => $enabled,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "addon_toggled";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        $addonTypeKey = ucfirst($context["addonType"]);
        if ($context["enabled"]) {
            return "$addonTypeKey `{$context["addonName"]}` was enabled.";
        } else {
            return "$addonTypeKey `{$context["addonName"]}` was disabled.";
        }
    }
}
