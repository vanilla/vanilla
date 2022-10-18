<?php
/**
 * @author David Barbier <dbarbier@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\TrackingEventInterface;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LoggableEventInterface;

/**
 * Represent a Discussion Tag event.
 * We consider this a type of Discussion event, thus subclasses the DiscussionEvent class.
 */
class DiscussionTagEvent extends DiscussionEvent implements LoggableEventInterface, TrackingEventInterface
{
    const ACTION_DISCUSSION_TAGGED = "discussionTagged";

    /** @var array */
    private $tags = [];

    /**
     * Construct the DiscussionTagEvent based on a DiscussionEvent.
     * This alters the payload, sets the action to 'discussionTagged' and sets the type to 'discussionEvent'.
     *
     * @param DiscussionEvent $discussionEvent
     * @param array $tagsData
     */
    public function __construct(DiscussionEvent $discussionEvent, array $tagsData)
    {
        $tagIDs = $tagNames = [];

        $payload = $discussionEvent->getPayload();

        foreach ($tagsData as $tag) {
            $tagIDs[] = $tag["TagID"];
            $tagNames[] = $tag["FullName"];
        }

        $this->tags = $payload["tags"] = ["tagNames" => $tagNames, "tagIDs" => $tagIDs];
        parent::__construct(self::ACTION_DISCUSSION_TAGGED, $payload, $discussionEvent->getSender());
        $this->type = "discussion";
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableCollection(): ?string
    {
        switch ($this->getAction()) {
            case DiscussionTagEvent::ACTION_DISCUSSION_TAGGED:
                return "post-modify";
            default:
                return null;
        }
    }

    /**
     * Get event data needed for tracking.
     *
     * @param TrackableCommunityModel $trackableCommunity
     *
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        $trackingData = [
            "discussion" => $trackableCommunity->getTrackableDiscussion($this->getPayload()["discussion"]),
            "tags" => $this->tags,
        ];
        return $trackingData;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableAction(): string
    {
        return $this->getAction();
    }
}
