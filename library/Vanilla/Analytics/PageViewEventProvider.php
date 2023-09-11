<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Analytics;

use Garden\Schema\Schema;
use Vanilla\Community\Events\PageViewEvent;

class PageViewEventProvider implements EventProviderInterface
{
    /**
     * @inheritDoc
     */
    public function getEvent(array $body): object
    {
        $payload = $this->getPayloadSchema()->validate($body);
        $action = $payload["type"] ?? PageViewEvent::ACTION_PAGE_VIEW;
        $context = $this->getContextSchema()->validate($body);
        return new PageViewEvent($action, $payload, $context);
    }

    /**
     * @inheritDoc
     */
    public function canHandleRequest(array $body): bool
    {
        $type = $body["type"] ?? null;
        return in_array($type, [PageViewEvent::ACTION_PAGE_VIEW, PageViewEvent::ACTION_DISCUSSION_VIEW]);
    }

    /**
     * Return schema for properties needed for analytics
     *
     * @return Schema
     */
    private function getPayloadSchema(): Schema
    {
        return Schema::parse([
            "url:s?" => [
                "description" => "URL of page view to track for analytics",
            ],
            "referrer:s?" => [
                "description" => "URL of referrer",
            ],
            "type:s?" => [
                "description" => "The type of page view event (i.e. page_view, discussion_view)",
                "enum" => [PageViewEvent::ACTION_PAGE_VIEW, PageViewEvent::ACTION_DISCUSSION_VIEW],
                "default" => PageViewEvent::ACTION_PAGE_VIEW,
            ],
            "discussionID:i?" => [
                "description" => "The ID of a discussion to fetch discussion data for",
            ],
            "siteSectionID:s?" => [
                "description" => "The ID of the current site section",
            ],
        ]);
    }

    /**
     * Return schema which contains payload properties as well as extra contextual properties.
     *
     * @return Schema
     */
    private function getContextSchema(): Schema
    {
        return $this->getPayloadSchema()->merge(Schema::parse(["categoryID:i?"]));
    }
}
