<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\CurrentTimeStamp;
use Vanilla\DateFilterSchema;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Http\InternalClient;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\Model;

/**
 * API Controller for the `/drafts` resource.
 */
class DraftsApiController extends \AbstractApiController
{
    const SCHEDULE_STATUS = "scheduled";

    /**
     * DraftsApiController constructor.
     *
     */
    public function __construct(private ContentDraftModel $draftModel, private \DraftModel $legacyDraftModel)
    {
    }

    /**
     * Delete a draft.
     *
     * @param int $id The unique ID of the draft.
     */
    public function delete(int $id)
    {
        $this->permission("session.valid");
        $this->draftByID($id);
        if (ContentDraftModel::enabled()) {
            $this->draftModel->delete(["draftID" => $id]);
        } else {
            $this->legacyDraftModel->deleteID($id);
        }
    }

    /**
     * Get a draft.
     *
     * @param int $id The unique ID of the draft.
     * @return array
     */
    public function get(int $id): array
    {
        $this->permission("session.valid");
        // Already validated.
        $draft = $this->draftByID($id);
        return $draft;
    }

    /**
     * Get a draft for editing.
     *
     * @param int $id The unique ID of the draft.
     * @return array
     */
    public function get_edit($id)
    {
        $this->permission("session.valid");

        $schemaFields = ["draftID", "parentRecordID?", "attributes"];

        if (ContentDraftModel::enabled()) {
            $schemaFields = array_merge($schemaFields, ["recordID?", "recordType?", "parentRecordType?"]);
        }

        if (ContentDraftModel::draftSchedulingEnabled()) {
            $schemaFields = array_merge($schemaFields, ["draftStatus", "dateScheduled"]);
        }

        $out = Schema::parse($schemaFields)->add($this->fullSchema());

        $draft = $this->draftByID($id);

        $result = $out->validate($draft);
        return $result;
    }

