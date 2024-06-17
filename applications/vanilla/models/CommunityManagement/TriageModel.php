<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Formatting\FormatService;
use Vanilla\Utility\SchemaUtils;

/**
 * Model for querying discussions/comments to triage.
 */
class TriageModel
{
    private \Gdn_Database $db;
    private ReportModel $reportModel;
    private ReportReasonModel $reportReasonModel;
    private FormatService $formatService;
    private \CategoryModel $categoryModel;
    private \Gdn_Session $session;
    private RecordStatusModel $recordStatusModel;

    /**
     * Constructor.
     */
    public function __construct(
        \Gdn_Database $db,
        ReportModel $reportModel,
        ReportReasonModel $reportReasonModel,
        FormatService $formatService,
        \CategoryModel $categoryModel,
        \Gdn_Session $session,
        RecordStatusModel $recordStatusModel
    ) {
        $this->db = $db;
        $this->reportModel = $reportModel;
        $this->reportReasonModel = $reportReasonModel;
        $this->formatService = $formatService;
        $this->categoryModel = $categoryModel;
        $this->session = $session;
        $this->recordStatusModel = $recordStatusModel;
    }

    /**
     * Get a paging count for the triage query.
     *
     * @param array $filters
     * @return int
     */
    public function countTriaged(array $filters): int
    {
        $query = $this->createTriagedQuery($filters);
        return $query->getPagingCount("d.DiscussionID");
    }

    /**
     * Create the base triage query.
     *
     * @param array $filters
     *
     * @return \Gdn_SQLDriver
     */
    private function createTriagedQuery(array $filters): \Gdn_SQLDriver
    {
        $discussionQuery = $this->db
            ->createSql()
            ->from("Discussion d")
            ->select([
                "d.DiscussionID as recordID",
                "'discussion' as recordType",
                "d.CategoryID as placeRecordID",
                "'category' as placeRecordType",
                "d.Name as recordName",
                "d.Body as recordBody",
                "d.Format as recordFormat",
                "d.InsertUserID as recordUserID",
                "d.DateInserted as recordDateInserted",
                "d.DateUpdated as recordDateUpdated",
                "d.statusID as recordStatusID",
                "d.internalStatusID as recordInternalStatusID",
                "COUNT(DISTINCT(r.reportID)) as countReports",
                "MAX(r.dateInserted) as dateLastReport",
                "JSON_ARRAYAGG(rrj.reportReasonID) as reportReasonIDs",
                "JSON_ARRAYAGG(r.insertUserID) as reportUserIDs",
            ])
            ->leftJoin("report r", "r.recordType = 'discussion' AND r.recordID = d.DiscussionID")
            ->leftJoin("reportReasonJunction rrj", "r.reportID = rrj.reportID")
            ->groupBy("d.DiscussionID");

        // Apply the discussion permission filter.
        $visibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
            $this->session->UserID,
            "Vanilla.Posts.Moderate"
        );
        $discussionQuery->where(["d.CategoryID" => $visibleCategoryIDs]);

        // Apply the other wheres
        if (($filters["placeRecordType"] ?? null) === "category" && isset($filters["placeRecordID"])) {
            $discussionQuery->where("d.CategoryID", $filters["placeRecordID"]);
        }

        if (isset($filters["recordInternalStatusID"])) {
            $discussionQuery->where("d.internalStatusID", $filters["recordInternalStatusID"]);
        }

        if (isset($filters["recordUserID"])) {
            $discussionQuery->where("d.InsertUserID", $filters["recordUserID"]);
        }

        if (isset($filters["recordUserRoleID"])) {
            $discussionQuery->leftJoin("UserRole ur", "d.InsertUserID = ur.UserID");
            $discussionQuery->where("ur.RoleID", $filters["recordUserRoleID"]);
        }

        return $discussionQuery;
    }

    /**
     * Query discussions/comments to triage.
     *
     * @param array{placeRecordType?: string, placeRecordID?: mixed, recordInternalStatusID?: mixed} $filters
     * @param array $options Standard model options.
     * @return array<array>
     */
    public function queryTriaged(array $filters, array $options): array
    {
        $discussionQuery = $this->createTriagedQuery($filters);

        // Apply limits and offsets.
        $query = $discussionQuery->applyModelOptions($options);

        $results = $query->get()->resultArray();

        // Normalize array values.
        foreach ($results as &$result) {
            // Normalize the records
            $result["recordUrl"] = $this->triageRecordUrl($result);
            $result["recordIsLive"] = true;
            $result["recordWasEdited"] = dateCompare($result["recordDateUpdated"], $result["recordDateInserted"]) > 0;
            $result["recordExcerpt"] = $this->formatService->renderExcerpt(
                $result["recordBody"],
                $result["recordFormat"]
            );

            // Handle the json columns
            $result["reportReasonIDs"] = array_unique(array_filter(json_decode($result["reportReasonIDs"], true)));
            $result["reportUserIDs"] = array_unique(array_filter(json_decode($result["reportUserIDs"], true)));

            $result["countReportUsers"] = count($result["reportUserIDs"]);
        }

        $this->reportReasonModel->expandReportReasonArrays($results);
        $this->reportModel->expandReportUsers($results);
        $this->recordStatusModel->expandStatuses($results);

        SchemaUtils::validateArray($results, self::triageRecordSchema());

        return $results;
    }

    /**
     * Given a triage row, create a URL for the record and apply it on the row.
     *
     * @param array $row
     *
     * @return string|null
     */
    private function triageRecordUrl(array &$row): ?string
    {
        if ($row["recordType"] === "discussion") {
            return \DiscussionModel::discussionUrl([
                "DiscussionID" => $row["recordID"],
                "CategoryID" => $row["placeRecordID"],
                "Name" => $row["recordName"],
            ]);
        } else {
            return null;
        }
    }

    /**
     * Get the schema for a triage record.
     *
     * @return Schema
     */
    public static function triageRecordSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            CommunityManagementRecordModel::fullRecordSchema(),
            ReportModel::reportRelatedSchema()
        );
    }
}
