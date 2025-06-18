<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Container\ContainerException;
use Garden\EventManager;
use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Gdn_SQLDriver;
use PermissionNotificationGenerator;
use Ramsey\Uuid\Uuid;
use Vanilla\Community\Events\ReportEvent;
use Vanilla\Community\Events\SpamEvent;
use Vanilla\CurrentTimeStamp;
use Vanilla\Dashboard\Activity\ReportActivity;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\ResourceEventProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Formatting\Formats\WysiwygFormat;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Models\SpamReport;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

/**
 * Model for GDN_report table.

 */
class ReportModel extends PipelineModel
{
    public const STATUS_NEW = "new";
    public const STATUS_REJECTED = "rejected";
    public const STATUS_ESCALATED = "escalated";
    public const STATUS_DISMISSED = "dismissed";

    public const STATUSES = [self::STATUS_NEW, self::STATUS_ESCALATED, self::STATUS_DISMISSED, self::STATUS_REJECTED];

    /**
     * Constructor.
     */
    public function __construct(
        private \Gdn_Session $session,
        private FormatService $formatService,
        private \CategoryModel $categoryModel,
        private \UserModel $userModel,
        private ReportReasonModel $reportReasonModel,
        private CommunityManagementRecordModel $communityManagementRecordModel,
        private ResourceEventProcessor $resourceEventProcessor,
        private EventManager $eventManager
    ) {
        parent::__construct("report");
        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["premoderatedRecord"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isPending", "isPendingUpdate"]));

        $this->resourceEventProcessor->setResourceEventClass(ReportEvent::class);
        $this->addPipelineProcessor($this->resourceEventProcessor);
    }

    /**
     * Join a count of reports onto a set of DB rows.
     *
     * @param array $rowOrRows
     * @param string $recordType
     *
     * @return void
     */
    public function expandReportMeta(array &$rowOrRows, string $recordType): void
    {
        if (ArrayUtils::isAssociative($rowOrRows)) {
            $rows = [&$rowOrRows];
        } else {
            $rows = &$rowOrRows;
        }

        $recordIDField = "{$recordType}ID";
        $recordIDs = array_column($rows, $recordIDField);

        $countRows = $this->createSql()
            ->select([
                "r.recordID",
                "COUNT(DISTINCT(r.reportID)) as countReports",
                "MAX(r.dateInserted) as dateLastReport",
                "JSON_ARRAYAGG(rrj.reportReasonID) as reportReasonIDs",
                "JSON_ARRAYAGG(r.insertUserID) as reportUserIDs",
            ])
            ->from("report r")
            ->leftJoin("reportReasonJunction rrj", "r.reportID = rrj.reportID")
            ->where([
                "recordType" => $recordType,
                "recordID" => $recordIDs,
            ])
            ->groupBy(["recordType", "recordID"])
            ->get()
            ->resultArray();

        $countRowsByRecordID = array_column($countRows, null, "recordID");

        foreach ($countRowsByRecordID as &$countRow) {
            $countRow["reportReasonIDs"] = array_unique(array_filter(json_decode($countRow["reportReasonIDs"], true)));
            $countRow["reportUserIDs"] = array_unique(array_filter(json_decode($countRow["reportUserIDs"], true)));
            $countRow["countReportUsers"] = count($countRow["reportUserIDs"]);
        }

        $this->reportReasonModel->expandReportReasonArrays($countRowsByRecordID);
        $this->expandReportUsers($countRowsByRecordID);

        foreach ($rows as &$row) {
            $recordID = $row[$recordIDField] ?? null;
            $foundCountRow = $countRowsByRecordID[$recordID] ?? null;
            if ($foundCountRow === null) {
                $row["reportMeta"] = [
                    "countReports" => 0,
                    "countReportUsers" => 0,
                    "dateLastReport" => null,
                    "reportReasonIDs" => [],
                    "reportReasons" => [],
                    "reportUserIDs" => [],
                    "reportUsers" => [],
                ];
                continue;
            }

            $row["reportMeta"] = $foundCountRow;
        }
    }

    /**
     * Get where of ReportIDs.
     *
     * @param array $where
     *
     * @return array
     * @throws \Exception
     */
    public function createCountedReportsWhere(array $where): array
    {
        $query = $this->createCountedReportsQuery($where);
        $groupedResult = $query->get()->resultArray();
        $reportIDs = array_column($groupedResult, "maxReportID");
        return ["r.reportID" => $reportIDs];
    }

    /**
     * Get Query for counted reports.
     *
     * @param array $where
     *
     * @return Gdn_SQLDriver
     */
    public function createCountedReportsQuery(array $where): Gdn_SQLDriver
    {
        if (isset($where["reportReasonID"])) {
            $where["rrj.reportReasonID"] = $where["reportReasonID"];
            unset($where["reportReasonID"]);
        }

        if (isset($where["categoryID"])) {
            $where["placeRecordID"] = $where["categoryID"];
            unset($where["categoryID"]);
        }
        $wherePrefixes = [];
        foreach ($where as $key => $value) {
            $wherePrefixes[] = explode(".", $key)[0] ?? null;
        }
        $wherePrefixes = array_unique($wherePrefixes);
        $countReports = $where["countReports"];
        unset($where["countReports"]);

        $includeSubcategories = $where["includeSubcategories"] ?? false;
        unset($where["includeSubcategories"]);

        $query = $this->createSql()
            ->select("r.recordID", "count", "countReports")
            ->select("r.recordID")
            ->select("r.recordType")
            ->select("r.reportID", "max", "maxReportID")
            ->from("report r")
            ->where($where)
            ->groupBy("r.recordID, r.recordType")
            ->having("countReports >=", $countReports);

        if (in_array("rrj", $wherePrefixes)) {
            $query->leftJoin("reportReasonJunction rrj", "r.reportID = rrj.reportID");
        }
        if (in_array("ur", $wherePrefixes)) {
            $query->leftJoin("UserRole ur", "r.insertUserID = ur.UserID");
        }
        if (in_array("placeRecordID", $where)) {
            $categories = [];
            foreach ($where["placeRecordID"] as $categoryID) {
                $categories[] = $this->categoryModel->getSearchCategoryIDs($categoryID, false, $includeSubcategories);
            }
            $query->where(["r.placeRecordType" => "category", "r.placeRecordID" => $categories]);
        }
        return $query;
    }

    /**
     * @param array $where
     *
     * @return Gdn_SQLDriver
     */
    private function createVisibleReportsQuery(array $where): Gdn_SQLDriver
    {
        $wherePrefixes = [];
        foreach ($where as $key => $value) {
            $wherePrefixes[] = explode(".", $key)[0] ?? null;
        }
        $wherePrefixes = array_unique($wherePrefixes);

        $query = $this->createSql()
            ->select("r.*")
            ->select("MAX(rej.escalationID) as escalationID")
            ->from("report r")
            ->where($where)
            ->groupBy("r.reportID");

        if (in_array("rrj", $wherePrefixes)) {
            $query->leftJoin("reportReasonJunction rrj", "r.reportID = rrj.reportID");
        }
        if (in_array("ur", $wherePrefixes)) {
            $query->leftJoin("UserRole ur", "r.insertUserID = ur.UserID");
        }
        $query->leftJoin("reportEscalationJunction rej", "r.reportID = rej.reportID");

        // Filter category permissions
        if (!$this->session->checkPermission("community.moderate")) {
            $visibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
                $this->session->UserID,
                "Vanilla.Posts.Moderate"
            );
            $query->where(["r.placeRecordType" => "category", "r.placeRecordID" => $visibleCategoryIDs]);
        }
        return $query;
    }

