<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\HttpException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Gdn;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Addon\ReportPostTriggerHandler;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementNotificationGenerator;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Forum\Models\CommunityManagement\TriageModel;
use Vanilla\Models\FormatSchema;
use Vanilla\Models\Model;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\SchemaUtils;

/**
 * /api/v2/reports
 */
class ReportsApiController extends \AbstractApiController
{
    /**
     * DI.
     */
    public function __construct(
        private ReportModel $reportModel,
        private ReportReasonModel $reportReasonModel,
        private \UserModel $userModel,
        private FormatService $formatService,
        private CommunityManagementRecordModel $communityManagementRecordModel
    ) {
    }

    /**
     * POST /api/v2/reports/:reportID/dismiss
     *
     * @param int $reportID
     *
     * @return array
     */
    public function patch_dismiss(int $reportID): array
    {
        $this->permission(["posts.moderate", "community.moderate"]);
        $report = $this->getReport($reportID);
        $this->reportModel->update(
            [
                "status" => ReportModel::STATUS_DISMISSED,
            ],
            [
                "reportID" => $reportID,
            ]
        );

        return $this->getReport($reportID);
    }

    /**
     * PATCH /api/v2/reports/:reportID/approve-record
     *
     * @param int $reportID
     * @param array $body
     * @return array
     */
    public function patch_approveRecord(int $reportID, array $body = []): array
    {
        // Permission and existance check.
        $this->permission(["posts.moderate", "community.moderate"]);
        $report = $this->getReport($reportID);

        $in = Schema::parse([
            "verifyRecordUser:b" => [
                "default" => false,
            ],
        ]);
        $body = $in->validate($body);

        $recordType = $report["recordType"];
        $recordID = $report["recordID"] ?? null;
        $isPending = $report["isPending"];

        if ($recordID === null && !$isPending) {
            // This should never happen.
            throw new ServerException(
                "Report does not have a record ID, and also does not have a premoderated record."
            );
        }

        if (!$report["recordIsLive"] || $isPending) {
            if ($isPending) {
                // We are using the premoderation model to create the record for the first time.
                $this->reportModel->saveRecordFromPremoderation($reportID);
            } else {
                $this->communityManagementRecordModel->restoreRecord($recordType, $recordID);
            }
        }

        // Update our status
        $this->reportModel->update(
            set: [
                "status" => ReportModel::STATUS_DISMISSED,
            ],
            where: [
                "reportID" => $reportID,
            ]
        );

        if ($body["verifyRecordUser"]) {
            // This is a special case where we need to verify the user.
            $this->userModel->setField($report["recordUserID"], "Verified", true);
        }

        $report = $this->getReport($reportID);

        return $report;
    }

    /**
     * PATCH /api/v2/reports/:escalationID/reject-record
     *
     * @param int $reportID
     * @return array
     */
    public function patch_rejectRecord(int $reportID): array
    {
        // Permission and existance check.
        $this->permission(["posts.moderate", "community.moderate"]);
        $report = $this->getReport($reportID);

        if ($report["recordIsLive"]) {
            $this->communityManagementRecordModel->removeRecord($report["recordType"], $report["recordID"]);
        }
        $this->reportModel->update(
            set: [
                "status" => ReportModel::STATUS_REJECTED,
            ],
            where: [
                "reportID" => $reportID,
            ]
        );
        $report = $this->getReport($reportID);
        return $report;
    }

