<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Models\CommunityManagement;

use Garden\Schema\Schema;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Dashboard\Models\AttachmentService;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\SetLiterals\Increment;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Permissions;
use Vanilla\Utility\ArrayUtils;
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
    public const STATUS_DONE = "done";

    /**
     * Constructor
     */
    public function __construct(
        private \Gdn_Session $session,
        private \CategoryModel $categoryModel,
        private ReportModel $reportModel,
        private ReportReasonModel $reportReasonModel,
        private CommunityManagementRecordModel $communityManagementRecordModel,
        private \AttachmentModel $attachmentModel
    ) {
        parent::__construct("escalation");

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted"], ["dateUpdated"]));
        $userProcessor = new CurrentUserFieldProcessor($session);
        $userProcessor->camelCase();
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * @return AttachmentService
     */
    private function attachmentService(): AttachmentService
    {
        return \Gdn::getContainer()->get(AttachmentService::class);
    }

    /**
     * Check if we have view permission.
     *
     * @param int $escalationID
     * @param bool $throw
     *
     * @return bool
     *
     * @throws NotFoundException
     * @throws PermissionException
     */
    public function hasViewPermission(int $escalationID, bool $throw = true): bool
    {
        try {
            $escalation = $this->selectSingle(["escalationID" => $escalationID]);
        } catch (NoResultsException $resultsException) {
            throw new NotFoundException(
                "Escalation",
                [
                    "escalationID" => $escalationID,
                ],
                $resultsException
            );
        }

        if ($this->session->getPermissions()->hasAny(["community.moderate", "site.manage"])) {
            return true;
        }

        if ($escalation["parentRecordType"] !== "category") {
            throw new ServerException("Only category escalations are supported.");
        }

        $categoryID = $escalation["parentRecordID"];

        $isCategoryMod = $this->session
            ->getPermissions()
            ->has(
                "posts.moderate",
                $categoryID,
                Permissions::CHECK_MODE_RESOURCE_IF_JUNCTION,
                \CategoryModel::PERM_JUNCTION_TABLE
            );
        if (!$isCategoryMod) {
            if ($throw) {
                throw new PermissionException(
                    ["posts.moderate", "community.moderate", "site.manage"],
                    [
                        "escalationID" => $escalationID,
                        "categoryID" => $categoryID,
                    ]
                );
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * @param int $escalationID
     * @param array $reportIDs
     * @return void
     */
    public function linkReportsToEscalation(int $escalationID, array $reportIDs): void
    {
        foreach ($reportIDs as $reportID) {
            $this->createSql()->insert("reportEscalationJunction", [
                "reportID" => $reportID,
                "escalationID" => $escalationID,
            ]);
        }
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
            $this->linkReportsToEscalation($escalationID, $reportIDs);

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
     * @param int $limit
     *
     * @return int
     */
    public function queryEscalationsCount(array $filters, int $limit = 10000): int
    {
        $query = $this->createSql()
            ->from("escalation e")
            ->leftJoin("reportEscalationJunction rej", "e.escalationID = rej.escalationID")
            ->leftJoin("reportReasonJunction rrj", "rej.reportID = rrj.reportID")
            ->groupBy("e.escalationID");

        $query = $this->applyFiltersToEscalationsQuery($query, $filters);
        $count = $query->getPagingCount("e.escalationID", $limit);

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
        if (!$this->session->checkPermission("community.moderate")) {
            // If the user isn't a global moderator apply permission filters.
            $visibleCategoryIDs = $this->categoryModel->getCategoryIDsWithPermissionForUser(
                $this->session->UserID,
                "Vanilla.Posts.Moderate"
            );
            $query->where([
                "e.placeRecordType" => "category",
                "e.placeRecordID" => $visibleCategoryIDs,
            ]);
        }

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
    public function queryEscalations(array $filters, array $options = []): array
    {
        $query = $this->createSql()
            ->from("escalation e")
            ->select([
                "e.*",
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
            $row["countReports"] = count($row["reportIDs"]);
        }

        $this->communityManagementRecordModel->joinLiveRecordData($rows);
        $this->reportReasonModel->expandReportReasonArrays($rows);
        $this->reportModel->expandReportUsers($rows);
        $this->normalizeRows($rows);
        $this->attachmentModel->joinAttachments($rows);

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
                "dateLastReport:dt?",
                "url:s",
                "attachments:a?",
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

    /**
     * @param array $rowsOrRow
     */
    public function normalizeRows(array &$rowsOrRow): void
    {
        if (ArrayUtils::isAssociative($rowsOrRow)) {
            $rows = [&$rowsOrRow];
        } else {
            $rows = &$rowsOrRow;
        }

        foreach ($rows as &$row) {
            $row["url"] = self::escalationUrl($row["escalationID"]);
        }
    }

    /**
     * @param int $escalationID
     * @return string
     */
    public static function escalationUrl(int $escalationID): string
    {
        return url("/dashboard/content/escalations/{$escalationID}", true);
    }

    /**
     * Get all available escalation statuses.
     *
     * @return array<string, string> A mapping of statusID => status label code.
     */
    public function getStatuses(): array
    {
        $statuses = [
            self::STATUS_OPEN => "Open",
            self::STATUS_IN_PROGRESS => "In Progress",
            self::STATUS_ON_HOLD => "On Hold",
            self::STATUS_DONE => "Done",
        ];

        $attachmentProviders = $this->attachmentService()->getAllProviders();
        foreach ($attachmentProviders as $attachmentProvider) {
            if ($attachmentProvider instanceof EscalationStatusProviderInterface) {
                $statuses[$attachmentProvider->getStatusID()] = $attachmentProvider->getStatusLabelCode();
            }
        }

        return $statuses;
    }

    /**
     * @return string[]
     */
    public function getStatusIDs(): array
    {
        return array_keys($this->getStatuses());
    }

    /**
     * @param array $comment
     * @return void
     * @throws \Exception
     */
    public function handleCommentInsert(array $comment): void
    {
        if ($comment["parentRecordType"] !== "escalation") {
            return;
        }

        $escalationID = $comment["parentRecordID"];
        $this->incrementCommentCount($escalationID, value: 1);
    }

    /**
     * @param array $comment
     * @return void
     */
    public function handleCommentDelete(array $comment): void
    {
        if ($comment["parentRecordType"] !== "escalation") {
            return;
        }

        $escalationID = $comment["parentRecordID"];
        $this->incrementCommentCount($escalationID, value: -1);
    }

    /**
     * @param int $escalationID
     * @param int $value
     * @return void
     */
    private function incrementCommentCount(int $escalationID, int $value = 1): void
    {
        $this->createSql()
            ->update("escalation")
            ->set("countComments", new Increment($value))
            ->where("escalationID", $escalationID)
            ->put();
    }
}