    /**
     * List drafts created by the current user.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query): Data
    {
        $this->permission("session.valid");
        $draftSchedulingEnabled = ContentDraftModel::draftSchedulingEnabled();
        $legacy = false;
        $in = Schema::parse([
            "recordType:s?" => [
                "x-filter" => true,
            ],
            "parentRecordType:s?" => [
                "x-filter" => true,
            ],
            "parentRecordID:i?" => [
                "x-filter" => true,
            ],
            "page:i?" => [
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "default" => 30,
                "minimum" => 1,
                "maximum" => 500,
            ],
        ]);
        if ($draftSchedulingEnabled) {
            $in->add(
                $this->schema([
                    "draftStatus:i?" => [
                        "default" => 0,
                        "enum" => [0, 1, 2],
                        "x-filter" => true,
                    ],
                    "dateUpdated:dt?" => new DateFilterSchema([
                        "description" => "Filter drafts by date updated",
                        "x-filter" => [
                            "field" => "dateUpdated",
                            "processor" => [DateFilterSchema::class, "dateFilterField"],
                        ],
                    ]),
                    "dateScheduled:dt?" => new DateFilterSchema([
                        "description" => "Filter drafts by its scheduled date.",
                        "x-filter" => [
                            "field" => "dateScheduled",
                            "processor" => [DateFilterSchema::class, "dateFilterField"],
                        ],
                    ]),
                    "sort:s?" => [
                        "default" => "-dateUpdated",
                        "enum" => ApiUtils::sortEnum("dateInserted", "dateUpdated", "dateScheduled"),
                    ],
                    "expand:b?" => "Expand associated records.",
                ]),
                true
            );
            $in = $this->addDraftStatusFilter($in, "in");
        }
        $query = $in->validate($query);

        if ($draftSchedulingEnabled) {
            $out = Schema::parse([":a" => $this->contentDraftSchema()]);
        } else {
            $out = Schema::parse([":a" => $this->fullSchema()]);
        }

        [$offset, $limit] = ApiUtils::offsetLimit($query);
        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection(
            $query["sort"] ?? "dateUpdated"
        );

        if (!ContentDraftModel::enabled()) {
            $legacy = true;
            $where = ["InsertUserID" => $this->getSession()->UserID];
            if (array_key_exists("recordType", $query)) {
                switch ($query["recordType"]) {
                    case "comment":
                        if ($query["parentRecordID"] !== null) {
                            $where["DiscussionID"] = $query["parentRecordID"];
                        } else {
                            $where["DiscussionID >"] = 0;
                        }
                        break;
                    case "discussion":
                        if ($query["parentRecordID"] !== null) {
                            $where["CategoryID"] = $query["parentRecordID"];
                        }
                        $where["DiscussionID"] = null;
                        break;
                }
            }

            $rows = $this->legacyDraftModel->getWhere($where, "DateUpdated", "desc", $limit, $offset)->resultArray();
            $count = $this->legacyDraftModel->getCount($where);
        } else {
            $where = ["insertUserID" => $this->getSession()->UserID];
            $where += ApiUtils::queryToFilters($in, $query);
            $rows = $this->draftModel->getDrafts($where, $limit, $offset, $orderField, $orderDirection);
            $count = $this->draftModel->selectPagingCount($where, 1000);
        }
        foreach ($rows as &$row) {
            if ($legacy) {
                $row = $this->draftModel->normalizeLegacyDraft($row);
            } else {
                $row = $this->getEventManager()->fireFilter("normalizeDraft", $row, $query["expand"] ?? false);
            }
        }
        $result = $out->validate($rows);
        $paging = ApiUtils::numberedPagerInfo($count, "/api/v2/drafts", $query, $in);

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Update a draft.
     *
     * @param int $id The unique ID of the draft.
     * @param array $body The request body.
     * @return array
     */
    public function patch(int $id, array $body): array
    {
        $this->permission("session.valid");

        $updatedRow = [];
        $body["draftID"] = $id;
        $updatedRow = $this->getEventManager()->fireFilter("beforeDraftProcess", $updatedRow, $body, "patch");
        if (!empty($updatedRow)) {
            return $updatedRow;
        }

        // Ensure it exists and we have permission to edit it.
        $row = $this->draftByID($id);

        $addSchedule = $this->checkCanAddScheduledDraft($body);

        if ($addSchedule) {
            $this->checkSchedulePermission();
            $in = $this->draftSchedulePostSchema($id);
        } else {
            $in = $this->draftPostSchema();
        }

        $body = $in->validate($body, true);

        if ($addSchedule) {
            $createPlaceHolderRecord = empty($row["recordID"]);
            // we are converting a normal draft to a scheduled draft
            // we need to create a placeholder record for the scheduled draft

            if (isset($row["recordID"]) && empty($body["recordID"])) {
                $body["recordID"] = $row["recordID"];
            }
            if ($body["recordType"] === "discussion") {
                $this->addUpdateScheduledDraftDiscussionRecord($body);
                if ($createPlaceHolderRecord) {
                    $this->deleteDiscussionRecord($body["recordID"]);
                }
            } else {
                $body = $this->getEventManager()->fireFilter("processScheduleDraft", $body);
            }
        } else {
            // this might be a scheduled draft that is being converted to a normal draft
            // clear all the scheduled draft fields
            if (ContentDraftModel::draftSchedulingEnabled()) {
                $body["dateScheduled"] = null;
                $body["draftStatus"] = ContentDraftModel::DRAFT_TYPE_NORMAL;
                $body["error"] = null;
                $body["recordID"] = null;
                if ((!empty($row["recordID"]) || !empty($body["recordID"])) && $row["recordType"] === "discussion") {
                    $recordID = $body["recordID"] ?? $row["recordID"];
                    $discussion = \DiscussionModel::instance()->getID($recordID, DATASET_TYPE_ARRAY);
                    if (!empty($discussion)) {
                        $body["recordID"] = $recordID;
                    }
                }
                $body = $this->getEventManager()->fireFilter("processNormalDraft", $body, $row);
            }
        }

        if (ContentDraftModel::enabled()) {
            $this->draftModel->update(set: $body, where: ["draftID" => $id]);
        } else {
            $recordType = $row["recordType"];
            $draftData = $this->draftModel->convertToLegacyDraft($body, $recordType);
            $draftData["DraftID"] = $id;
            $this->legacyDraftModel->save($draftData);
            $this->validateModel($this->legacyDraftModel);
        }

        $updatedRow = $this->draftByID($id);
        return $updatedRow;
    }

