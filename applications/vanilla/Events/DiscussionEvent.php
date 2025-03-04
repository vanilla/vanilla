<?php
/**
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Exception;
use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;
use Vanilla\Utility\ArrayUtils;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface
{
    const COLLECTION_NAME = "discussion";

    const ACTION_MOVE = "move";
    const ACTION_CLOSE = "close";
    const ACTION_MERGE = "merge";
    const ACTION_SPLIT = "split";
    const ACTION_STATUS = "status";

    /** @var int|null */
    private $sourceDiscussionID = null;

    /** @var int|null */
    private $destinationDiscussionID = null;

    /** @var int|null */
    private $sourceCategoryID = null;

    /** @var array|null */
    private $commentIDs = null;

    /**
     * DiscussionEvent constructor.
     *
     * @param string $action
     * @param array $payload
     * @param array|object|null $sender
     * @throws Exception
     */
    public function __construct(string $action, array $payload, $sender = null)
    {
        parent::__construct($action, $payload, $sender);
        $this->addApiParams(["expand" => "tagIDs,crawl,vectorize,roles"]);
    }

    /**
     * Get the name of the collection this resource event belongs to.
     *
     * @return string
     */
    public function getCollectionName(): string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry
    {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context["discussion"] = ArrayUtils::pluck($this->payload["discussion"] ?? [], [
            "discussionID",
            "categoryID",
            "type",
            "dateInserted",
            "dateUpdated",
            "updateUserID",
            "insertUserID",
            "url",
            "name",
        ]);

        $log = new LogEntry(LogLevel::INFO, LoggerUtils::resourceEventLogMessage($this), $context);

        return $log;
    }

    /**
     * @param array $statusFragment
     */
    public function setStatus(array $statusFragment)
    {
        $this->payload["status"] = $statusFragment;
    }

    /**
     * @param array $statusFragment
     */
    public function setInternalStatus(array $statusFragment)
    {
        $this->payload["internalStatus"] = $statusFragment;
    }

    /**
     * @inheritdoc
     */
    public function getApiUrl()
    {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/discussions?discussionID={$recordID}";
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableCollection(): ?string
    {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "post";
            case ResourceEvent::ACTION_UPDATE:
            case ResourceEvent::ACTION_DELETE:
            case self::ACTION_CLOSE:
            case self::ACTION_MOVE:
            case self::ACTION_MERGE:
            case self::ACTION_SPLIT:
            case self::ACTION_STATUS:
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
        $trackingData = $trackableCommunity->getTrackableDiscussion($this->getPayload()["discussion"]);

        // If we have a source category ID, we add a trackable category data structure to the payload.
        if (isset($this->sourceCategoryID)) {
            $trackingData["sourceCategory"] = $trackableCommunity->getTrackableCategory($this->sourceCategoryID);
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

        // If we have an array of comment IDs, we add them to the payload.
        if (isset($this->commentIDs)) {
            $trackingData["commentIDs"] = $this->commentIDs;
        }

        // Clear out the body because it's too large.
        $trackingData["discussion"] = [
            "body" => null,
        ];

        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.
        if (isset($this->payload["discussion"]["siteSectionIDs"])) {
            $trackingData["siteSectionID"] = $this->payload["discussion"]["siteSectionIDs"][0];
        }

        return $trackingData;
    }

    /**
     * The tracking action for updated discussions should be set as "edit".
     *
     * @return string
     */
    public function getTrackableAction(): string
    {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "discussion_add";
            case ResourceEvent::ACTION_UPDATE:
                return "discussion_edit";
            case ResourceEvent::ACTION_DELETE:
                return "discussion_delete";
            case self::ACTION_CLOSE:
                return "discussion_close";
            case self::ACTION_MOVE:
                return "discussion_move";
            case self::ACTION_MERGE:
                return "discussion_merge";
            case self::ACTION_SPLIT:
                return "comment_split";
            case self::ACTION_STATUS:
                return "discussion_status";
            default:
                return $this->getAction();
        }
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

    /**
     * @param int|null $sourceCategoryID
     */
    public function setSourceCategoryID(?int $sourceCategoryID): void
    {
        $this->sourceCategoryID = $sourceCategoryID;
        $this->payload["sourceCategoryID"] = $sourceCategoryID;
    }

    /**
     * Set the `announce` value for the source discussion.
     *
     * @param int $sourceAnnounce
     */
    public function setSourceAnnounce(int $sourceAnnounce): void
    {
        $this->payload["sourceAnnounce"] = $sourceAnnounce;
    }

    /**
     * @param array|null $commentIDs
     */
    public function setCommentIDs(?array $commentIDs): void
    {
        $this->commentIDs = $commentIDs;
        $this->payload["commentIDs"] = $commentIDs;
    }

    /**
     * @return int
     */
    public function getInsertUserID(): int
    {
        return $this->payload["discussion"]["insertUserID"];
    }

    /**
     * Get StatusID
     *
     * @return int
     */
    public function getStatusID(): int
    {
        return $this->payload["discussion"]["statusID"] ?? 0;
    }

    /**
     * Get Internal StatusID
     *
     * @return int
     */
    public function getInternalStatusID(): int
    {
        return $this->payload["discussion"]["internalStatusID"] ?? 0;
    }

    /**
     * @param int $oldStatusID
     */
    public function setOldStatusID(int $oldStatusID)
    {
        $this->payload["oldStatusID"] = $oldStatusID;
    }

    public function getSiteSectionID(): ?string
    {
        return $this->payload["discussion"]["siteSectionIDs"][0] ?? null;
    }
}