    /**
     * Select reports visible to the current user and normalize them.
     *
     * @param array $where
     * @param array $opts Standard model options.
     *
     * @return array<array> The rows.
     */
    public function selectVisibleReports(array $where, array $opts = []): array
    {
        $visibleReportsQuery = $this->createVisibleReportsQuery($where);
        $query = $visibleReportsQuery->applyModelOptions($opts);
        $rows = $query->get()->resultArray();
        $rows = $this->validateOutputRows($rows, $opts);
        $results = $this->normalizeRows($rows);
        return $results;
    }

    /**
     * @param array $where
     * @param int $limit
     *
     * @return int
     */
    public function countVisibleReports(array $where, int $limit = 10000): int
    {
        $count = $this->createVisibleReportsQuery($where)->getPagingCount("r.reportID", $limit);
        return $count;
    }

    /**
     * Given a reportID, insert reasons for the report into the GDN_reportReasonJunction table.
     *
     * @param int $reportID
     * @param array $reportReasonIDs
     *
     * @return void
     */
    public function putReasonsForReport(int $reportID, array $reportReasonIDs): void
    {
        $this->createSql()->delete("reportReasonJunction", ["reportID" => $reportID]);

        $rows = [];
        foreach ($reportReasonIDs as $reportReasonID) {
            $rows[] = [
                "reportID" => $reportID,
                "reportReasonID" => $reportReasonID,
            ];
        }
        $this->createSql()->insert("reportReasonJunction", $rows);
    }

