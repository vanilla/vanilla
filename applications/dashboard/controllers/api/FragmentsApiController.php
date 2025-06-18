<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2025 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Controllers\Api;

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Redirect;
use Gdn_Session;
use Ramsey\Uuid\Uuid;
use UserMetaModel;
use UserModel;
use Vanilla\ApiUtils;
use Vanilla\Dashboard\Events\FragmentDisclosureEvent;
use Vanilla\Dashboard\Models\FragmentModel;
use Vanilla\Layout\LayoutModel;
use Vanilla\Logging\AuditLogger;
use Vanilla\Models\Model;
use Vanilla\Models\VanillaMediaSchema;
use Vanilla\Permissions;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Web\CacheControlConstantsInterface;

/**
 * /api/v2/fragments
 */
class FragmentsApiController extends \AbstractApiController
{
    public function __construct(
        private FragmentModel $fragmentModel,
        private \Gdn_Database $db,
        private \MediaModel $mediaModel,
        private UserMetaModel $userMetaModel,
        private Gdn_Session $session
    ) {
    }

    /**
     * GET /api/v2/fragments
     */
    public function index(array $query): array
    {
        $this->permission("site.manage");
        $in = Schema::parse([
            "status:s?" => [
                "enum" => array_merge(FragmentModel::STATUSES, ["latest"]),
                "default" => FragmentModel::STATUS_ACTIVE,
            ],
            "fragmentType:s?",
            "sort:s?" => [
                "enum" => ApiUtils::sortEnum("dateRevisionInserted"),
                "default" => "-dateRevisionInserted",
            ],
            "appliedStatus" => [
                "enum" => ["all", "applied", "not-applied"],
                "default" => "all",
            ],
        ]);

        $query = $in->validate($query);

        $where = [
            "status <>" => FragmentModel::STATUS_DELETED,
        ];
        if ($query["status"] === "latest") {
            $where["isLatest"] = true;
        } else {
            $where["status"] = $query["status"];
        }
        if ($fragmentType = $query["fragmentType"] ?? null) {
            $where["fragmentType"] = $fragmentType;
        }

        switch ($query["appliedStatus"]) {
            case "applied":
                $where["fragmentUUID"] = $this->fragmentModel->getAppliedFragmentUUIDs();
                break;
            case "not-applied":
                $where["fragmentUUID <>"] = $this->fragmentModel->getAppliedFragmentUUIDs();
                break;
        }

        // Filter to only active revisions
        $result = $this->fragmentModel->select(
            where: $where,
            options: [
                Model::OPT_SELECT => FragmentModel::fragmentSchema(),
                Model::OPT_ORDER => $query["sort"],
            ]
        );

        $this->normalizeRows($result);

        return $result;
    }

    /**
     * DELETE /api/v2/fragments/:fragmentUUID
     *
     * Delete a fragment.
     */
    public function delete(string $id, array $query = []): Data
    {
        $this->permission("site.manage");
        $in = Schema::parse(["fragmentRevisionUUID:s?"]);
        $query = $in->validate($query);

        $where = ["fragmentUUID" => $id];
        if ($revisionUUID = $query["fragmentRevisionUUID"] ?? null) {
            // Make sure the revision exists and is a draft
            $revision = $this->get($id, ["fragmentRevisionUUID" => $revisionUUID]);

            if ($revision["status"] !== FragmentModel::STATUS_DRAFT) {
                throw new ClientException("Cannot delete a non-draft revision.", 400);
            }

            $where["fragmentRevisionUUID"] = $revisionUUID;

            // Delete the draft and set the active revision back to latest.
            $this->db->runWithTransaction(function () use ($where) {
                $this->fragmentModel->update(set: ["status" => FragmentModel::STATUS_DELETED], where: $where);
                $this->fragmentModel->update(
                    set: ["isLatest" => true],
                    where: ["fragmentUUID" => $where["fragmentUUID"], "status" => FragmentModel::STATUS_ACTIVE]
                );
            });
        } else {
            // Make sure it exists
            $this->get($id, ["status" => FragmentModel::STATUS_ACTIVE]);

            // Now delete
            $this->fragmentModel->update(set: ["status" => FragmentModel::STATUS_DELETED], where: $where);
        }

        return new Data(
            null,
            meta: [
                "status" => 204,
            ]
        );
    }