    /**
     * Create a draft.
     *
     * @param array $body The request body.
     * @return array
     */
    public function post(array $body): array
    {
        $this->permission("session.valid");
        $result = [];
        $result = $this->getEventManager()->fireFilter("beforeDraftProcess", $result, $body, "post");
        if (!empty($result)) {
            return $result;
        }
        $addSchedule = $this->checkCanAddScheduledDraft($body);
        if ($addSchedule) {
            $this->checkSchedulePermission();
            $in = $this->draftSchedulePostSchema();
        } else {
            $in = $this->draftPostSchema();
        }
        $in = $this->schema($in, ["DraftPost", "in"]);

        $body = $in->validate($body);
        $body["attributes"]["format"] = $body["attributes"]["format"] ?? "Text";
        $isDiscussion = $body["recordType"] === "discussion";
        $liveRecord = !empty($body["recordID"]);
        if ($liveRecord && $isDiscussion) {
            $discussion = \DiscussionModel::instance()->getID($body["recordID"], DATASET_TYPE_ARRAY);
            if (empty($discussion)) {
                throw new NotFoundException("Discussion", ["discussionID" => $body["recordID"]]);
            }
        }
        if ($addSchedule) {
            // this is a scheduled draft, we need to check if the record type is discussion and create a placeholder record for it for permLink
            if ($isDiscussion) {
                $this->addUpdateScheduledDraftDiscussionRecord($body);
                $discussionID = $body["recordID"] ?? null;
                if (empty($discussionID)) {
                    ErrorLogger::error("Error creating a draft", ["draft", "post"], ["body" => $body]);
                    throw new ClientException("Error creating a draft");
                }
                // Remove the placeholder record from polluting the current table
                if (!$liveRecord) {
                    $this->deleteDiscussionRecord($discussionID);
                }
            } else {
                $body = $this->getEventManager()->fireFilter("processScheduleDraft", $body, true);
            }
        }

        if (ContentDraftModel::enabled()) {
            $draftID = $this->draftModel->insert(set: $body);
        } else {
            $draftData = $this->draftModel->convertToLegacyDraft($body);
            $draftID = $this->legacyDraftModel->save($draftData);
            $this->validateModel($this->legacyDraftModel);
        }

        $result = $this->draftByID($draftID);
        return $result;
    }

    /**
     * validate if the current draft is a scheduled draft and if it can be added
     *
     * @param array $body
     * @return bool
     */
    private function checkCanAddScheduledDraft(array $body): bool
    {
        $scheduledDraftEnabled = ContentDraftModel::draftSchedulingEnabled();
        $newCommunityDrafts = ContentDraftModel::enabled();
        $draftStatus = isset($body["draftStatus"])
            ? ContentDraftModel::DRAFT_TYPES[strtolower($body["draftStatus"])] ?? ContentDraftModel::DRAFT_TYPE_NORMAL
            : ContentDraftModel::DRAFT_TYPE_NORMAL;
        return $scheduledDraftEnabled &&
            $newCommunityDrafts &&
            $draftStatus === ContentDraftModel::DRAFT_TYPE_SCHEDULED;
    }

