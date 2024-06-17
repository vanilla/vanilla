<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\PermissionException;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Models\Model;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\SchemaUtils;

/**
 * /api/v2/escalations
 */
class EscalationsApiController extends \AbstractApiController
{
    /**
     * Constructor.
     */
    public function __construct(
        private EscalationModel $escalationsModel,
        private CommunityManagementRecordModel $communityManagementRecordModel,
        private ReportsApiController $reportsApiController,
        private ReportReasonModel $reportReasonModel
    ) {
    }

    /**
     * GET /api/v2/escalations
     *
     * @param array $query
     *
     * @return Data
     */
    public function index(array $query = []): Data
    {
        $this->permission("posts.moderate");

        $in = Schema::parse([
            "escalationID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "status?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => EscalationModel::STATUSES,
                ],
                "style" => "form",
                "x-filter" => true,
            ],
            "recordType:s?" => [
                "x-filter" => true,
            ],
            "recordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "placeRecordType:s?" => [
                "x-filter" => true,
            ],
            "placeRecordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "assignedUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "reportReasonID?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->getAvailableReasonIDs(),
                ],
                "style" => "form",
                "x-filter" => true,
            ],
            "recordUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "recordUserRoleID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
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

        $filters = ApiUtils::queryToFilters($in, $query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $escalations = $this->escalationsModel->queryEscalations($filters, [
            Model::OPT_ORDER => $query["sort"],
            Model::OPT_LIMIT => $limit,
            Model::OPT_OFFSET => $offset,
        ]);

        $countEscalations = $this->escalationsModel->queryEscalationsCount($filters);

        $paging = ApiUtils::numberedPagerInfo($countEscalations, "/api/v2/escalations", $query, $in);

        return new Data($escalations, ["paging" => $paging]);
    }

    /**
     * GET /api/v2/escalations/:escalationID
     *
     * @param int $escalationID
     * @return array
     */
    public function get(int $escalationID): array
    {
        $this->permission("posts.moderate");
        $escalation = $this->getEscalation($escalationID);
        return $escalation;
    }

    /**
     * POST /api/v2/escalations
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("posts.moderate");
        $baseSchema = Schema::parse([
            "recordType:s",
            "recordID:i",
            "assignedUserID:i?" => [
                "default" => EscalationModel::UNASSIGNED_USER_ID,
            ],
            "name:s",
            "recordIsLive:b" => [
                "default" => true,
            ],
            "status:s?" => [
                "enum" => EscalationModel::STATUSES,
            ],
        ]);

        $initialBody = $baseSchema->validate($body);
        // Validate deeper permissions
        $record = $this->communityManagementRecordModel->getRecord(
            $initialBody["recordType"],
            $initialBody["recordID"]
        );
        if (!$record) {
            throw new NotFoundException($initialBody["recordType"], [
                "recordType" => $initialBody["recordType"],
                "recordID" => $initialBody["recordID"],
            ]);
        }

        // Check permissions on that particular category.
        $this->permission("posts.moderate", $record["CategoryID"]);

        if (isset($body["reportID"])) {
            $schema = SchemaUtils::composeSchemas($baseSchema, Schema::parse(["reportID:i"]));
            $body = $schema->validate($body);
            $report = $this->reportsApiController->get($body["reportID"]);
        } else {
            $schema = SchemaUtils::composeSchemas($baseSchema, $this->reportsApiController->postSchema());
            $body = $schema->validate($body);
            $report = $this->reportsApiController->post($body);
        }

        $escalationID = $this->escalationsModel->insert([
            "name" => $body["name"],
            "status" => $body["status"] ?? EscalationModel::STATUS_OPEN,
            "assignedUserID" => $body["assignedUserID"] ?? null,
            "countComments" => 0,
            "recordType" => $body["recordType"],
            "recordID" => $body["recordID"],
            "recordUserID" => $record["InsertUserID"],
            "recordDateInserted" => $record["DateInserted"],
            "placeRecordType" => "category",
            "placeRecordID" => $record["CategoryID"],
        ]);
        $this->escalationsModel->escalateReportsForEscalation($escalationID);

        if (!$body["recordIsLive"]) {
            $this->communityManagementRecordModel->removeRecord($body["recordType"], $body["recordID"]);
        }

        $result = $this->getEscalation($escalationID);
        return $result;
    }

    /**
     * PATCH /api/v2/escalations/:escalationID
     *
     * @param int $escalationID
     * @param array $body
     * @return array
     */
    public function patch(int $escalationID, array $body)
    {
        $this->permission("posts.moderate");

        $in = Schema::parse([
            "recordIsLive:b?",
            "name:s?",
            "assignedUserID:i|n?",
            "status:s?" => [
                "enum" => EscalationModel::STATUSES,
            ],
        ]);

        $body = $in->validate($body);

        $existingEscalation = $this->getEscalation($escalationID);
        $this->permission("posts.moderate", $existingEscalation["placeRecordID"]);

        $escalationModifications = $body;
        unset($escalationModifications["recordIsLive"]);

        $this->escalationsModel->update($escalationModifications, [
            "escalationID" => $escalationID,
        ]);

        if (isset($body["recordIsLive"])) {
            $shouldRemoveRecord = $body["recordIsLive"] === false && $existingEscalation["recordIsLive"] === true;
            $shouldRestoreRecord = $body["recordIsLive"] === true && $existingEscalation["recordIsLive"] === false;
            if ($shouldRestoreRecord) {
                $this->communityManagementRecordModel->restoreRecord(
                    $existingEscalation["recordType"],
                    $existingEscalation["recordID"]
                );
            } elseif ($shouldRemoveRecord) {
                $this->communityManagementRecordModel->removeRecord(
                    $existingEscalation["recordType"],
                    $existingEscalation["recordID"]
                );
            }
        }

        $result = $this->getEscalation($escalationID);
        return $result;
    }

    /**
     * Utility to fetch a single escalation or throw a 404.
     *
     * @param int $escalationID
     *
     * @return array
     *
     * @throws NotFoundException
     * @throws PermissionException
     */
    private function getEscalation(int $escalationID): array
    {
        $rows = $this->escalationsModel->queryEscalations(
            [
                "escalationID" => $escalationID,
            ],
            [Model::OPT_LIMIT => 1]
        );
        $result = $rows[0] ?? null;
        if ($result === null) {
            throw new NotFoundException("escalation", ["escalationID" => $escalationID]);
        }

        $this->permission("posts.moderate", $result["placeRecordID"]);

        return $result;
    }
}
