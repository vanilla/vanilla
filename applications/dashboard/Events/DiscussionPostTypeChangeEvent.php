<?php

namespace Vanilla\Dashboard\Events;

use Vanilla\Logging\BasicAuditLogEvent;

class DiscussionPostTypeChangeEvent extends BasicAuditLogEvent
{
    public function __construct(string $postTypeID, string $previousPostTypeID, array $context = [])
    {
        parent::__construct(
            [
                "postTypeID" => $postTypeID,
                "previousPostTypeID" => $previousPostTypeID,
            ] + $context
        );
    }

    /**
     * @inheritdoc
     */
    public static function eventType(): string
    {
        return "discussion_post_type_change";
    }

    /**
     * @inheritdoc
     */
    public static function formatAuditMessage(string $eventType, array $context, array $meta): string
    {
        return "Discussion post type changed from `{$context["previousPostTypeID"]}` to `{$context["postTypeID"]}`";
    }
}
