<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Forum\Models\CommunityManagement\TriageModel;
use Vanilla\Models\Model;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\ModelUtils;
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
        private CommunityManagementRecordModel $communityManagementRecordModel,
        private TriageModel $triageModel,
        private \AttachmentModel $attachmentModel
    ) {
    }

    /**
     * POST /api/v2/reports/dismiss
     *
     * @param array $body
     * @return Data
     */
    public function post_dismiss(array $body): Data
    {
        $in = Schema::parse([
            "reportID:i",
            "verifyRecordUser:b" => [
                "default" => false,
            ],
        ]);
        $body = $in->validate($body);

        $report = $this->reportModel->selectSingle(["reportID" => $body["reportID"]]);
        $this->reportModel->update(
            [
                "status" => ReportModel::STATUS_DISMISSED,
            ],
            [
                "reportID" => $body["reportID"],
            ]
        );

        if ($body["verifyRecordUser"]) {
            $this->userModel->setField($report["recordUserID"], "Verified", true);
        }

        return new Data("", [], ["status" => 204]);
    }

    /**
     * @return Schema
     */
    public function postSchema(): Schema
    {
        return Schema::parse([
            "recordType:s" => [
                "enum" => ["discussion", "comment"],
            ],
            "recordID:i",
            "noteBody:s",
            "noteFormat:s" => new \Vanilla\Models\FormatSchema(),
            "reportReasonIDs:a" => [
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->getPermissionAvailableReasonIDs(),
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

        $in = $this->postSchema();

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

        $this->formatService->filter($body["noteBody"], $body["noteFormat"]);

        $reportID = $this->reportModel->insert([
            "recordType" => $body["recordType"],
            "recordID" => $body["recordID"],
            "noteBody" => $body["noteBody"],
            "noteFormat" => $body["noteFormat"],
            "status" => ReportModel::STATUS_NEW,
            "placeRecordType" => "category",
            "placeRecordID" => $record["CategoryID"],
            "recordUserID" => $record["InsertUserID"],
            "recordName" => $record["Name"],
            "recordBody" => $record["Body"],
            "recordFormat" => $record["Format"],
        ]);

        $reasonIDs = $body["reportReasonIDs"];
        $this->reportModel->putReasonsForReport($reportID, $reasonIDs);

        return $this->getReport($reportID);
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
        $this->permission("posts.moderate");
        $in = Schema::parse([
            "status:s" => [
                "enum" => ReportModel::STATUSES,
            ],
        ]);

        $body = $in->validate($body);
        // Ensure the report exists and the user has permissions.
        $report = $this->getReport($reportID);
        $categoryID = $report["placeRecordID"];
        $this->permission("posts.moderate", $categoryID);
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
        $this->permission("posts.moderate");
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
            "recordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", ["fieldr =>.recordID"]),
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
     * GET /api/v2/reports/triage
     *
     * @param array $query
     * @return Data
     */
    public function get_triage(array $query = []): Data
    {
        $this->permission("posts.moderate");
        $in = Schema::parse([
            "recordInternalStatusID?" => [
                "style" => "form",
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "x-filter" => true,
            ],
            "placeRecordType:s?" => [
                "x-filter" => true,
            ],
            "placeRecordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "recordUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "recordUserRoleID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "sort:s?" => ApiUtils::sortEnum("recordDateInserted"),
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
        $results = $this->triageModel->queryTriaged($where, [
            Model::OPT_ORDER => $query["sort"] ?? "-recordDateInserted",
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);
        $count = $this->triageModel->countTriaged($where);

        $paging = ApiUtils::numberedPagerInfo($count, "/api/v2/reports/triage", $query, $in);

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
        $this->permission("posts.moderate");
        $report = $this->getReport($reportID);

        $categoryID = $report["placeRecordID"];
        $this->permission("posts.moderate", $categoryID);
        return $report;
    }

    /**
     * Fetch a single report by ID.
     *
     * @param int $reportID
     * @return array
     *
     * @throws NotFoundException
     * @throws PermissionException
     */
    private function getReport(int $reportID): array
    {
        try {
            $report = $this->reportModel->selectSingle(["reportID" => $reportID]);
        } catch (NoResultsException $resultsException) {
            throw new NotFoundException("report", ["reportID" => $reportID], $resultsException);
        }

        $report = $this->reportModel->normalizeRows([$report])[0] ?? null;

        return $report;
    }
}
