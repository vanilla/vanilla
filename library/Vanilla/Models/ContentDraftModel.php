<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\EventManager;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Session;
use DiscussionModel;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\ApiUtils;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Activity\ScheduledPostFailedActivity;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Http\InternalClient;
use Vanilla\Logger;
use Vanilla\Permissions;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerNextArgs;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Scheduler\LongRunnerTimeoutException;
use Vanilla\Scheduler\TrackingSlipInterface;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\PermissionCheckTrait;
use Gdn;
use Vanilla\Web\SystemCallableInterface;

/**
 * Handle all-purpose drafts.
 */
class ContentDraftModel extends PipelineModel implements LoggerAwareInterface, SystemCallableInterface
{
    use PermissionCheckTrait, LoggerAwareTrait;

    public const FEATURE = "newCommunityDrafts";

    public const FEATURE_SCHEDULE = "DraftScheduling";

    public const DRAFT_TYPE_NORMAL = 0;

    public const DRAFT_TYPE_SCHEDULED = 1;

    public const DRAFT_TYPE_ERROR = 2;

    public const CACHE_CONST = "UserDraftCount_";

    public const DRAFT_TYPES = [
        "draft" => self::DRAFT_TYPE_NORMAL,
        "scheduled" => self::DRAFT_TYPE_SCHEDULED,
        "error" => self::DRAFT_TYPE_ERROR,
    ];

    const SCHEDULED_DRAFTS_LIMIT = 10;

    /**
     * @return bool
     */
    public static function enabled(): bool
    {
        return true;
    }