    /**
     * Get a draft by its unique ID.
     *
     * @param int $id
     *
     * @throws NotFoundException
     * @throws PermissionException
     *
     * @return array{draftID: int, insertUserID: int, parentRecordID: int|null, attributes: array}
     */
    private function draftByID(int $id): array
    {
        if (ContentDraftModel::enabled()) {
            try {
                $row = $this->draftModel->selectSingle(["draftID" => $id]);
                $draft = $row;
            } catch (NoResultsException $ex) {
                throw new NotFoundException("Draft", previous: $ex);
            }
        } else {
            $row = $this->legacyDraftModel->getID($id, DATASET_TYPE_ARRAY);
            if (!$row) {
                throw new NotFoundException("Draft");
            }

            $draft = $this->draftModel->normalizeLegacyDraft($row);
        }

        $draft = $this->fullSchema()->validate($draft);

        if ($draft["insertUserID"] !== $this->getSession()->UserID) {
            $this->permission("community.moderate");
        }

        return $draft;
    }

    /**
     * Get a draft schema with minimal add/edit fields.
     *
     * @return Schema Returns a schema object.
     */
    public function draftPostSchema(): Schema
    {
        $draftPostSchema = Schema::parse([
            "recordID?",
            "recordType",
            "parentRecordType?",
            "parentRecordID?",
            "attributes",
        ])->add($this->fullSchema());
        if (ContentDraftModel::draftSchedulingEnabled()) {
            $draftPostSchema->add(
                $this->schema([
                    "draftStatus:i" => [
                        "default" => 0,
                    ],
                ]),
                true
            );
            $draftPostSchema = $this->addDraftStatusFilter($draftPostSchema, "in");
        }
        return $draftPostSchema;
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema(): Schema
    {
        $draftScheduleEnabled = ContentDraftModel::draftSchedulingEnabled();
        $draftFields = $this->draftSchemaFields();
        if ($draftScheduleEnabled) {
            $draftFields = array_merge($draftFields, $this->scheduledSchemaFields());
        }
        $draftSchema = Schema::parse($draftFields);
        if ($draftScheduleEnabled) {
            $draftSchema = $this->addDraftStatusFilter($draftSchema, "out");
        }

        return $draftSchema;
    }

    /**
     * Schema for the normal draft
     */
    protected function draftSchemaFields(): array
    {
        return [
            "draftID:i",
            "recordID:i?",
            "recordType:s",
            "parentRecordType:s?",
            "parentRecordID:i?",
            "attributes:o" => "A free-form object containing all custom data for this draft.",
            "insertUserID:i",
            "dateInserted:dt",
            "updateUserID:i|n",
            "dateUpdated:dt|n",
        ];
    }

    /**
     * Schema for additional Schema scheduled fields
     */
    protected function scheduledSchemaFields(): array
    {
        return ["dateScheduled:dt|n", "draftStatus:s"];
    }

    /**
     * Content list schema
     * @return Schema
     */
    protected function contentDraftSchema(): Schema
    {
        $draftSchemaArray = $this->draftSchemaFields();
        $additionalListingFields = ["name:s|n?", "excerpt:s|n?", "editUrl:s|n", "breadCrumbs:a|n", "permaLink:s|n"];
        $draftFields = array_merge($draftSchemaArray, $additionalListingFields);
        $draftSchema = Schema::parse($draftFields);
        if (ContentDraftModel::draftSchedulingEnabled()) {
            $draftSchema->add($this->schema(["dateScheduled:dt|n", "draftStatus:s", "failedReason:s|n"]), true);
            $draftSchema = $this->addDraftStatusFilter($draftSchema, "out");
        }

        return $draftSchema;
    }

    /**
     * Generate a Schedule validation schema based on record type
     *
     * @return Schema
     */
    private function draftSchedulePostSchema(?int $draftID = null): Schema
    {
        $draftSchema = $this->draftPostSchema();
        $scheduledSchemaFields = ["draftStatus:i", "dateScheduled:dt"];
        $draftSchema->add($this->schema($scheduledSchemaFields), true);
        $draftSchema
            ->addValidator("", function ($draft, ValidationField $field) {
                if (($draft["recordType"] ?? null) !== "discussion") {
                    return true;
                }
                $attributes = $draft["attributes"] ?? [];
                if (empty($attributes["format"])) {
                    $field->setName("attributes.format");
                    $field->addError("format is required.");
                    return false;
                }
                $bodyRequired = \Gdn::config("Vanilla.DiscussionBody.Required", true);
                if (empty($attributes["body"]) && $bodyRequired) {
                    $field->setName("attributes.body");
                    $field->addError("body is required.");
                    return false;
                }
                if (empty($attributes["draftMeta"]["name"])) {
                    $field->setName("attributes.draftMeta.name");
                    $field->addError("name is required.");
                    return false;
                }
                if (empty($attributes["draftMeta"]["postTypeID"])) {
                    $field->setName("attributes.draftMeta.postTypeID");
                    $field->addError("postTypeID is required.");
                    return false;
                }

                return true;
            })
            ->addValidator("", function ($draft, ValidationField $field) use ($draftID) {
                $recordID = $draft["recordID"] ?? null;
                if (!empty($recordID)) {
                    $where = [
                        "recordID" => $recordID,
                        "draftStatus" => ContentDraftModel::DRAFT_TYPE_SCHEDULED,
                        "recordType" => $draft["recordType"],
                    ];
                    if (!empty($draftID)) {
                        $where["draftID <>"] = $draftID;
                    }

                    $drafts = $this->draftModel->getDrafts($where, limit: 1, offset: 0, order: "draftID");
                    $scheduledDraft = $drafts && count($drafts) > 0 ? $drafts[0] : null;

                    if ($scheduledDraft) {
                        $errorMessage =
                            "An update is already scheduled for this item. Please wait for the scheduled update to be published, or cancel it before scheduling a new one.";

                        $isOwnSchedule = \Gdn::session()->UserID === $scheduledDraft["insertUserID"];

                        if (!$isOwnSchedule) {
                            $draftOwner = \Gdn::userModel()->getID($scheduledDraft["insertUserID"], DATASET_TYPE_ARRAY);
                            $errorMessage =
                                "An update is already scheduled for this item by " .
                                htmlspecialchars($draftOwner["Name"]) .
                                ". You can’t schedule another update while one is pending.";
                        }
                        $field->setName("recordID");
                        $field->addError($errorMessage, [
                            "status" => [
                                "error" => 400,
                                "recordID" => $recordID,
                                "draftID" => $scheduledDraft["draftID"],
                                "isOwnSchedule" => $isOwnSchedule,
                            ],
                        ]);
                    }
                }
                return true;
            });
        return $this->schema($this->addDateScheduleValidator($draftSchema), ["DraftSchedulePost", "in"]);
    }

    /**
     * Update an existing schedule datetime
     *
     * @param int $draftID
     * @param array $body
     * @return Data
     * @throws ClientException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws \Garden\Schema\ValidationException
     * @throws \Garden\Web\Exception\HttpException
     */
    public function patch_schedule(int $draftID, array $body): Data
    {
        $this->checkSchedulePermission();

        $in = $this->addDateScheduleValidator($this->schema(["dateScheduled:dt"]));

        // Ensure it exists and we have permission to edit it.
        $draft = $this->draftByID($draftID);
        if ($draft["draftStatus"] !== "scheduled") {
            throw new ClientException("You cannot update the schedule as it is not currently in the scheduled state.");
        }

        $body = $in->validate($body, true);
        $this->draftModel->update(set: $body, where: ["draftID" => $draftID]);
        $updatedRow = $this->draftByID($draftID);
        return new Data($updatedRow);
    }

    /**
     * check to see if the user has permission to schedule drafts
     *
     * @return void
     * @throws ClientException
     * @throws PermissionException
     * @throws \Garden\Web\Exception\HttpException
     */
    public function checkSchedulePermission(): void
    {
        $this->permission("Schedule.Allow");
        if (!FeatureFlagHelper::featureEnabled(ContentDraftModel::FEATURE_SCHEDULE)) {
            throw new ClientException("Draft scheduling is not enabled.");
        }
    }

    /**
     * Add validation for date Schedules
     *
     * @param Schema $schema
     * @return Schema
     */
    protected function addDateScheduleValidator(Schema $schema): Schema
    {
        return $schema->addValidator("dateScheduled", function ($dateScheduled, ValidationField $field) {
            $now = CurrentTimeStamp::getDateTime();
            if ($now->getTimestamp() > $dateScheduled->getTimestamp()) {
                $field->addError("The scheduled date and time must be at least 15 minutes in the future.");
                return false;
            }
            if ($dateScheduled->getTimestamp() < $now->modify("+15 minutes")->getTimestamp()) {
                $field->addError("The scheduled date and time must be at least 15 minutes in the future.");
                return false;
            }
            if ($dateScheduled->getTimestamp() > $now->modify("+1 year")->getTimestamp()) {
                $field->addError("The scheduled date and time must be less than 1 year from now.");
                return false;
            }
            return true;
        });
    }

    /**
     * Filter for draft status
     *
     * @param Schema $schema
     * @return Schema
     */
    protected function addDraftStatusFilter(Schema $schema, string $type = "in"): Schema
    {
        $schema->addFilter("draftStatus", function ($draftStatus) use ($type) {
            $draftTypes = ContentDraftModel::DRAFT_TYPES;
            if (strtolower($type) == "in") {
                $draftStatus = strtolower($draftStatus);
            } else {
                $draftTypes = array_flip($draftTypes);
            }
            return $draftTypes[$draftStatus] ?? current($draftTypes);
        });
        return $schema;
    }

    /**
     * Convert back a scheduled or errored draft to a normal draft
     *
     * @param int $draftID
     * @return Data
     * @throws ClientException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws \Garden\Web\Exception\HttpException
     */
    public function patch_cancelSchedule(int $draftID): Data
    {
        $this->checkSchedulePermission();
        $draft = $this->draftByID($draftID);
        if ($draft["draftStatus"] === ContentDraftModel::DRAFT_TYPE_NORMAL) {
            throw new ClientException("The record currently is a draft no changes are needed.");
        }
        $set["draftStatus"] = ContentDraftModel::DRAFT_TYPE_NORMAL;
        $set["dateScheduled"] = null;
        $set["error"] = null;
        // check if this is a draft of an existing discussion
        if (!empty($draft["recordID"]) && $draft["recordType"] === "discussion") {
            $discussion = \DiscussionModel::instance()->getID($draft["recordID"], DATASET_TYPE_ARRAY);
            $set["recordID"] = empty($discussion) ? null : $draft["recordID"];
        }
        $this->draftModel->update(set: $set, where: ["draftID" => $draftID]);
        return new Data($this->draftByID($draftID));
    }

    /**
     * Validate the draft record and Create a placeholder discussion record with recordType "scheduled" if not exists
     *
     * @param array $draftData
     * @return array
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    protected function addUpdateScheduledDraftDiscussionRecord(array &$draftData): void
    {
        $internalClient = \Gdn::getContainer()->get(InternalClient::class);
        if ($draftData["recordType"] !== "discussion" || !isset($draftData["attributes"]["draftMeta"]["postTypeID"])) {
            return;
        }
        [$postData, $postUrl] = $this->draftModel->getDiscussionPostDataAndUrlFromDrafts($draftData);
        $postData["type"] = \DiscussionModel::SCHEDULE_TYPE;
        try {
            $result = $internalClient->post($postUrl, $postData);
        } catch (NotFoundException $ex) {
            if ($ex->getMessage() === "Discussion not found.") {
                // This is expected if the discussion is not found the database in case of an update
                return;
            }
            throw $ex;
        } catch (\Exception $ex) {
            throw $ex;
        }
        $record = $result->getBody();
        $draftData["recordID"] = $record["discussionID"];
    }

    /**
     * Delete a discussion record created
     *
     * @param int $record
     * @return void
     */
    private function deleteDiscussionRecord(int $discussionID): void
    {
        $discussionModel = \DiscussionModel::instance();
        $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        if (!$discussion) {
            throw new NotFoundException("Discussion", ["discussionID" => $discussionID]);
        }
        $sql = $discussionModel->SQL;
        $sql->delete("Discussion", ["DiscussionID" => $discussionID]);
    }
}