    /**
     * Given an array of reports, normalize them for usage.
     *
     * @param array<array> $reports
     *
     * @return array<array>
     */
    public function normalizeRows(array $reports): array
    {
        $reasonFragments = $this->reportReasonModel->getReportReasonsFragments([
            "reportID" => array_column($reports, "reportID"),
        ]);
        $reasonFragmentsByReportID = ArrayUtils::arrayColumnArrays($reasonFragments, null, "reportID");

        foreach ($reports as &$report) {
            $reportID = $report["reportID"];
            $report["reasons"] = $reasonFragmentsByReportID[$reportID] ?? [];

            $report["noteHtml"] = trim($this->formatService->renderHTML($report["noteBody"], $report["noteFormat"]));
            $report["recordHtml"] = trim(
                $this->formatService->renderHTML($report["recordBody"], $report["recordFormat"])
            );
            $report["recordPlainText"] = trim(
                $this->formatService->renderPlainText($report["recordBody"], $report["recordFormat"])
            );
            unset($report["noteBody"], $report["noteFormat"], $report["recordBody"], $report["recordBodyFormat"]);
            $report["isPendingUpdate"] = (bool) $report["isPendingUpdate"];
            $report["isPending"] = (bool) $report["isPending"];
            unset($report["premoderatedRecord"]);
            if (isset($report["escalationID"])) {
                $report["escalationUrl"] = EscalationModel::escalationUrl($report["escalationID"]);
            } else {
                $report["escalationUrl"] = null;
                $report["escalationID"] = null;
            }
        }
        $this->communityManagementRecordModel->joinLiveRecordData($reports);
        return $reports;
    }

    /**
     * Create the database structure for reporting tables.
     *
     * @param \Gdn_DatabaseStructure $structure
     *
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("report")
            ->primaryKey("reportID")

            // About the report
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)
            ->column("noteBody", "mediumtext", true)
            ->column("noteFormat", "varchar(20)", true)
            ->column("status", "varchar(50)")

            // About the post
            ->column("recordUserID", "int")
            ->column("recordType", "varchar(50)")
            ->column("recordID", "int", true)
            ->column("placeRecordType", "varchar(50)")
            ->column("placeRecordID", "int")
            ->column("recordName", "text")
            ->column("recordBody", "mediumtext")
            ->column("recordFormat", "mediumtext")
            ->column("recordDateInserted", "datetime", true)
            ->column("recordInsertIPAddress", "ipaddress", true)
            ->column("premoderatedRecord", "json", true)
            ->column("isPending", "tinyint(1)", "0")
            ->column("isPendingUpdate", "tinyint(1)", "0")
            ->set(false, false);

        $structure
            ->table("report")
            ->createIndexIfNotExists("IX_report_recordType_recordID", ["recordType", "recordID"])
            ->createIndexIfNotExists("IX_report_placeRecordType_placeRecordID_dateInserted", [
                "placeRecordType",
                "placeRecordID",
                "dateInserted",
            ])
            ->createIndexIfNotExists("IX_report_placeRecordType_placeRecordID_dateUpdated", [
                "placeRecordType",
                "placeRecordID",
                "dateUpdated",
            ])
            ->createIndexIfNotExists("IX_report_insertUserID", ["insertUserID"])
            ->createIndexIfNotExists("IX_report_recordUserID", ["recordUserID"]);

        $structure
            ->table("reportReasonJunction")
            ->primaryKey("reportReasonJunctionID")
            ->column("reportID", "int", false, "index")
            ->column("reportReasonID", "varchar(255)", false, "index")
            ->set(false, false);

        $structure
            ->table("reportEscalationJunction")
            ->primaryKey("reportEscalationJunctionID")
            ->column("reportID", "int", false, "index")
            ->column("escalationID", "int", false, "index")
            ->set(false, false);
    }

    /**
     * Expand reportingUsers on a set of rows.
     *
     * Because this is an array of ids, we can't use {@link UserModel::expandUsers()}.
     *
     * @param array<array{reportUserIDs: int[]}> $rows
     *
     * @return array
     */
    public function expandReportUsers(array &$rows): array
    {
        $userIDs = [];
        foreach ($rows as $row) {
            $userIDs = array_merge($userIDs, $row["reportUserIDs"]);
        }
        $userIDs = array_unique($userIDs);
        $userFragmentsByID = $this->userModel->getUserFragments($userIDs);
        foreach ($rows as &$row) {
            $row["reportUsers"] = array_map(function ($userID) use ($userFragmentsByID) {
                return $userFragmentsByID[$userID] ?? null;
            }, $row["reportUserIDs"] ?? []);
            $row["reportUsers"] = array_filter($row["reportUsers"]);
        }
        return $rows;
    }

