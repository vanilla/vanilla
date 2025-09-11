<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\TrackingEventInterface;
use Vanilla\Analytics\TrackableCommunityModel;

/**
 * Represents a page view event
 */
class PageViewEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "page";

    const ACTION_PAGE_VIEW = "page_view";
    const ACTION_DISCUSSION_VIEW = "discussion_view";

    const BOUNCE_TYPE_EXTERNAL_NAVIGATION = "externalNavigation";
    const BOUNCE_TYPE_EXIT_NAVIGATION = "exitNavigation";

    /** @var string */
    private $action;

    /** @var array */
    private $payload;

    /** @var int|null */
    private $categoryID;

    /** @var int|null */
    private $discussionID;

    /**
     * Constructor
     *
     * @param string $action
     * @param array $payload
     * @param array $context
     */
    public function __construct(string $action, array $payload, array $context)
    {
        $this->action = $action;
        $this->payload = $payload;
        $this->categoryID = $context["categoryID"] ?? null;
        $this->discussionID = $context["discussionID"] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Update payload with discussion data if discussionID exists
     *
     * @param TrackableCommunityModel $trackableCommunityModel
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunityModel): array
    {
        $payload = $this->getPayload();
        $discussionID = $payload["discussionID"] ?? null;
        if ($discussionID) {
            $payload["discussion"] = $trackableCommunityModel->getTrackableDiscussion($discussionID);
        }
        return $payload;
    }

    /**
     * @return int|null
     */
    public function getCategoryID(): ?int
    {
        return $this->categoryID;
    }

    /**
     * @return int|null
     */
    public function getDiscussionID(): ?int
    {
        return $this->discussionID;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        return $this->payload["siteSectionID"] ?? null;
    }
}
