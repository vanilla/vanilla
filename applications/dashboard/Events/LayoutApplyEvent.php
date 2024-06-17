<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

/**
 * Event fired when a layout is applied.
 */
class LayoutApplyEvent extends BasicAuditLogEvent
{
    /**
     * @param array $layout
     * @param array $layoutViews
     */
    public function __construct(array $layout, array $layoutViews)
    {
        $layoutViewContext = [];
        foreach ($layoutViews as $layoutView) {
            $recordTypeLabel = match ($layoutView["recordType"]) {
                "siteSection" => "subcommunities",
                "category" => "categories",
                "global", "root" => "site",
                default => $layoutView["recordType"],
            };
            $layoutViewContext[$recordTypeLabel][] = $layoutView["record"]["name"];
        }

        parent::__construct([
            "layoutID" => $layout["layoutID"],
            "layoutName" => $layout["name"],
            "appliedTo" => $layoutViewContext,
        ]);
    }

    /**
     * @inheritDoc
     */
    public static function eventType(): string
    {
        return "layout_apply";
    }

    /**
     * @inheritDoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Layout `{$context["layoutName"]}` was applied.";
    }
}