    /**
     * Get a schema for things that are joined/aggregated with reports.
     *
     * @return Schema
     */
    public static function reportMetaSchema(): Schema
    {
        return Schema::parse([
            "reportReasons:a" => ReportReasonModel::reasonFragmentSchema(),
            "dateLastReport:dt?",
            "countReports:i",
            "countReportUsers:i",
            "reportUserIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "reportUsers:a" => new UserFragmentSchema(),
        ]);
    }

    /**
     * Given a reportID that has premoderation data, save the premoderated record into the appropriate record table.
     * Then mark the report as done.
     *
     * @param int $reportID
     * @return void
     */
    public function saveRecordFromPremoderation(int $reportID): void
    {
        $report = $this->selectSingle(["reportID" => $reportID]);
        $premoderatedRecord = $report["premoderatedRecord"];
        if (empty($premoderatedRecord)) {
            throw new ServerException("No premoderated record found.");
        }

        $premoderatedRecord["InsertUserID"] = $report["recordUserID"]; // Preserve the original user.
        if (!$report["isPendingUpdate"]) {
            $premoderatedRecord["DateInserted"] = CurrentTimeStamp::getMySQL(); // Forward date the post in case it took a while to moderate.
        }

        switch ($report["recordType"]) {
            case "discussion":
                if (isset($report["recordID"])) {
                    $premoderatedRecord["DiscussionID"] = $report["recordID"];
                    $premoderatedRecord["DateUpdated"] = CurrentTimeStamp::getMySQL();
                }
                $discussionModel = \Gdn::getContainer()->get(\DiscussionModel::class);
                $discussionID = $discussionModel->save($premoderatedRecord, [
                    "CheckPermission" => false,
                    "SpamCheck" => false,
                    "skipSpamCheck" => true,
                ]);
                $recordID = $discussionID;
                ModelUtils::validationResultToValidationException($discussionModel);
                ModelUtils::validateSaveResultPremoderation($discussionModel, "discussion");

                break;
            case "comment":
                if (isset($report["recordID"])) {
                    $premoderatedRecord["CommentID"] = $report["recordID"];
                    $premoderatedRecord["DateUpdated"] = CurrentTimeStamp::getMySQL();
                }
                $commentModel = \Gdn::getContainer()->get(\CommentModel::class);
                $commentID = $commentModel->save($premoderatedRecord, [
                    "CheckPermission" => false,
                    "SpamCheck" => false,
                    "skipSpamCheck" => true,
                ]);
                $recordID = $commentID;
                ModelUtils::validationResultToValidationException($commentModel);
                ModelUtils::validateSaveResultPremoderation($commentModel, "comment");
                break;
            default:
                throw new ServerException("Invalid record type.");
        }

        // Persist this recordID into the escalation record
        $this->update(
            set: [
                "recordID" => $recordID,
                "premoderatedRecord" => null,
                "isPending" => false,
                "isPendingUpdate" => false,
            ],
            where: ["reportID" => $reportID]
        );
    }

    /**
     * Fetch a report based on its ID.
     *
     * @param int $reportID
     * @return array|null
     * @throws NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function getReport(int $reportID): ?array
    {
        try {
            $report = $this->selectSingle(["reportID" => $reportID]);
        } catch (NoResultsException $resultsException) {
            throw new NotFoundException("report", ["reportID" => $reportID], $resultsException);
        }

        return $this->normalizeRows([$report])[0] ?? null;
    }

    /**
     * Overwrite the `insert()` function to send out notifications.
     *
     * @param array $set
     * @param array $options
     * @return int
     * @throws NotFoundException
     * @throws \Garden\Schema\ValidationException
     */
    public function insert(array $set, array $options = [])
    {
        $reportID = parent::insert($set, $options);
        if (!empty($set["reportReasonIDs"])) {
            $this->putReasonsForReport($reportID, $set["reportReasonIDs"]);
        }

        $report = $this->getReport($reportID);
        $this->notifyNewReport($report);

        return $reportID;
    }