    /**
     * POST /api/v2/fragments
     *
     * @param array $body
     * @return Data
     * @throws \Exception
     */
    public function post(array $body): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "fragmentType:s",
            "name:s",
            "js:s",
            "jsRaw:s",
            "css:s?",
            "customSchema:o?",
            "previewData:a?" => FragmentModel::previewDataSchema(),
            "files:a?" => new VanillaMediaSchema(true),
            "commitMessage:s?",
            "commitDescription:s?",
        ]);

        $body = $in->validate($body);

        // Generate a fresh fragment UUID
        $uuid = Uuid::uuid7()->toString();
        $body["fragmentUUID"] = $uuid;
        $body["customSchema"] =
            isset($body["customSchema"]) && is_array($body["customSchema"]) ? $body["customSchema"] : [];
        $body["isLatest"] = true;
        $body["status"] = FragmentModel::STATUS_ACTIVE;
        $body["commitMessage"] = $body["commitMessage"] ?? t("Initial commit");
        $body["commitDescription"] = $body["commitDescription"] ?? "";

        $this->fragmentModel->insert(set: $body);
        $this->assosciateMedia($uuid, $body["files"] ?? null);

        return $this->get($uuid);
    }

    /**
     * PATCH /api/v2/fragments/:fragmentUUID
     *
     * @param string $id
     * @param array $body
     *
     * @return Data
     */
    public function patch(string $id, array $body): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "name:s?",
            "js:s?",
            "jsRaw:s?",
            "css:s?",
            "customSchema:o?",
            "previewData:a?" => FragmentModel::previewDataSchema(),
            "files:a?" => new VanillaMediaSchema(true),
            "commitMessage:s?",
            "commitDescription:s?",
        ]);

        $body = $in->validate($body);

        // Not using selectSingle because it throws if there isn't one.
        $existingDrafts = $this->fragmentModel->select(
            where: ["fragmentUUID" => $id, "status" => FragmentModel::STATUS_DRAFT],
            options: [
                Model::OPT_LIMIT => 1,
            ]
        );
        $isCommit = isset($body["commitMessage"]);

        if (!$isCommit && !empty($existingDrafts)) {
            // Let's update that draft.
            $revisionUUID = $existingDrafts[0]["fragmentRevisionUUID"];
            $this->fragmentModel->update(set: $body, where: ["fragmentRevisionUUID" => $revisionUUID]);
        } else {
            // We are going to create a new draft.
            $existing = $this->get($id)->getData();
            $newRecord = array_merge($existing, $body);
            // we'll want a new UUID for this revision
            unset($newRecord["fragmentRevisionUUID"]);
            $newRecord["isLatest"] = true;
            $newRecord["status"] = $isCommit ? FragmentModel::STATUS_ACTIVE : FragmentModel::STATUS_DRAFT;
            $newRecord["commitMessage"] = $body["commitMessage"] ?? t("Draft");
            $newRecord["customSchema"] = $body["customSchema"] ?? [];
            $newRecord["commitDescription"] = $body["commitDescription"] ?? "";

            $revisionUUID = $this->db->runWithTransaction(function () use ($id, $newRecord, $isCommit) {
                $existingRecordUpdate = [
                    "isLatest" => false,
                ];
                if ($isCommit) {
                    $existingRecordUpdate["status"] = FragmentModel::STATUS_PAST_REVISION;
                }
                // Mark existing ones as inactive.
                $this->fragmentModel->update(
                    set: $existingRecordUpdate,
                    where: ["fragmentUUID" => $id, "status <>" => FragmentModel::STATUS_DELETED]
                );

                // Then insert the new, active row.
                return $this->fragmentModel->insert($newRecord);
            });
        }

        $this->assosciateMedia($id, $body["files"] ?? null);

        return $this->get($id, [
            "fragmentRevisionUUID" => $revisionUUID,
        ]);
    }

    /**
     * POST /api/v2/fragments/:fragmentUUID/commit-revision
     *
     * @param string $id
     * @param array $body
     * @return Data
     * @throws \Throwable
     */
    public function post_commitRevision(string $id, array $body): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse(["fragmentRevisionUUID:s", "commitMessage:s", "commitDescription:s?"]);

        $body = $in->validate($body);

        $fragment = $this->fragmentModel->selectSingle(
            where: ["fragmentUUID" => $id, "fragmentRevisionUUID" => $body["fragmentRevisionUUID"]]
        );

        $this->db->runWithTransaction(function () use ($id, $body) {
            // Mark existing ones as inactive.
            $this->fragmentModel->update(
                set: [
                    "isLatest" => false,
                    "status" => FragmentModel::STATUS_PAST_REVISION,
                ],
                where: ["fragmentUUID" => $id, "status <>" => FragmentModel::STATUS_DELETED]
            );

            // Then insert the new, active row.
            $this->fragmentModel->update(
                set: [
                    "isLatest" => true,
                    "status" => FragmentModel::STATUS_ACTIVE,
                    "commitMessage" => $body["commitMessage"],
                    "commitDescription" => $body["commitDescription"] ?? "",
                ],
                where: [
                    "fragmentUUID" => $id,
                    "fragmentRevisionUUID" => $body["fragmentRevisionUUID"],
                ]
            );
        });

        return $this->get($id, ["fragmentRevisionUUID" => $fragment["fragmentRevisionUUID"]]);
    }

    /**
     * Given files from a fragment, associate the media rows with the fragment.
     *
     * @param string $fragmentUUID
     * @param array|null $files
     * @return void
     */
    private function assosciateMedia(string $fragmentUUID, array|null $files): void
    {
        if (empty($files)) {
            return;
        }
        $mediaModel = $this->mediaModel;
        $mediaIDs = array_values(array_filter(array_column($files, "mediaID")));

        $mediaModel->assosciateWithRecord(
            where: [
                "MediaID" => $mediaIDs,
            ],
            foreignType: "fragment",
            foreignID: $fragmentUUID
        );
    }

    /**
     * GET /api/v2/fragments/:fragmentUUID/revisions
     *
     * @param string $id
     * @param array $query
     * @return Data
     * @throws \Exception
     */
    public function get_revisions(string $id, array $query): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "page:i?" => ["default" => 1],
            "limit:i?" => ["default" => 100, "max" => 500],
            "commitMessage?" => [
                // Pretty inneficient but the dataset is small and it let's automcomplete do a commit search.
                "type" => "string",
                "minLength" => 0,
            ],
        ]);
        $out = FragmentModel::fragmentSchema()->merge(Schema::parse(["commitMessage:s?"]));

        $query = $in->validate($query);

        [$offset, $limit] = ApiUtils::offsetLimit($query);

        $where = [
            "fragmentUUID" => $id,
            "status <>" => FragmentModel::STATUS_DELETED,
        ];

        if ($commitMessage = $query["commitMessage"] ?? null) {
            $where["commitMessage LIKE"] = "%$commitMessage%";
        }

        $revisions = $this->fragmentModel->select(
            where: $where,
            options: [
                Model::OPT_SELECT => $out,
                Model::OPT_ORDER => "-dateRevisionInserted",
                Model::OPT_LIMIT => $limit,
                Model::OPT_OFFSET => $offset,
            ]
        );
        $this->normalizeRows($revisions);

        $countRevisions = $this->fragmentModel->selectPagingCount(["fragmentUUID" => $id]);

        $paging = ApiUtils::numberedPagerInfo($countRevisions, "/api/v2/fragments/{$id}/revisions", $query, $in);

        return new Data($revisions, meta: ["paging" => $paging]);
    }

    /**
     * GET /api/v2/fragments/:fragmentUUID
     *
     * @param string $id
     * @param array $query
     * @return Data
     * @throws \Exception
     */
    public function get(string $id, array $query = []): Data
    {
        $this->permission("site.manage");

        $in = Schema::parse([
            "fragmentRevisionUUID:s?",
            "status:s?" => [
                "enum" => ["latest", FragmentModel::STATUS_ACTIVE],
            ],
        ])->addValidator("", SchemaUtils::onlyOneOf(["fragmentRevisionUUID", "status"]));
        $query = $in->validate($query);

        $query["status"] = $query["status"] ?? "latest";

        $where = ["fragmentUUID" => $id, "status <>" => FragmentModel::STATUS_DELETED];
        if (isset($query["fragmentRevisionUUID"])) {
            $where["fragmentRevisionUUID"] = $query["fragmentRevisionUUID"];
        } elseif ($query["status"] === "latest") {
            $where["isLatest"] = true;
        } else {
            $where["status"] = $query["status"];
        }

        $fragment = $this->fragmentModel->selectSingle(where: $where);
        $this->normalizeRows($fragment);

        return new Data($fragment);
    }

    /**
     * GET /api/v2/fragments/:fragmentUUID/js
     *
     * @param string $id
     * @param array $query
     * @return Data
     */
    public function get_js(string $id, array $query = []): Data
    {
        return $this->getCssOrJs("js", $id, $query);
    }

    /**
     * GET /api/v2/fragments/:fragmentUUID/css
     *
     * @param string $id
     * @param array $query
     * @return Data
     */
    public function get_css(string $id, array $query = []): Data
    {
        return $this->getCssOrJs("css", $id, $query);
    }

    /**
     * GET /api/v2/fragments/diclosure-state
     *
     * @return Data
     */
    public function get_disclosureState(): Data
    {
        $this->permission("site.manage");

        $value = $this->userMetaModel->getUserMeta($this->session->UserID, "acceptedFragmentDisclosure", false);

        $out = Schema::parse([
            "userID:i",
            "didAccept:b" => [
                "default" => false,
            ],
        ]);
        $result = $out->validate([
            "userID" => $this->session->UserID,
            "didAccept" => $value["acceptedFragmentDisclosure"],
        ]);
        return new Data($result);
    }

    /**
     * PUT /api/v2/fragments/diclosure-state
     *
     * @param array $body
     *
     * @return Data
     */
    public function put_disclosureState(array $body): Data
    {
        $this->permission("site.manage");
        $in = Schema::parse(["didAccept:b"]);
        $body = $in->validate($body);

        $this->userMetaModel->setUserMeta($this->session->UserID, "acceptedFragmentDisclosure", $body["didAccept"]);
        AuditLogger::log(new FragmentDisclosureEvent($body["didAccept"]));

        return $this->get_disclosureState();
    }

    /**
     * Utility to get CSS or JS of fragment or redirect to the latest revision.
     *
     * @param string $type
     * @param string $fragmentUUID
     * @param array $query
     *
     * @return Data
     */
    private function getCssOrJs(string $type, string $fragmentUUID, array $query): Data
    {
        // No permission intentionally.
        $in = Schema::parse(["fragmentRevisionUUID:s?"]);
        $query = $in->validate($query);

        $fragment = $this->fragmentModel->selectSingle([
            "fragmentUUID" => $fragmentUUID,
            "status" => FragmentModel::STATUS_ACTIVE,
        ]);
        $this->normalizeRows($fragment);

        if (!isset($query["fragmentRevisionUUID"])) {
            // Used in layout/theme editor to get the latest active CSS.
            return new Redirect(
                url(
                    "/api/v2/fragments/{$fragmentUUID}/{$type}?fragmentRevisionUUID={$fragment["fragmentRevisionUUID"]}",
                    true
                )
            );
        }

        $contents = match ($type) {
            "js" => $fragment["js"],
            "css" => $fragment["css"],
        };

        return new Data(
            $contents,
            meta: [
                CacheControlConstantsInterface::META_NO_VARY => true,
                // Customer could make this a field.
                "api-allow" => ["email"],
            ],
            headers: [
                "Content-Type" => match ($type) {
                    "js" => "application/javascript",
                    "css" => "text/css",
                },
                "Cache-Control" => CacheControlConstantsInterface::MAX_CACHE,
            ]
        );
    }

    /**
     * @param array $rowOrRows
     * @return void
     */
    public function normalizeRows(array &$rowOrRows): void
    {
        if (ArrayUtils::isAssociative($rowOrRows)) {
            $rows = [&$rowOrRows];
        } else {
            $rows = &$rowOrRows;
        }

        $appliedFragmentUUIDs = $this->fragmentModel->getAppliedFragmentUUIDs();
        foreach ($rows as &$row) {
            $row["isApplied"] = in_array($row["fragmentUUID"], $appliedFragmentUUIDs);
            $row["url"] = url("/appearance/fragments/{$row["fragmentUUID"]}/edit", true);
            $row["jsUrl"] = $this->fragmentModel->assetUrl("js", $row);
            $row["cssUrl"] = $this->fragmentModel->assetUrl("css", $row);
            if (isset($row["customSchema"]) && is_array($row["customSchema"]) && empty($row["customSchema"])) {
                $row["customSchema"] = new \stdClass();
            }
        }

        $this->fragmentModel->expandFragmentViews($rows);
    }
}
