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
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Models\CommunityManagement\CommunityManagementRecordModel;
use Vanilla\Forum\Models\CommunityManagement\EscalationModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Forum\Models\CommunityManagement\ReportReasonModel;
use Vanilla\Forum\Models\VanillaEscalationAttachmentProvider;
use Vanilla\Models\FormatSchema;
use Vanilla\Models\Model;
use Vanilla\Schema\RangeExpression;
use Vanilla\Utility\ModelUtils;
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
        private ReportReasonModel $reportReasonModel,
        private \CommentModel $commentModel,
        private FormatService $formatService,
        private \UserModel $userModel,
        private ReportModel $reportModel
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
        $this->permission(["posts.moderate", "community.moderate"]);

        $in = Schema::parse([
            "escalationID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "status?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => $this->escalationsModel->getStatusIDs(),
                ],
                "style" => "form",
                "x-filter" => true,
            ],
            "recordType:s?" => [
                "x-filter" => true,
            ],
            "recordID?" => [
                "type" => "array",
                "items" => [
                    "type" => "integer",
                ],
                "style" => "form",
                "x-filter" => true,
            ],
            "placeRecordType:s?" => [
                "x-filter" => true,
            ],
            "placeRecordID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "assignedUserID?" => RangeExpression::createSchema([":i"])->setField("x-filter", true),
            "reportReasonID?" => [
                "type" => "array",
                "items" => [
                    "type" => "string",
                    "enum" => $this->reportReasonModel->selectReasonIDs(),
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
        $this->permission(["posts.moderate", "community.moderate"]);
        $escalation = $this->getEscalation($escalationID);
        return $escalation;
    }

    /**
     * @param array $query
     * @return Data
     */
    public function get_lookupAssignee(array $query)
    {
        $this->permission(["posts.moderate", "community.moderate"]);

        $in = Schema::parse([
            "name:s?" => [
                "default" => "",
            ],
            "page:i?" => [
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "default" => 30,
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(),
            ],
            "escalationID:i?",
        ]);
        $out = Schema::parse([
            ":a" => $this->getUserFragmentSchema(),
        ]);

        $query = $in->validate($query);
        [$offset, $limit] = ApiUtils::offsetLimit($query);

        $escalationID = $query["escalationID"] ?? null;
        if ($escalationID !== null) {
            // Make sure the escalation exists and we have permission to see it.
            $escalation = $this->getEscalation($escalationID);
        }

        // Now we need to get all the roleIDs that would have access to this (or any if no escalationID) escalation.
        $where = [
            new CallbackWhereExpression(function (\Gdn_SQLDriver $sql) use ($escalationID) {
                $validRoleIDs = $this->escalationsModel->selectRoleIDsCanViewEscalation($escalationID);

                // Now join userRole and filter the list.
                $sql->distinct()
                    ->leftJoin("UserRole ur", "u.UserID = ur.UserID")
                    ->where("ur.RoleID", $validRoleIDs);
            }),
        ];

        $rows = $this->userModel
            ->queryByName($query["name"], $where, [
                Model::OPT_ORDER => "-dateLastActive",
                Model::OPT_LIMIT => $limit,
                Model::OPT_OFFSET => $offset,
            ])
            ->get()
            ->resultArray();

        foreach ($rows as &$row) {
            $row = $this->userModel->normalizeRow($row);
        }

        $this->userModel->filterPrivateUserRecord($rows);
        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/users/names", $query, $in);

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * POST /api/v2/escalations
     *
     * @param array $body
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission(["posts.moderate", "community.moderate"]);
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
                "enum" => $this->escalationsModel->getStatusIDs(),
            ],
            "initialCommentBody:s?",
            "initialCommentFormat?" => new FormatSchema(),
        ])->addValidator("", SchemaUtils::onlyTogether(["initialCommentBody", "initialCommentFormat"]));

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

        // Check permissions on that particular category if the user isn't a global moderator.
        if (!$this->getSession()->checkPermission("community.moderate")) {
            $this->permission("posts.moderate", $record["CategoryID"]);
        }

        if (isset($body["reportID"])) {
            $schema = SchemaUtils::composeSchemas($baseSchema, Schema::parse(["reportID:i"]));
            $body = $schema->validate($body);
            $report = $this->reportsApiController->get($body["reportID"]);
        } else {
            $schema = SchemaUtils::composeSchemas($baseSchema, $this->reportsApiController->postSchema());
            $body = $schema->validate($body);
            $report = $this->reportsApiController->post($body);
        }

        if (isset($body["initialCommentFormat"]) && isset($body["initialCommentBody"])) {
            $this->formatService->filter($body["initialCommentBody"], $body["initialCommentFormat"]);
        }

        $this->reportModel->dispatchSpamEventFromReport($report);

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

        $initialCommentBody = $body["initialCommentBody"] ?? null;
        $initialCommentFormat = $body["initialCommentFormat"] ?? null;
        if ($initialCommentBody !== null && $initialCommentFormat !== null) {
            $commentID = $this->commentModel->save([
                "Body" => $initialCommentBody,
                "Format" => $initialCommentFormat,
                "parentRecordType" => "escalation",
                "parentRecordID" => $escalationID,
            ]);
            ModelUtils::validationResultToValidationException($this->commentModel);
        }

        $result = $this->getEscalation($escalationID);
        $attachmentProvider = \Gdn::getContainer()->get(VanillaEscalationAttachmentProvider::class);
        $attachmentProvider->createAttachmentFromEscalation($result);

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
        $this->permission(["posts.moderate", "community.moderate"]);

        $in = Schema::parse([
            "recordIsLive:b?",
            "name:s?",
            "assignedUserID:i|n?",
            "status:s?" => [
                "enum" => $this->escalationsModel->getStatusIDs(),
            ],
        ]);

        $body = $in->validate($body);

        // This does deeper permission checks.
        $existingEscalation = $this->getEscalation($escalationID);

        $escalationModifications = $body;
        unset($escalationModifications["recordIsLive"]);

        $this->escalationsModel->update($escalationModifications, [
            "escalationID" => $escalationID,
        ]);

        if (isset($body["recordIsLive"])) {
            $shouldRemoveRecord = $body["recordIsLive"] === false && $existingEscalation["recordIsLive"] === true;
            $shouldRestoreRecord = $body["recordIsLive"] === true && $existingEscalation["recordIsLive"] === false;
            if ($shouldRestoreRecord) {
                // We have a record ID, so we can just restore the record from the log table.
                $this->communityManagementRecordModel->restoreRecord(
                    $existingEscalation["recordType"],
                    $existingEscalation["recordID"]
                );
            } elseif ($shouldRemoveRecord) {
                // Remove the record and send it to the log table.
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
        $result = $this->escalationsModel->getEscalation($escalationID);

        if ($result === null) {
            throw new NotFoundException("escalation", ["escalationID" => $escalationID]);
        }

        if (!$this->getSession()->checkPermission("community.moderate")) {
            $this->permission("posts.moderate", $result["placeRecordID"]);
        }
        return $result;
    }
}