    /**
     * Check if the draft scheduling feature is enabled.
     * @return bool
     */
    public static function draftSchedulingEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(self::FEATURE_SCHEDULE);
    }

    /**
     * DI.
     */
    public function __construct(
        private Gdn_Session $session,
        private \DraftModel $legacyDraftModel,
        private LongRunner $longRunner,
        private DiscussionModel $discussionModel,
        private ScheduledDraftModel $draftScheduledModel,
        private InternalClient $internalClient,
        private EventManager $eventManager
    ) {
        parent::__construct("contentDraft");
        $this->setPrimaryKey("draftID");

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $jsonProcessor = new JsonFieldProcessor();
        $jsonProcessor->setFields(["attributes"]);
        $this->addPipelineProcessor($jsonProcessor);
    }

    /**
     * @param \Gdn_DatabaseStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        if (!$structure->tableExists("contentDraft")) {
            // We are setting up a site for the first time.
            // Default this to on.
            \Gdn::config()->saveToConfig("Feature." . self::FEATURE . ".Enabled", true);
        }

        $structure
            ->table("contentDraft")
            ->primaryKey("draftID")
            ->column("recordType", "varchar(64)", false, ["index", "index.record", "index.parentRecord"])
            ->column("recordID", "int", true, "index.record")
            ->column("parentRecordType", "varchar(15)", true)
            ->column("parentRecordID", "int", true, "index.parentRecord")
            ->column("attributes", "mediumtext")
            ->column("insertUserID", "int", false, "index")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->set();
        $structure
            ->table("contentDraft")
            ->createIndexIfNotExists("IX_contentDraft_parentRecordType_parentRecordID", [
                "parentRecordType",
                "parentRecordID",
            ]);
        Gdn::permissionModel()->define(["Garden.Schedule.Allow" => "Garden.Curation.Manage"]);
        // set new permission to manage draft scheduling
        $structure
            ->table("contentDraft")
            ->column("dateScheduled", "datetime", null, "index")
            ->column("draftStatus", "tinyint(1)", 0)
            ->column("error", "varchar(255)", true)
            ->set();
    }

    /**
     * Get draft count for particular user and only articles when draftSchedulingEnabled is disabled
     *
     * @param int $userID
     * @return int
     */
    public function draftsCount(int $userID): int
    {
        $where = ["insertUserID" => $userID];
        if (!$this->draftSchedulingEnabled()) {
            $where["recordType"] = "article";
        }
        $countRecord = $this->createSql()
            ->from($this->getTable())
            ->select("*", "COUNT", "draftCount")
            ->where($where)
            ->groupBy("insertUserID")
            ->get()
            ->nextRow(DATASET_TYPE_ARRAY);

        return $countRecord["draftCount"] ?? 0;
    }

    /**
     * Get draft count for current user and only discussion/comment when draftSchedulingEnabled is disabled.
     *
     * @return int
     */
    public function draftsWhereCountByUser(): int
    {
        if ($this->session->UserID === 0) {
            return 0;
        } else {
            $where = ["insertUserID" => $this->session->UserID, "draftStatus" => self::DRAFT_TYPE_NORMAL];
            if (!$this->draftSchedulingEnabled()) {
                $where["recordType"] = ["comment", "discussion"];
                unset($where["draftStatus"]);
            }

            $countRecord = $this->createSql()
                ->from($this->getTable())
                ->select("*", "COUNT", "draftCount")
                ->where($where)
                ->groupBy("insertUserID")
                ->get()
                ->nextRow(DATASET_TYPE_ARRAY);
            $counts = $countRecord["draftCount"] ?? 0;

            return $counts;
        }
    }

    /**
     * Delete a draft, while checking permissions for the deletion.
     *
     * Support for the legacy draft model or this one.
     *
     * @param int $draftID
     * @return void
     * @throws NotFoundException
     * @throws PermissionException
     */
    public function deleteDraftWithPermissionCheck(int $draftID): void
    {
        if (self::enabled()) {
            try {
                $draft = $this->selectSingle(["draftID" => $draftID]);
            } catch (NoResultsException $ex) {
                throw new NotFoundException("Draft", previous: $ex);
            }
            if ($draft["insertUserID"] !== $this->session->UserID) {
                $this->permission("community.moderate");
            }
            $this->delete(where: ["draftID" => $draftID]);
        } else {
            $draft = $this->legacyDraftModel->getID($draftID, DATASET_TYPE_ARRAY);
            if (!$draft) {
                throw new NotFoundException("Draft");
            }

            if ($draft["InsertUserID"] !== $this->session->UserID) {
                $this->permission("community.moderate");
            }

            $this->legacyDraftModel->deleteID($draftID);
            ModelUtils::validationResultToValidationException($this->legacyDraftModel);
        }
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeLegacyDraft(array $dbRecord)
    {
        $parentRecordID = null;

        $commentAttributes = ["Body", "Format"];
        $discussionAttributes = ["Announce", "Body", "Closed", "Format", "Name", "Sink", "Tags", "Type", "GroupID"];
        if (array_key_exists("DiscussionID", $dbRecord) && !empty($dbRecord["DiscussionID"])) {
            $dbRecord["RecordType"] = "comment";
            $dbRecord["parentRecordType"] = "discussion";
            $parentRecordID = $dbRecord["DiscussionID"];
            $attributes = $commentAttributes;
        } else {
            if (array_key_exists("CategoryID", $dbRecord) && !empty($dbRecord["CategoryID"])) {
                $parentRecordID = $dbRecord["CategoryID"];
                $dbRecord["parentRecordType"] = "category";
            }
            $dbRecord["RecordType"] = "discussion";
            $attributes = $discussionAttributes;
        }
        $dbRecord["ParentRecordID"] = $parentRecordID;
        $dbRecord["Attributes"] = array_intersect_key($dbRecord, array_flip($attributes));

        // Remove redundant attribute columns on the row.
        foreach (array_merge($commentAttributes, $discussionAttributes) as $col) {
            unset($dbRecord[$col]);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @param string|null $recordType
     * @return array Return a database record.
     */
    public function convertToLegacyDraft(array $schemaRecord, string|null $recordType = null): array
    {
        // If the record type is not explicitly defined by the parameters, try to extract it from $body.
        if ($recordType === null && array_key_exists("recordType", $schemaRecord)) {
            $recordType = $schemaRecord["recordType"];
        }

        if (array_key_exists("attributes", $schemaRecord)) {
            $columns = [
                "announce",
                "body",
                "categoryID",
                "closed",
                "format",
                "name",
                "sink",
                "tags",
                "type",
                "groupID",
            ];
            $attributes = array_intersect_key($schemaRecord["attributes"], array_flip($columns));
            $schemaRecord = array_merge($schemaRecord, $attributes);
            unset($schemaRecord["attributes"]);
        }

        if (array_key_exists("tags", $schemaRecord)) {
            if (empty($schemaRecord["tags"])) {
                $schemaRecord["tags"] = null;
            } elseif (is_array($schemaRecord["tags"])) {
                $schemaRecord["tags"] = implode(",", $schemaRecord["tags"]);
            }
        }
        switch ($recordType) {
            case "comment":
                if (array_key_exists("parentRecordID", $schemaRecord)) {
                    $schemaRecord["DiscussionID"] = $schemaRecord["parentRecordID"];
                }
                $schemaRecord["Type"] = $schemaRecord["type"] ?? "comment";
                break;
            case "discussion":
                if (array_key_exists("parentRecordID", $schemaRecord)) {
                    $schemaRecord["CategoryID"] = $schemaRecord["parentRecordID"];
                }
                $schemaRecord["DiscussionID"] = null;
                $schemaRecord["Type"] = $schemaRecord["type"] ?? "Discussion";
                break;
        }
        unset($schemaRecord["recordType"], $schemaRecord["parentRecordID"]);

        $result = ApiUtils::convertInputKeys($schemaRecord);
        return $result;
    }

    /**
     * @return Permissions|null
     */
    protected function getPermissions(): ?Permissions
    {
        return $this->session->getPermissions();
    }

    /**
     * Get user drafts based on conditions
     *
     * @param array $where
     * @param int $limit
     * @param int $offset
     * @param string $order
     * @param string $direction
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getDrafts(array $where, int $limit, int $offset, string $order, string $direction = "desc"): array
    {
        $direction = strtolower($direction) === "asc" ? "asc" : "desc";
        if (self::draftSchedulingEnabled()) {
            $rows = $this->createSql()
                ->select("*")
                ->select("error as failedReason")
                ->from($this->getTable())
                ->where($where)
                ->limit($limit)
                ->offset($offset)
                ->orderBy($order, $direction)
                ->get()
                ->resultArray();
            if (count($rows)) {
                return array_map(function ($row) {
                    $row["attributes"] = json_decode($row["attributes"], true);
                    return $row;
                }, $rows);
            }
        }
        return $this->select(
            where: $where,
            options: [
                Model::OPT_LIMIT => $limit,
                Model::OPT_OFFSET => $offset,
                Model::OPT_ORDER => $order,
                Model::OPT_DIRECTION => $direction,
            ]
        );
    }

    /**
     *  Get count of Scheduled Drafts
     */
    public function getCurrentScheduledDraftsCount(): int
    {
        $currentTime = CurrentTimeStamp::getMySQL();

        $result = $this->select(
            ["draftStatus" => self::DRAFT_TYPE_SCHEDULED, "dateScheduled <" => $currentTime],
            ["select" => "COUNT(*) as total"]
        );

        return $result[0]["total"];
    }

    /**
     * {@inheritdoc}
     */
    public static function getSystemCallableMethods(): array
    {
        return ["publishScheduledDraftsIterator"];
    }

    /**
     * Create a long-runner task for processing Scheduled drafts
     * @param int $scheduleID
     */
    public function publishScheduledDraftsAction(int $scheduleID): TrackingSlipInterface
    {
        $action = new LongRunnerAction(self::class, "publishScheduledDraftsIterator", [0, $scheduleID]);
        return $this->longRunner->runDeferred($action);
    }

    /**
     * Run a long-runner job to publish any scheduled drafts that are due.
     *
     * @param int $lastExecutedDraftID
     * @return \Generator <array, array|LongRunnerNextArgs>
     */
    public function publishScheduledDraftsIterator(int $lastExecutedDraftID = 0, int $scheduledID = 0): \Generator
    {
        $logContext = [
            Logger::FIELD_TAGS => ["scheduledDrafts", "ScheduledDraftJob"],
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            "DateTime" => CurrentTimeStamp::getDateTime(),
        ];

        try {
            $activityModel = new \ActivityModel();
            $currentTime = CurrentTimeStamp::getMySQL();
            $currentSessionUserID = Gdn::session()->UserID;
            $userModel = Gdn::userModel();
            $failed = false;

            $this->draftScheduledModel->updateStatus($scheduledID, ScheduledDraftModel::PROCESSING);
            while (true) {
                try {
                    $scheduledDrafts = $this->getDrafts(
                        [
                            "draftStatus" => self::DRAFT_TYPE_SCHEDULED,
                            "dateScheduled <=" => $currentTime,
                            "draftID >" => $lastExecutedDraftID,
                        ],
                        self::SCHEDULED_DRAFTS_LIMIT,
                        0,
                        "dateScheduled",
                        "asc"
                    );
                    if (count($scheduledDrafts) === 0) {
                        break;
                    }
                    foreach ($scheduledDrafts as $draft) {
                        $lastExecutedDraftID = $draft["draftID"];
                        $userID = $draft["insertUserID"];
                        $user = $userModel->getID($userID, DATASET_TYPE_ARRAY);
                        $userExists = true;
                        if (!$user) {
                            $userExists = false;
                            $this->logger->info(
                                "skipped publishing draft as the user doesn't exist .",
                                $logContext + ["draftID" => $draft["draftID"]]
                            );
                            throw new NotFoundException("User", [
                                "userID" => $userID,
                                "draftID" => $draft["draftID"],
                            ]);
                        }
                        // Check if the user still have permission to publish the draft
                        if (!$userModel->checkPermission($user, "Garden.Schedule.Allow")) {
                            $this->logger->info(
                                "skipped publishing draft as the user doesn't have permission to publish the draft.",
                                $logContext + ["draftID" => $draft["draftID"], "userID" => $userID]
                            );
                            throw new PermissionException(
                                "Garden.Schedule.Allow",
                                $logContext + [
                                    "draftID" => $draft["draftID"],
                                    "userID" => $userID,
                                ]
                            );
                        }
                        // Create a new session with the user
                        $this->internalClient->setUserID($userID);
                        // re-fetch category permission for the user
                        \CategoryModel::$Categories = null;

                        // Check if the recordID exists. This case shouldn't happen as the record should be created. This is a safety check.
                        if (empty($draft["recordID"])) {
                            $this->logger->info(
                                "skipped publishing draft as the recordID was missing for the draft.",
                                $logContext + ["draftID" => $draft["draftID"]]
                            );
                            throw new ClientException("Record ID is missing for the Schedule", [
                                "draftID" => $draft["draftID"],
                            ]);
                        }

                        if ($draft["recordType"] === DiscussionModel::RECORD_TYPE) {
                            $discussion = $this->discussionModel->getID($draft["recordID"], DATASET_TYPE_ARRAY);
                            $method = $discussion ? "patch" : "post";
                            $this->processDiscussionDraft($draft, $method);
                        } else {
                            // We need to fire an event to process other record Types
                            $this->eventManager->fire("publishScheduledDraft", $draft);
                        }
                        // Delete the draft
                        $this->delete(["draftID" => $lastExecutedDraftID]);
                        yield new LongRunnerSuccessID($lastExecutedDraftID);
                    }
                } catch (LongRunnerTimeoutException $ex) {
                    return new LongRunnerNextArgs([$lastExecutedDraftID, $scheduledID]);
                } catch (\Exception $ex) {
                    $this->logger->error("Error processing scheduled draft.", $logContext + ["Exception" => $ex]);
                    if (isset($draft) && $draft["draftID"]) {
                        // Mark the draft as errored.
                        $this->update(
                            ["draftStatus" => self::DRAFT_TYPE_ERROR, "error" => $ex->getMessage()],
                            ["draftID" => $draft["draftID"]]
                        );
                        $activity = [
                            "ActivityType" => ScheduledPostFailedActivity::getActivityTypeID(),
                            "NotifyUserID" => $userID ?? $currentSessionUserID,
                            "ActivityUserID" => $userID ?? $currentSessionUserID,
                            "HeadlineFormat" => ScheduledPostFailedActivity::getProfileHeadline(),
                            "Story" =>
                                t("There was an error with your scheduled post.") .
                                " " .
                                t("Follow the link below to see details."),
                            "RecordType" => $draft["recordType"],
                            "RecordID" => $draft["draftID"],
                            "Route" => url("/drafts?tab=errors", true),
                            "Format" => "HTML",
                            "Data" => [
                                "Name" => $draft["attributes"]["draftMeta"]["name"],
                                "Reason" => "scheduled post failed",
                                "Error" => $ex->getMessage(),
                            ],
                            "Ext" => [
                                "Email" => [
                                    "Format" => "HTML",
                                    "ActionText" => t("Review"),
                                ],
                            ],
                            "Emailed" => \ActivityModel::SENT_PENDING,
                            "Notified" => \ActivityModel::SENT_SKIPPED,
                        ];
                        if ($userExists ?? false) {
                            // Send Notification to the user when the scheduled post fails
                            $activityModel->queue($activity, false, ["Force" => true]);
                            $activityModel->saveQueue();
                        }
                    }
                    yield new LongRunnerFailedID($lastExecutedDraftID);
                }
            }
        } catch (\Exception $ex) {
            $this->logger->error(
                "Error occurred while trying to process scheduled draft.",
                $logContext + ["Exception" => $ex]
            );
            $failed = true;
            $this->draftScheduledModel->updateStatus($scheduledID, ScheduledDraftModel::FAILED);
        } catch (\Throwable $t) {
            $this->logger->error(
                "Error occurred while trying to process scheduled draft.",
                $logContext + ["Exception" => $t, "errormessage" => $t->getMessage()]
            );
            $failed = true;
            $this->draftScheduledModel->updateStatus($scheduledID, ScheduledDraftModel::FAILED);
        } finally {
            $this->internalClient->setUserID($currentSessionUserID);
            if (!$failed) {
                $this->draftScheduledModel->updateStatus($scheduledID, ScheduledDraftModel::PROCESSED);
            }
        }
        return LongRunner::FINISHED;
    }

    /**
     * create a new post or update the existing post based on the draft data
     *
     * @param array $draftData
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function processDiscussionDraft(array $draftData, string $method): bool
    {
        [$postData, $postUrl] = $this->getDiscussionPostDataAndUrlFromDrafts($draftData);
        if ($method === "patch") {
            // Update the discussion
            $postUrl = "/discussions/{$postData["discussionID"]}";
        }

        try {
            $result = $this->internalClient->$method($postUrl, $postData);
        } catch (\Exception $ex) {
            throw $ex;
        }
        $record = $result->getBody();
        return !empty($record["discussionID"]);
    }

    /**
     * Provide discussion post data and post url from draft data
     *
     * @param array $draftData
     * @return array
     * @throws ClientException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function getDiscussionPostDataAndUrlFromDrafts(array $draftData): array
    {
        $postTypeModel = Gdn::getContainer()->get(PostTypeModel::class);
        $postTypeID = $discussionType = $draftData["attributes"]["draftMeta"]["postTypeID"] ?? "discussion";
        $postUrl = "/discussions";
        if (!in_array($postTypeID, ["discussion", "idea", "question"])) {
            $postTypeRecord = $postTypeModel->getByID($postTypeID);
            if (
                empty($postTypeRecord) ||
                !in_array($postTypeRecord["parentPostTypeID"], ["discussion", "idea", "question"])
            ) {
                throw new ClientException("Invalid post type provided");
            }
            if ($postTypeRecord["parentPostTypeID"] !== "discussion") {
                $postUrl .= "/" . $postTypeRecord["parentPostTypeID"];
            }
            $discussionType = $postTypeRecord["parentPostTypeID"];
        } else {
            if ($postTypeID !== "discussion") {
                $postUrl .= "/" . $postTypeID;
            }
        }
        $categoryID = $draftData["attributes"]["draftMeta"]["categoryID"] ?? ($draftData["parentRecordID"] ?? null);

        $postData = [
            "body" => $draftData["attributes"]["body"],
            "format" => $draftData["attributes"]["format"],
            "name" => $draftData["attributes"]["draftMeta"]["name"],
            "categoryID" =>
                $draftData["attributes"]["draftMeta"]["categoryID"] ?? ($draftData["parentRecordID"] ?? null),
            "postTypeID" => $postTypeID,
            "type" => ucfirst($discussionType),
        ];
        if (!empty($draftData["attributes"]["draftMeta"]["postMeta"])) {
            $postData["postMeta"] = $draftData["attributes"]["draftMeta"]["postMeta"];
        }

        $canAnnounce = $this->getPermissions()->has(
            "discussions.announce",
            $categoryID,
            Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
            \CategoryModel::PERM_JUNCTION_TABLE
        );
        if (!empty($draftData["attributes"]["draftMeta"]["pinLocation"]) && $canAnnounce) {
            $postData["pinLocation"] = $draftData["attributes"]["draftMeta"]["pinLocation"];
            $postData["pinned"] = $postData["pinLocation"] === "none" ? false : true;
            if ($postData["pinLocation"] === "none") {
                unset($postData["pinLocation"]);
            }
        }
        if (!empty($draftData["recordID"])) {
            $postData["discussionID"] = $draftData["recordID"];
        }
        if (!empty($draftData["draftID"])) {
            $postData["draftID"] = $draftData["draftID"];
        }
        if (!empty($draftData["attributes"]["groupID"])) {
            $postData["groupID"] = $draftData["attributes"]["groupID"];
        }
        if (isset($draftData["attributes"]["draftMeta"]["newTagNames"])) {
            $postData["newTagNames"] = $draftData["attributes"]["draftMeta"]["newTagNames"];
        }
        if (isset($draftData["attributes"]["draftMeta"]["tagIDs"])) {
            $postData["tagIDs"] = $draftData["attributes"]["draftMeta"]["tagIDs"];
        }
        if (isset($draftData["attributes"]["draftMeta"]["publishedSilently"])) {
            $postData["publishedSilently"] = $draftData["attributes"]["draftMeta"]["publishedSilently"];
        }
        return [$postData, $postUrl];
    }
}