    /**
     * @return Schema
     */
    public function postSchema($includeSystemReason = false): Schema
    {
        return Schema::parse([
            "recordType:s" => [
                "enum" => ["discussion", "comment"],
            ],
            "recordID:i",
            "noteBody:s?",
            "noteFormat:s?" => new FormatSchema(),
            "reportReasonIDs:a" => [
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->getPermissionAvailableReasonIDs($includeSystemReason),
                ],
            ],
        ]);
    }

    /**
     * POST /api/v2/reports
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("flag.add");

        $availableReasonIDs = $this->reportReasonModel->getPermissionAvailableReasonIDs();
        if (empty($availableReasonIDs)) {
            throw new ClientException("No report reasons available.", 403);
        }

        $includeSystemReason = $body["automation"] ?? false;
        $in = $this->postSchema((bool) $includeSystemReason);

        $body = $in->validate($body);
        $record = $this->communityManagementRecordModel->getRecord($body["recordType"], $body["recordID"]);
        if (!$record) {
            throw new NotFoundException($body["recordType"], [
                "recordType" => $body["recordType"],
                "recordID" => $body["recordID"],
            ]);
        }

        if ($record["CategoryID"] === \CategoryModel::ROOT_ID) {
            throw new ClientException("Cannot report a record in the root category.");
        }
        if (isset($body["noteBody"]) && isset($body["noteFormat"])) {
            $this->formatService->filter($body["noteBody"], $body["noteFormat"]);
        }

        $reportID = $this->reportModel->insert([
            "recordType" => $body["recordType"],
            "recordID" => $body["recordID"],
            "noteBody" => $body["noteBody"] ?? null,
            "noteFormat" => $body["noteFormat"] ?? null,
            "status" => ReportModel::STATUS_NEW,
            "placeRecordType" => "category",
            "placeRecordID" => $record["CategoryID"],
            "recordUserID" => $record["InsertUserID"],
            "recordName" => $record["Name"],
            "recordBody" => $record["Body"],
            "recordFormat" => $record["Format"],
            "recordDateInserted" => $record["DateInserted"],
            "reportReasonIDs" => $body["reportReasonIDs"] ?? [],
        ]);

        if (Gdn::config("Feature.AutomationRules.Enabled")) {
            $reportPostTriggerHandler = \Gdn::getContainer()->get(ReportPostTriggerHandler::class);
            $reportPostTriggerHandler->handleUserEvent($reportID);
        }
        return $this->getReport($reportID, checkPermissions: false);
    }

    /**
     * PATCH /api/v2/reports/:reportID
     *
     * @param int $reportID
     * @param array $body
     * @return array
     */
    public function patch(int $reportID, array $body): array
    {
        $this->permission(["posts.moderate", "community.moderate"]);
        $in = Schema::parse([
            "status:s" => [
                "enum" => ReportModel::STATUSES,
            ],
        ]);

        $body = $in->validate($body);
        // Ensure the report exists and the user has permissions.
        $report = $this->getReport($reportID);

        $this->reportModel->update(
            [
                "status" => $body["status"],
            ],
            [
                "reportID" => $reportID,
            ]
        );

        return $this->getReport($reportID);
    }

    /**
     * GET /api/v2/reports
     *
     * @param array $query
     * @return Data
     */
    public function index(array $query = []): Data
    {
        $this->permission(["posts.moderate", "community.moderate"]);
        $in = Schema::parse([
            "reportID?" => RangeExpression::createSchema([":i"])->setField("x-filter", ["field" => "r.reportID"]),
            "status?" => [
                "type" => "array",
                "style" => "form",
                "items" => [
                    "type" => "string",
                    "enum" => ReportModel::STATUSES,
                ],
                "x-filter" => ["field" => "r.status"],
            ],
            "recordType:s?" => [
                "x-filter" => ["field" => "r.recordType"],
            ],
            "escalationID?" => RangeExpression::createSchema([":i"])->setField("x-filter", [
                "field" => "rej.escalationID",
            ]),
            "recordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", ["field" => "r.recordID"]),
            "placeRecordType:s?" => [
                "x-filter" => ["field" => "r.placeRecordType"],
            ],
            "placeRecordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", [
                "field" => "r.placeRecordID",
            ]),
            "recordUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", [
                "field" => "r.recordUserID",
            ]),
            "insertUserRoleID?" => RangeExpression::createSchema([":i"])->setField("x-filter", [
                "field" => "ur.RoleID",
            ]),
            "insertUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", [
                "field" => "r.insertUserID",
            ]),
            "reportReasonID?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->selectReasonIDs(),
                ],
                "style" => "form",
                "x-filter" => ["field" => "rrj.reportReasonID"],
            ],
            "sort:s?" => [
                "enum" => ApiUtils::sortEnum("dateInserted", "recordDateInserted"),
                "default" => "-dateInserted",
            ],
            "page:i" => [
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i" => [
                "default" => 100,
                "minimum" => 1,
                "maximum" => 500,
            ],
        ])
            ->addFilter("", SchemaUtils::fieldRequirement("recordID", "recordType"))
            ->addFilter("", SchemaUtils::onlyTogether(["placeRecordType", "placeRecordID"]));
        $query = $in->validate($query);
        $where = ApiUtils::queryToFilters($in, $query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $results = $this->reportModel->selectVisibleReports($where, [
            Model::OPT_ORDER => $query["sort"],
            Model::OPT_OFFSET => $offset,
            Model::OPT_LIMIT => $limit,
        ]);
        $countReports = $this->reportModel->countVisibleReports($where);

        $paging = ApiUtils::numberedPagerInfo($countReports, "/api/v2/reports", $query, $in);

        return new Data($results, ["paging" => $paging]);
    }

    /**
     * GET /api/v2/reports/:reportID
     *
     * @param int $reportID
     * @return array
     */
    public function get(int $reportID): array
    {
        $this->permission(["posts.moderate", "community.moderate"]);
        $report = $this->getReport($reportID);

        $categoryID = $report["placeRecordID"];
        if (!$this->getSession()->checkPermission("community.moderate")) {
            $this->permission("posts.moderate", $categoryID);
        }
        return $report;
    }

    /**
     * Fetch a single report by ID.
     *
     * @param int $reportID
     * @param bool $checkPermissions
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws PermissionException
     */
    private function getReport(int $reportID, bool $checkPermissions = true): array
    {
        $report = $this->reportModel->getReport($reportID);
        if ($checkPermissions) {
            $categoryID = $report["placeRecordID"];
            if (!$this->getPermissions()->has("community.moderate")) {
                // If user isn't a global moderator check the global permission.
                $this->permission("posts.moderate", $categoryID);
            }
        }

        return $report;
    }

    /**
     * GET /api/v2/reports/automation
     *
     * @param array $query
     * @return Data
     * @throws PermissionException
     * @throws ValidationException
     * @throws HttpException
     */
    public function get_automation(array $query = []): Data
    {
        $this->permission("posts.moderate");
        $in = Schema::parse([
            "countReports" => [
                "type" => "integer",
                "x-filter" => ["field" => "countReports"],
            ],
            "reportReasonID?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->selectReasonIDs(),
                ],
                "style" => "form",
                "x-filter" => ["field" => "rrj.reportReasonID"],
            ],
            "placeRecordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "includeSubcategories?" => [
                "type" => "boolean",
            ],
            "page:i?" => [
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "default" => 30,
                "minimum" => 1,
                "maximum" => 100,
            ],
        ])->addFilter("", SchemaUtils::onlyTogether(["placeRecordType", "placeRecordID"]));

        $query = $in->validate($query);
        $where = ApiUtils::queryToFilters($in, $query);
        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);
        // Get list of reportIDs that match the query. From group and having clauses.
        $where = $this->reportModel->createCountedReportsWhere($where);
        $results = $this->reportModel->selectVisibleReports($where, [
            Model::OPT_OFFSET => $offset,
            Model::OPT_LIMIT => $limit,
        ]);
        $countReports = $this->reportModel->countVisibleReports($where);

        $paging = ApiUtils::numberedPagerInfo($countReports, "/api/v2/reports", $query, $in);

        return new Data($results, ["paging" => $paging]);
    }
}
