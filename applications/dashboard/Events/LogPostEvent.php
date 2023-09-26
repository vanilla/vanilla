<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\ResourceEvent;
use Gdn;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Site\SiteSectionModel;

/**
 * Represent a LogPostEvent. The event type will be either "discussion" or "comment", depending on the post's type.
 */
class LogPostEvent implements \Garden\Events\TrackingEventInterface
{
    const COLLECTION_NAME = "moderation";

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
     * @param array|object $discipliningUser
     * @param array|object $disciplinedUser
     * @param string $disciplineType
     * @param array $additionalInfo
     */
    public function __construct(
        ResourceEvent $postEvent,
        string $source,
        $discipliningUser,
        $disciplinedUser,
        string $disciplineType,
        array $additionalInfo = []
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
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * {@inheritDoc}
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritDoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * Get a payload for analytics tracking.
     *
     * @param TrackableCommunityModel $trackableCommunityModel
     * @param TrackableUserModel $trackableUserModel
     * @return array
     */
    public function getTrackablePayload(
        TrackableCommunityModel $trackableCommunityModel,
        TrackableUserModel $trackableUserModel
    ): array {
        $postEvent = $this->getPayload();
        $commentID = $postEvent["comment"]["commentID"] ?? null;
        if ($commentID !== null) {
            $trackingData = $trackableCommunityModel->getTrackableComment($postEvent["comment"]["commentID"]);
        } elseif (isset($postEvent["comment"])) {
            $trackingData = $trackableCommunityModel->getTrackableLogComment($postEvent["comment"]);
        }

        $discussionID = $postEvent["discussion"]["discussionID"] ?? null;
        if ($discussionID !== null) {
            $trackingData = $trackableCommunityModel->getTrackableDiscussion($postEvent["discussion"]["discussionID"]);
        } elseif (isset($postEvent["discussion"])) {
            $trackingData["discussion"] = $trackableCommunityModel->getTrackableLogDiscussion($postEvent["discussion"]);
        }

        $trackingData["discipliningUser"] = $trackableUserModel->getTrackableUser(
            $postEvent["discipliningUser"]["userID"]
        );
        $trackingData["disciplinedUser"] = $trackableUserModel->getTrackableUser(
            $postEvent["disciplinedUser"]["userID"]
        );

        return $trackingData;
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        $siteSection = Gdn::getContainer()
            ->get(SiteSectionModel::class)
            ->getCurrentSiteSection();

        return $siteSection->getSectionID() ?? null;
    }
}
