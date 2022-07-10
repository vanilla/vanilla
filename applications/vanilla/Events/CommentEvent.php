<?php
/**
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a comment resource event.
 */
class CommentEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface {

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context['comment'] = array_intersect_key($this->payload["comment"] ?? [], [
            "commentID" => true,
            "discussionID" => true,
            "categoryID" => true,
            "discussion" => true,
            "dateInserted" => true,
            "dateUpdated" => true,
            "updateUserID" => true,
            "insertUserID" => true,
            "url" => true,
            "name" => true,
        ]);

        $log = new LogEntry(
            LogLevel::INFO,
            LoggerUtils::resourceEventLogMessage($this),
            $context
        );

        return $log;
    }

    /**
     * Set the collection name depending on what the event action is.
     *
     * @return string|null
     */
    public function getTrackableCollection(): ?string {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "post";
            case ResourceEvent::ACTION_UPDATE:
            case ResourceEvent::ACTION_DELETE:
                return "post-modify";
            default:
                return null;
        }
    }

    /**
     * Create a payload suitable for tracking.
     *
     * @param TrackableCommunityModel $trackableCommunity
     *
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array {
        $trackingData = $trackableCommunity->getTrackableComment(
            $this->getPayload()["comment"],
            $this->getTrackableAction()
        );
        return $trackingData;
    }

    /**
     * The tracking action for updated comments should be set as "edit".
     *
     * @return string
     */
    public function getTrackableAction(): string {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return 'comment_add';
            case ResourceEvent::ACTION_UPDATE:
                return 'comment_edit';
            case ResourceEvent::ACTION_DELETE:
                return 'comment_delete';
            default:
                return $this->getAction();
        }
    }

    /**
     * @return int
     */
    public function getDiscussionID(): int {
        return $this->payload['comment']['discussionID'];
    }

    /**
     * @return int
     */
    public function getInsertUserID(): int {
        return $this->payload['comment']['insertUserID'];
    }
}
