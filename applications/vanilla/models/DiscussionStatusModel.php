<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\ValidationException;
use Garden\Web\Exception\ClientException;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Dashboard\Models\RecordStatusLogModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\CurrentTimeStamp;
use Vanilla\Contracts\ConfigurationInterface;

/**
 * Model for discussions status updates.
 */
class DiscussionStatusModel
{
    /** @var DiscussionModel */
    private $discussionModel;

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /** @var RecordStatusLogModel */
    private $recordStatusLogModel;

    /** @var UserModel */
    private $userModel;

    /**
     * Class constructor.
     *
     * @param DiscussionModel $discussionModel
     * @param RecordStatusModel $recordStatusModel
     *
     * @param UserModel $userModel
     */
    public function __construct(
        DiscussionModel $discussionModel,
        RecordStatusModel $recordStatusModel,
        RecordStatusLogModel $recordStatusLogModel,
        UserModel $userModel
    ) {
        $this->discussionModel = $discussionModel;
        $this->recordStatusModel = $recordStatusModel;
        $this->recordStatusLogModel = $recordStatusLogModel;
        $this->userModel = $userModel;
    }

    /**
     * Get Discussion Status record.
     *
     * @param int $discussionID
     *
     * @return array SQL result.
     */
    public function getDiscussionStatus(int $discussionID): array
    {
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new ClientException("Record not found.");
        }
        $status = $this->recordStatusModel->selectSingle(["statusID" => $discussion["statusID"]]);
        if (!is_array($status)) {
            throw new ClientException("Invalid status ID.");
        }
        return $status;
    }

    /**
     * Update Discussion Status
     *
     * @param int $discussionID
     * @param int $statusID
     * @param string|null $reason Updates StatusNotes unless $reason is null
     *
     * @return array SQL result.
     * @throws ClientException|NoResultsException|ValidationException
     */
    public function updateDiscussionStatus(int $discussionID, int $statusID, ?string $reason = null): array
    {
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new ClientException("Record not found.");
        }
        // Verify the new status.
        $status = $this->recordStatusModel->selectSingle(["statusID" => $statusID]);
        if (!is_array($status) || $status["recordType"] != "discussion") {
            throw new ClientException("Invalid status ID.");
        }
        if ($status["isInternal"]) {
            $oldStatusID = $discussion["internalStatusID"] ?? 0;
        } else {
            $oldStatusID = $discussion["statusID"] ?? 0;
        }
        $noChange = $statusID === $oldStatusID;

        $this->discussionModel->saveToSerializedColumn("Attributes", $discussionID, "StatusChanged", !$noChange);

        $existingStatusNotes = DiscussionModel::getRecordAttribute($discussion, "StatusNotes");
        $statusNotes = $reason ?? $existingStatusNotes;
        $statusNotes = $statusNotes === "" ? null : $statusNotes;
        $this->discussionModel->saveToSerializedColumn("Attributes", $discussionID, "StatusNotes", $statusNotes);

        if (!$noChange) {
            $recordLogData = [
                "insertUserID" => Gdn::session()->UserID,
                "dateInserted" => DateTimeFormatter::timeStampToDateTime(CurrentTimeStamp::get()),
                "recordType" => "discussion",
                "recordID" => $discussionID,
                "reason" => $reason,
                "statusID" => $statusID,
            ];

            if ($status["isInternal"]) {
                $this->discussionModel->setField($discussionID, "internalStatusID", $statusID);
            } else {
                $this->discussionModel->setField($discussionID, "statusID", $statusID);
            }

            $statusLogId = $this->recordStatusLogModel->insert($recordLogData);
            if (!$statusLogId) {
                throw new \Exception("failed saving record status log");
            }
        }

        $event = $this->statusChangeUpdate($discussionID, $oldStatusID);
        $statusEvent = new DiscussionStatusEvent(
            DiscussionStatusEvent::ACTION_DISCUSSION_STATUS,
            $event->getPayload(),
            $event->getSender(),
            $status
        );
        $this->discussionModel->getEventManager()->dispatch($statusEvent);

        return $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
    }

    /**
     * Find proper discussion status to be applied for a discussion and update the discussion
     *
     * @param int $discussionID
     * @return array
     */
    public function determineAndUpdateDiscussionStatus(int $discussionID): array
    {
        //first set status to default discussion status
        $statusID = RecordStatusModel::DISCUSSION_STATUS_NONE;
        $statusRecord = $this->getDiscussionStatus($discussionID);
        // If status does not have recordSubType specified, we do not change statusID.
        if ($statusRecord["recordSubtype"] === null) {
            return $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        }
        return $this->updateDiscussionStatus($discussionID, $statusID);
    }

    /**
     * Dispatch an update event for a discussion whose status has changed.
     *
     * @param int $discussionID
     * @param int $oldStatusID
     */
    private function statusChangeUpdate(int $discussionID, int $oldStatusID): DiscussionEvent
    {
        // Fetch the row again.
        $newRow = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $newRow["oldStatusID"] = $oldStatusID;

        // KLUDGE: In the future this should just be an expand that gets expanded out on discussions by default.
        return $this->discussionModel->eventFromRow(
            (array) $newRow,
            DiscussionStatusEvent::ACTION_DISCUSSION_STATUS,
            $this->userModel->currentFragment()
        );
    }

    /**
     * Get active default internal status for creation of discussion internalStatusID.
     *
     * @return array{statusID: string, name: string, state: string}
     */
    public function getDefaultInternalStatus(): array
    {
        return $this->recordStatusModel->getDefaultActiveInternalStatus();
    }

    /**
     * Try to get a record status fragment.
     *
     * @param int $statusID
     *
     * @return array|null
     */
    public function tryGetStatusFragment(int $statusID): ?array
    {
        try {
            $status = $this->recordStatusModel->selectSingle(["statusID" => $statusID]);
            return RecordStatusModel::getSchemaFragment()->validate($status);
        } catch (NoResultsException $nre) {
            ErrorLogger::error("Discussion Status Not Found", ["recordStatus"], ["statusID" => $statusID]);
            return null;
        } catch (ValidationException $e) {
            $context = ["status" => $status];
            ErrorLogger::error("Discussion Status Validation Failure", ["recordStatus"], $context);
            return null;
        }
    }

    /**
     * Filter a statusID so that it is always an active one.
     *
     * @param int|null $statusID
     *
     * @return int
     */
    public function filterActiveStatusID(?int $statusID): int
    {
        if ($statusID === null) {
            return RecordStatusModel::DISCUSSION_STATUS_NONE;
        }
        // This should always be cached and be relatively efficient.
        $activeStatusesByID = $this->recordStatusModel->getStatuses(true, false);
        if (!isset($activeStatusesByID[$statusID])) {
            return RecordStatusModel::DISCUSSION_STATUS_NONE;
        }

        // It's fine.
        return $statusID;
    }
}
