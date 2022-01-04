<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Community\Events\DiscussionEvent;

/**
 * Represent a LogPostEvent. The event type will be either "discussion" or "comment", depending on the post's type.
 */
class LogPostEvent implements \Garden\Events\TrackingEventInterface {
    const COLLECTION_NAME = 'moderation';

    /** @var string */
    protected $action;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $sender;

    /** @var string */
    protected $type;

    /**
     * Construct a LogPostEvent.
     *
     * @param ResourceEvent $postEvent
     * @param string $source
     * @param array $discipliningUser
     * @param array $disciplinedUser
     * @param string $disciplineType
     * @param array $additionalInfo
     */
    public function __construct(
        ResourceEvent $postEvent,
        string        $source,
        array         $discipliningUser,
        array         $disciplinedUser,
        string        $disciplineType,
        array         $additionalInfo = []
    ) {
        $payload = [];
        $payload["source"] = $source;
        $payload["discipliningUser"] = $discipliningUser;
        $payload["disciplinedUser"] = $disciplinedUser;
        $payload["disciplineType"] = $disciplineType;
        if (!empty($additionalInfo)) {
            foreach ($additionalInfo as $field => $value) {
                $payload[$field] = $value;
            }
        }

        // Set the payload to discussion or comment, depending on which event type is passed.
        $type = null;
        switch ($postEvent) {
            case $postEvent instanceof DiscussionEvent:
                $payload["discussion"] = $postEvent->getPayload()["discussion"];
                $type = "discussion";
                break;
            case $postEvent instanceof CommentEvent:
                $payload["comment"] = $postEvent->getPayload()["comment"];
                $type = "comment";
                break;
            default:
                break;
        }

        $this->payload = $payload;
        $this->action = $postEvent->getAction();
        $this->type = $type;
    }

    /**
     * {@inheritDoc}
     */
    public function getPayload(): ?array {
        return $this->payload;
    }

    /**
     * {@inheritDoc}
     */
    public function getAction(): string {
        return $this->action;
    }

    /**
     * @inheritDoc
     */
    public function getTrackableCollection(): ?string {
        return self::COLLECTION_NAME;
    }

    /**
     * Get a payload for analytics tracking.
     *
     * @param TrackableCommunityModel $trackableCommunityModel
     * @param TrackableUserModel $trackableUserModel
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunityModel, TrackableUserModel $trackableUserModel): array {
        $trackingData = $this->getPayload();
        $commentID = $trackingData['comment']['commentID'] ?? null;
        if ($commentID !== null) {
            $trackingData["comment"] = $trackableCommunityModel->getTrackableComment($trackingData["comment"]["commentID"]);
        } elseif (isset($trackingData['comment'])) {
            $trackingData["comment"] = $trackableCommunityModel->getTrackableLogComment($trackingData["comment"]);
        }

        $discussionID = $trackingData['discussion']['discussionID'] ?? null;
        if ($discussionID !== null) {
            $trackingData["discussion"] = $trackableCommunityModel->getTrackableDiscussion($trackingData["discussion"]["discussionID"]);
        } elseif (isset($trackingData['discussion'])) {
            $trackingData["discussion"] = $trackableCommunityModel->getTrackableLogDiscussion($trackingData["discussion"]);
        }

        $trackingData["discipliningUser"] = $trackableUserModel->getTrackableUser($trackingData["discipliningUser"]["userID"]);
        $trackingData["disciplinedUser"] = $trackableUserModel->getTrackableUser($trackingData["disciplinedUser"]["userID"]);

        return $trackingData;
    }
}
