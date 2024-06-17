<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\SchemaUtils;

/**
 * Model for GDN_escalation table.
 */
class EscalationModel extends PipelineModel
{
    public const UNASSIGNED_USER_ID = -4;
    public const STATUS_OPEN = "open";
    public const STATUS_IN_PROGRESS = "in-progress";
    public const STATUS_ON_HOLD = "on-hold";
    public const STATUS_EXTERNAL_STAR = "external-zendesk";
    public const STATUS_DONE = "done";

    public const STATUSES = [
        self::STATUS_OPEN,
        self::STATUS_IN_PROGRESS,
        self::STATUS_ON_HOLD,
        self::STATUS_EXTERNAL_STAR,
        self::STATUS_DONE,
    ];

    private \Gdn_Session $session;
    private \CategoryModel $categoryModel;
    private ReportModel $reportModel;
    private ReportReasonModel $reportReasonModel;
    private CommunityManagementRecordModel $communityManagementRecordModel;

    /**
     * Constructor
     */
    public function __construct(
        \Gdn_Session $session,
        \CategoryModel $categoryModel,
        ReportModel $reportModel,
        ReportReasonModel $reportReasonModel,
        CommunityManagementRecordModel $communityManagementRecordModel
    ) {
        parent::__construct("escalation");
        $this->session = $session;
        $this->categoryModel = $categoryModel;
        $this->reportModel = $reportModel;
        $this->reportReasonModel = $reportReasonModel;
        $this->communityManagementRecordModel = $communityManagementRecordModel;

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Given an escalationID mark all new reports as escalated.
     *
     * @param int $escalationID
     *
     * @return void
     */
    public function escalateReportsForEscalation(int $escalationID): void
    {
        $escalation = $this->selectSingle(["escalationID" => $escalationID]);

        $reportWhere = [
            "recordType" => $escalation["recordType"],
            "recordID" => $escalation["recordID"],
            "status" => ReportModel::STATUS_NEW,
        ];

        $this->database->beginTransaction();
        try {
            $reportIDs = array_column(
                $this->reportModel->select($reportWhere, [Model::OPT_SELECT => "reportID"]),
                "reportID"
            );

            $this->reportModel->update(["status" => ReportModel::STATUS_ESCALATED], $reportWhere);
            foreach ($reportIDs as $reportID) {
                $this->createSql()->insert("reportEscalationJunction", [
                    "reportID" => $reportID,
                    "escalationID" => $escalationID,
                ]);
            }

            $this->database->commitTransaction();
        } catch (\Throwable $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * Get a count of escalations matching certain filters.
     *
     * @param array $filters
     *
     * @return int
     */
    public function queryEscalationsCount(array $filters): int
    {
        $query = $this->createSql()
            ->from("escalation e")
            ->leftJoin("reportEscalationJunction rej", "e.escalationID = rej.escalationID")
            ->leftJoin("reportReasonJunction rrj", "rej.reportID = rrj.reportID")
            ->groupBy("e.escalationID");

        $query = $this->applyFiltersToEscalationsQuery($query, $filters);
        $count = $query->getPagingCount("e.escalationID");

        return $count;
    }

    /**
     * Apply common filters for {@link self::queryEscalationsCount()} and {@link self::queryEscalations()}.
     *
     * @param \Gdn_SQLDriver $query
     * @param array $filters
     * @return \Gdn_SQLDriver
     */
    private function applyFiltersToEscalationsQuery(\Gdn_SQLDriver $query, array $filters): \Gdn_SQLDriver
    {
        $visibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
            $this->session->UserID,
            "Vanilla.Posts.Moderate"
        );
        $query->where([
            "e.placeRecordType" => "category",
            "e.placeRecordID" => $visibleCategoryIDs,
        ]);

        // Some specific ones
        if ($reportReasonID = $filters["reportReasonID"] ?? null) {
            $query->where("rrj.reportReasonID", $reportReasonID);
            unset($filters["reportReasonID"]);
        }

        if ($recordUserRoleID = $filters["recordUserRoleID"] ?? null) {
            $query->leftJoin("UserRole ur", "e.recordUserID = ur.UserID")->where("ur.RoleID", $recordUserRoleID);
            unset($filters["recordUserRoleID"]);
        }

        foreach ($filters as $key => $value) {
            if (!str_contains($key, ".")) {
                $filters["e.{$key}"] = $value;
                unset($filters[$key]);
            }
        }

        $query->where($filters);

        return $query;
    }

    /**
     * Query escalations with various reports and reasons joined.
     *
     * @param array $filters
     * @param array $options Standard model options.
     *
     * @return array<array>
     */
    public function queryEscalations(array $filters, array $options): array
    {
        $query = $this->createSql()
            ->from("escalation e")
            ->select([
                "e.*",
                "COUNT(r.reportID) as countReports",
                "MAX(r.dateInserted) as dateLastReport",
                "JSON_ARRAYAGG(rrj.reportReasonID) as reportReasonIDs",
                "JSON_ARRAYAGG(r.insertUserID) as reportUserIDs",
                "JSON_ARRAYAGG(r.reportID) as reportIDs",
            ])
            ->leftJoin("reportEscalationJunction rej", "e.escalationID = rej.escalationID")
            ->leftJoin("reportReasonJunction rrj", "rej.reportID = rrj.reportID")
            ->leftJoin("report r", "rej.reportID = r.reportID")
            ->groupBy("e.escalationID");

        $query = $this->applyFiltersToEscalationsQuery($query, $filters);

        $query = $query->applyModelOptions($options);
        $rows = $query->get()->resultArray();

        // Unformat JSON aggregates
        foreach ($rows as &$row) {
            $row["reportReasonIDs"] = array_unique(array_filter(json_decode($row["reportReasonIDs"])));
            $row["reportUserIDs"] = array_unique(array_filter(json_decode($row["reportUserIDs"])));
            $row["reportIDs"] = array_unique(array_filter(json_decode($row["reportIDs"])));
        }

        $this->communityManagementRecordModel->joinLiveRecordData($rows);
        $this->reportReasonModel->expandReportReasonArrays($rows);
        $this->reportModel->expandReportUsers($rows);
        SchemaUtils::validateArray($rows, $this->escalationSchema());
        return $rows;
    }

    /**
     * Get the schema for an escalation.
     *
     * @return Schema
     */
    public function escalationSchema(): Schema
    {
        return SchemaUtils::composeSchemas(
            Schema::parse([
                "escalationID:i?",
                "name:s",
                "status:s",
                "assignedUserID:i",
                "countComments:i",
                "insertUserID:i",
                "dateInserted:dt",
                "dateUpdated:dt|n?",
                "updateUserID:i|n?",
                "reportReasonIDs:a",
                "reportReasons:a",
                "reportUserIDs:a",
                "reportUsers:a",
                "reportIDs:a",
                "countReports:i",
                "dateLastReport:dt",
            ]),
            CommunityManagementRecordModel::minimalRecordSchema()
        );
    }

    /**
     * Structure our database schema.
     *
     * @param \Gdn_DatabaseStructure $structure
     *
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure): void
    {
        $structure
            ->table("escalation")
            ->primaryKey("escalationID")
            ->column("name", "text")
            ->column("status", "varchar(50)", false, "index")
            ->column("assignedUserID", "int", self::UNASSIGNED_USER_ID, "index")
            ->column("countComments", "int", 0)
            ->column("insertUserID", "int")
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("updateUserID", "int", true)

            // About the record
            ->column("recordType", "varchar(50)")
            ->column("recordID", "int")
            ->column("recordUserID", "int")
            ->column("recordDateInserted", "datetime", false, "index")
            ->column("placeRecordType", "varchar(50)")
            ->column("placeRecordID", "int")
            ->set();

        $structure
            ->table("escalation")
            ->createIndexIfNotExists("IX_escalation_recordType_recordID", ["recordType", "recordID"])
            ->createIndexIfNotExists("IX_escalation_placeRecordType_placeRecordID", [
                "placeRecordType",
                "placeRecordID",
            ])
            ->createIndexIfNotExists("IX_escalation_assignedUserID", ["assignedUserID"]);
    }
}
