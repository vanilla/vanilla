<?php
/**
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Gdn;
use Psr\Log\LogLevel;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Logging\LogEntry;
use Vanilla\Logging\LoggableEventInterface;
use Vanilla\Logging\LoggerUtils;

/**
 * Represent a discussion resource event.
 */
class DiscussionEvent extends ResourceEvent implements LoggableEventInterface, TrackingEventInterface {
    const COLLECTION_NAME = 'discussion';

    const ACTION_MOVE = 'move';

    /** @var int|null */
    private $sourceCategoryID = null;

    /**
     * DiscussionEvent constructor.
     *
     * @param string $action
     * @param array $payload
     * @param array|null $sender
     */
    public function __construct(string $action, array $payload, ?array $sender = null) {
        parent::__construct($action, $payload, $sender);
        $this->addApiParams(['expand' => ['tagIDs', 'crawl']]);
    }

    /**
     * Get the name of the collection this resource event belongs to.
     *
     * @return string
     */
    public function getCollectionName(): string {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getLogEntry(): LogEntry {
        $context = LoggerUtils::resourceEventLogContext($this);
        $context['discussion'] = array_intersect_key($this->payload["discussion"] ?? [], [
            "discussionID" => true,
            "categoryID" => true,
            "type" => true,
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
     * @inheritdoc
     */
    public function getApiUrl() {
        [$recordType, $recordID] = $this->getRecordTypeAndID();
        return "/api/v2/discussions?discussionID={$recordID}";
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableCollection(): ?string {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return "post";
            case ResourceEvent::ACTION_UPDATE:
            case ResourceEvent::ACTION_DELETE:
            case self::ACTION_MOVE:
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
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array {
        $trackingData = [
            'discussion' => $trackableCommunity->getTrackableDiscussion($this->getPayload()['discussion'])
        ];

        if (isset($this->sourceCategoryID)) {
            $trackingData['sourceCategory'] = $trackableCommunity->getTrackableCategory($this->sourceCategoryID);
        }

        return $trackingData;
    }

    /**
     * The tracking action for updated discussions should be set as "edit".
     *
     * @return string
     */
    public function getTrackableAction(): string {
        switch ($this->getAction()) {
            case ResourceEvent::ACTION_INSERT:
                return 'discussion_add';
            case ResourceEvent::ACTION_UPDATE:
                return 'discussion_edit';
            case ResourceEvent::ACTION_DELETE:
                return 'discussion_delete';
            case self::ACTION_MOVE:
                return 'discussion_move';
            default:
                return $this->getAction();
        }
    }

    /**
     * @param int|null $sourceCategoryID
     */
    public function setSourceCategoryID(?int $sourceCategoryID): void {
        $this->sourceCategoryID = $sourceCategoryID;
        $this->payload['sourceCategoryID'] = $sourceCategoryID;
    }
}