    /**
     * Send out notifications for a new report.
     *
     * @param array $report
     * @return void
     * @throws ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function notifyNewReport(array $report): void
    {
        $reporter = $this->userModel->getID($report["insertUserID"]);
        $activity = [
            "ActivityType" => ReportActivity::getActivityTypeID(),
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "ActivityUserID" => $report["insertUserID"],
            "HeadlineFormat" => sprintf(ReportActivity::getProfileHeadline(), $reporter->Name, $report["recordName"]),
            "PluralHeadlineFormat" => sprintf(ReportActivity::getPluralHeadline(), $report["recordName"]),
            "RecordType" => $report["recordType"],
            "RecordID" => $report["recordID"],
            "Route" => $report["recordUrl"] ?? url("/dashboard/content/reports", true),
            "Data" => [
                "escalationID" => $report["reportID"],
                "name" => $report["recordName"],
                "Reason" => ReportActivity::getActivityReason(),
                "reportID" => $report["reportID"],
            ],
            "Ext" => [
                "Email" => [
                    "Format" => WysiwygFormat::FORMAT_KEY,
                    "Story" => $this->getReportEmailBody($report),
                ],
            ],
        ];

        $notificationGenerator = Gdn::getContainer()->get(PermissionNotificationGenerator::class);
        $notificationGenerator->notify(
            activity: $activity,
            permissions: "Garden.Moderation.Manage",
            preference: ReportActivity::getPreference(),
            hasDefaultPreferences: true
        );
    }

    /**
     * Generate the email for a new report notification.
     *
     * @param array $report
     * @return string
     */
    private static function getReportEmailBody(array $report): string
    {
        $body = "<div>" . ReportActivity::getActivityReason();
        if (!isset($report["reasons"])) {
            return $body . "<div>";
        }

        $body = "<h4>" . t("Reporting reason: ") . "</h4>";

        $reports = [];
        foreach ($report["reasons"] as $reason) {
            $reports[] = t($reason["name"]);
        }

        $body .= "<p>" . implode(", ", $reports) . "</p>";

        if (isset($report["noteHtml"])) {
            $body .= "<h4>" . t("Additional notes: ") . "</h4>";
            $body .= $report["noteHtml"];
        }

        return $body . "<div>";
    }

    /**
     * Conditionally dispatches a spam event for a given report record if the reason is spam.
     *
     * @param array $report
     * @return void
     */
    public function dispatchSpamEventFromReport(array $report): void
    {
        $reasons = array_column($report["reasons"], "reportReasonID");
        if (in_array(ReportReasonModel::INITIAL_REASON_SPAM, $reasons, true)) {
            $recordUser = $this->userModel->getID($report["recordUserID"], DATASET_TYPE_ARRAY);
            $record = $this->getRecord($report["recordType"], $report["recordID"]);

            $spamReport = new SpamReport(
                $report["recordType"],
                $recordUser["Name"] ?? null,
                $recordUser["Email"] ?? null,
                $report["recordName"] . "\n\n" . $report["recordPlainText"],
                isset($report["recordInsertIPAddress"]) ? ipDecode($report["recordInsertIPAddress"]) : null,
                $record["url"] ?? null
            );

            $this->eventManager->dispatch(new SpamEvent($spamReport));
        }
    }

    /**
     * Retrieve a normalized record for the given recordType and recordID.
     *
     * @param string $recordType
     * @param int $recordID
     * @return array|null
     */
    public static function getRecord(string $recordType, int $recordID): ?array
    {
        switch (strtolower($recordType)) {
            case "discussion":
                $model = Gdn::getContainer()->get(\DiscussionModel::class);
                $record = $model->getID($recordID, DATASET_TYPE_ARRAY);
                return $record ? $model->normalizeRow($record) : null;
            case "comment":
                $model = Gdn::getContainer()->get(\CommentModel::class);
                $record = $model->getID($recordID, DATASET_TYPE_ARRAY);
                return $record ? $model->normalizeRow($record) : null;
        }
        return null;
    }

    /**
     * Iterator to get records based on condition in batches
     *
     * @param array $where
     * @param array $orderFields
     * @param string $orderDirection
     * @param int $batchSize
     * @return \Generator
     * @throws \Exception
     */
    public function getWhereIterator(
        array $where = [],
        array $orderFields = [],
        string $orderDirection = "asc",
        int $batchSize = 100
    ): \Generator {
        $offset = 0;

        $sql = $this->createSql();
        while (true) {
            $records = $sql
                ->select()
                ->from("report r")
                ->where($where)
                ->orderBy($orderFields, $orderDirection)
                ->limit($batchSize, $offset)
                ->get()
                ->resultArray();

            foreach ($records as $record) {
                $primaryKey = $record["reportID"];
                yield $primaryKey => $record;
            }

            $offset += $batchSize;
            if (count($records) < $batchSize) {
                return;
            }
        }
    }
}
