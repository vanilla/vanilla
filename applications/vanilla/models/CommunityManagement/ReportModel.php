<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Google\ApiCore\ValidationException;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Formatting\FormatFieldTrait;
use Vanilla\Formatting\FormatService;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\PipelineModel;
use Vanilla\Models\UserFragmentSchema;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\SchemaUtils;

/**
 * Model for GDN_report table.

 */
class ReportModel extends PipelineModel
{
    public const STATUS_NEW = "new";
    public const STATUS_ESCALATED = "escalated";
    public const STATUS_DISMISSED = "dismissed";

    public const STATUSES = [self::STATUS_NEW, self::STATUS_ESCALATED, self::STATUS_DISMISSED];

    private \Gdn_Session $session;
    private FormatService $formatService;
    private \CategoryModel $categoryModel;
    private \UserModel $userModel;
    private ReportReasonModel $reportReasonModel;
    private CommunityManagementRecordModel $communityManagementRecordModel;

    /**
     * Constructor.
     */
    public function __construct(
        \Gdn_Session $session,
        FormatService $formatService,
        \CategoryModel $categoryModel,
        \UserModel $userModel,
        ReportReasonModel $reportReasonModel,
        CommunityManagementRecordModel $communityManagementRecordModel
    ) {
        parent::__construct("report");
        $this->session = $session;
        $this->formatService = $formatService;
        $this->categoryModel = $categoryModel;
        $this->userModel = $userModel;
        $this->reportReasonModel = $reportReasonModel;
        $this->communityManagementRecordModel = $communityManagementRecordModel;

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * @param array $where
     *
     * @return \Gdn_SQLDriver
     */
    private function createVisibleReportsQuery(array $where): \Gdn_SQLDriver
    {
        $wherePrefixes = [];
        foreach ($where as $key => $value) {
            $wherePrefixes[] = explode(".", $key)[0] ?? null;
        }
        $wherePrefixes = array_unique($wherePrefixes);

        $query = $this->createSql()
            ->select("r.*")
            ->from("report r")
            ->where($where)
            ->groupBy("r.reportID");

        if (in_array("rrj", $wherePrefixes)) {
            $query->leftJoin("reportReasonJunction rrj", "r.reportID = rrj.reportID");
        }
        if (in_array("ur", $wherePrefixes)) {
            $query->leftJoin("UserRole ur", "r.insertUserID = ur.UserID");
        }

        // Filter category permissions
        $visibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
            $this->session->UserID,
            "Vanilla.Posts.Moderate"
        );
        $query->where(["r.placeRecordType" => "category", "r.placeRecordID" => $visibleCategoryIDs]);
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
     * @return int
     */
    public function countVisibleReports(array $where): int
    {
        $count = $this->createVisibleReportsQuery($where)->getPagingCount("r.reportID");
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
            unset($report["noteBody"], $report["noteFormat"], $report["recordBody"], $report["recordBodyFormat"]);
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
            ->column("recordID", "int")
            ->column("placeRecordType", "varchar(50)")
            ->column("placeRecordID", "int")
            ->column("recordName", "text")
            ->column("recordBody", "mediumtext")
            ->column("recordFormat", "mediumtext")
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
    public static function reportRelatedSchema(): Schema
    {
        return Schema::parse([
            "reportReasons:a" => ReportReasonModel::reasonFragmentSchema(),
            "countReports:i",
            "dateLastReport:dt?",
            "countReportUsers:i",
            "reportUserIDs:a" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "reportUsers:a" => new UserFragmentSchema(),
        ]);
    }
}
