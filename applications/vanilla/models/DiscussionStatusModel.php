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
     * Update Discussion Status
     *
     * @param int $discussionID
     * @param int $statusID
     * @param string $reason
     *
     * @return array SQL result.
     */
    public function updateDiscussionStatus(int $discussionID, int $statusID, string $reason = ""): array
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
        $oldStatusID = $discussion["statusID"] ?? 0;
        $noChange = $statusID === $oldStatusID;

        $this->discussionModel->saveToSerializedColumn("Attributes", $discussionID, "StatusChanged", !$noChange);

        $this->discussionModel->saveToSerializedColumn(
            "Attributes",
            $discussionID,
            "StatusNotes",
            $reason != "" ? $reason : null
        );

        if (!$noChange) {
            $this->discussionModel->setField($discussionID, "statusID", $statusID);
            $recordLogData = [
                "statusID" => $statusID,
                "insertUserID" => Gdn::session()->UserID,
                "dateInserted" => DateTimeFormatter::timeStampToDateTime(CurrentTimeStamp::get()),
                "recordType" => "discussion",
                "recordID" => $discussionID,
                "reason" => $reason,
            ];

            $statusLogId = $this->recordStatusLogModel->insert($recordLogData);
            if (!$statusLogId) {
                throw new \Exception("failed saving record status log");
            }
        }

        $event = $this->statusChangeUpdate($discussionID);
        $statusEvent = new DiscussionStatusEvent(
            DiscussionStatusEvent::ACTION_DISCUSSION_STATUS,
            $event->getPayload(),
            $event->getSender()
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
        $statusID = 0;

        //Need to find if the resolved2 plugin is enabled
        /** @var ConfigurationInterface $config */
        $config = \Gdn::getContainer()->get(ConfigurationInterface::class);
        $isResolvedEnabled = $config->get("EnabledPlugins.resolved2", null);

        /**
         * KLUDGE: Shouldn't be referencing resolved2.
         * @psalm-suppress UndefinedClass
         */
        if ($isResolvedEnabled) {
            $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            $statusRecord = $this->recordStatusModel->selectSingle(["statusID" => $discussion["statusID"]]);
            $statusID = Resolved2Plugin::DISCUSSION_STATUS_UNRESOLVED;
            if ($statusRecord["state"] == "closed") {
                $statusID = Resolved2Plugin::DISCUSSION_STATUS_RESOLVED;
            }
        }
        return $this->updateDiscussionStatus($discussionID, $statusID);
    }
    /**
     * Dispatch an update event for a discussion whose status has changed.
     *
     * @param int $discussionID
     */
    private function statusChangeUpdate(int $discussionID): DiscussionEvent
    {
        // Fetch the row again.
        $newRow = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        // KLUDGE: In the future this should just be an expand that gets expanded out on discussions by default.
        return $this->discussionModel->eventFromRow(
            (array) $newRow,
            DiscussionStatusEvent::ACTION_DISCUSSION_STATUS,
            $this->userModel->currentFragment()
        );
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
        $activeStatusesByID = $this->recordStatusModel->getActiveStatuses();
        if (!isset($activeStatusesByID[$statusID])) {
            return RecordStatusModel::DISCUSSION_STATUS_NONE;
        }

        // It's fine.
        return $statusID;
    }
}
