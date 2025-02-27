<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Vanilla\ApiUtils;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Models\Model;
use Vanilla\Utility\ModelUtils;

/**
 * API Controller for the `/drafts` resource.
 */
class DraftsApiController extends \AbstractApiController
{
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

        $row = $this->draftByID($id);

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

        $out = Schema::parse(["draftID", "parentRecordID?", "attributes"])->add($this->fullSchema());

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
    public function index(array $query)
    {
        $this->permission("session.valid");

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
        $out = Schema::parse([":a" => $this->fullSchema()]);

        $query = $in->validate($query);

        [$offset, $limit] = ApiUtils::offsetLimit($query);

        if (!ContentDraftModel::enabled()) {
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
            foreach ($rows as &$row) {
                $row = $this->draftModel->normalizeLegacyDraft($row);
            }

            $count = $this->legacyDraftModel->getCount($where);
        } else {
            $where = ["insertUserID" => $this->getSession()->UserID];
            $where += ApiUtils::queryToFilters($in, $query);

            $rows = $this->draftModel->select(
                where: $where,
                options: [
                    Model::OPT_LIMIT => $limit,
                    Model::OPT_OFFSET => $offset,
                    Model::OPT_ORDER => "dateUpdated",
                    Model::OPT_DIRECTION => "desc",
                ]
            );
            $count = $this->draftModel->selectPagingCount($where, 1000);
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
    public function patch(int $id, array $body)
    {
        $this->permission("session.valid");

        $in = $this->draftPostSchema();

        // Ensure it exists and we have permission to edit it.
        $row = $this->draftByID($id);

        $body = $in->validate($body, true);

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
    public function post(array $body)
    {
        $this->permission("session.valid");

        $in = $this->draftPostSchema();

        $body = $in->validate($body);
        $body["attributes"]["format"] = $body["attributes"]["format"] ?? "Text";

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
        return Schema::parse(["recordType", "parentRecordType?", "parentRecordID?", "attributes"])->add(
            $this->fullSchema()
        );
    }

    /**
     * Get a schema instance comprised of all available draft fields.
     *
     * @return Schema Returns a schema object.
     */
    protected function fullSchema(): Schema
    {
        return Schema::parse([
            "draftID:i",
            "recordType:s",
            "parentRecordType:s?",
            "parentRecordID:i?",
            "attributes:o" => "A free-form object containing all custom data for this draft.",
            "insertUserID:i",
            "dateInserted:dt",
            "updateUserID:i|n",
            "dateUpdated:dt|n",
        ]);
    }
}
