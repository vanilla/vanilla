<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Garden\Web\Exception\NotFoundException;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a comment resource event.
 */
class CommentEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const ACTION_MOVE = "move";

    /** @var int|null */
    private $sourceDiscussionID = null;

    /** @var int|null */
    private $destinationDiscussionID = null;

    public function __construct(string $action, array $payload, $sender = null)
    {
        parent::__construct($action, $payload, $sender);
        $this->addApiParams(["expand" => "crawl,roles,vectorize"]);
    }

    /**
     * @inheritdoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context["comment"] = array_intersect_key($this->payload["comment"] ?? [], [
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

        $log = new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);

        return $log;
    }

    /**
     * Set the collection name depending on what the event action is.
     *
     * @return string|null
     */
    public function getTrackableCollection(): ?string
    {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "post";
            case ResourceEvent::ACTION_UPDATE:
            case self::ACTION_MOVE:
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
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        $trackingData = $trackableCommunity->getTrackableComment(
            $this->getPayload()["comment"],
            $this->getTrackableAction()
        );
        // Don't track the comment body.
        $trackingData["comment"] = [
            "body" => null,
        ];

        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.
        if (isset($this->payload["comment"]["siteSectionIDs"])) {
            $trackingData["siteSectionID"] = $this->payload["comment"]["siteSectionIDs"][0];
        }

        // If we have a source discussion ID, we add a trackable discussion data structure to the payload.
        if (isset($this->sourceDiscussionID)) {
            $trackingData["sourceDiscussion"] = $trackableCommunity->getTrackableDiscussion($this->sourceDiscussionID);
        }

        // If we have a destination discussion ID, we add a trackable discussion data structure to the payload.
        if (isset($this->destinationDiscussionID)) {
            $trackingData["destinationDiscussion"] = $trackableCommunity->getTrackableDiscussion(
                $this->destinationDiscussionID
            );
        }
        return $trackingData;
    }

    /**
     * The tracking action for updated comments should be set as "edit".
     *
     * @return string
     */
    public function getTrackableAction(): string
    {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "comment_add";
            case ResourceEvent::ACTION_UPDATE:
                return "comment_edit";
            case ResourceEvent::ACTION_DELETE:
                return "comment_delete";
            case self::ACTION_MOVE:
                return "comment_move";
            default:
                return $this->getAction();
        }
    }

    /**
     * @return int|null
     */
    public function getDiscussionID(): ?int
    {
        return $this->payload["comment"]["discussionID"] ?? null;
    }

    /**
     * @return int
     */
    public function getInsertUserID(): int
    {
        return $this->payload["comment"]["insertUserID"];
    }

    /**
     * Determine if the comment is searchable. Some parent types aren't implemented yet.
     *
     * @return bool
     */
    public function isSearchable(): bool
    {
        $parentRecordType = $this->payload["comment"]["parentRecordType"];
        $commentModel = \Gdn::getContainer()->get(\CommentModel::class);
        $handler = $commentModel->getParentHandler($parentRecordType);
        if (!$handler) {
            return false;
        }

        return $handler->isSearchable();
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        if (isset($this->payload["comment"]["siteSectionIDs"])) {
            return $this->payload["comment"]["siteSectionIDs"][0] ?? null;
        }
        return null;
    }

    /**
     * @param int|null $sourceDiscussionID
     */
    public function setSourceDiscussionID(?int $sourceDiscussionID): void
    {
        $this->sourceDiscussionID = $sourceDiscussionID;
        $this->payload["sourceDiscussionID"] = $sourceDiscussionID;
    }

    /**
     * @param int|null $destinationDiscussionID
     */
    public function setDestinationDiscussionID(?int $destinationDiscussionID): void
    {
        $this->destinationDiscussionID = $destinationDiscussionID;
        $this->payload["destinationDiscussionID"] = $destinationDiscussionID;
    }
}
