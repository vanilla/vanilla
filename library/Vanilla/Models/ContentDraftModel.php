<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use Garden\Web\Exception\NotFoundException;
use Gdn_Session;
use Vanilla\ApiUtils;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\PipelineModel;
use Vanilla\Permissions;
use Vanilla\Utility\ModelUtils;
use Vanilla\Web\PermissionCheckTrait;

/**
 * Handle all-purpose drafts.
 */
class ContentDraftModel extends PipelineModel
{
    use PermissionCheckTrait;

    public const FEATURE = "newCommunityDrafts";

    /**
     * @return bool
     */
    public static function enabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(self::FEATURE);
    }

    /**
     * DI.
     */
    public function __construct(private Gdn_Session $session, private \DraftModel $legacyDraftModel)
    {
        parent::__construct("contentDraft");
        $this->setPrimaryKey("draftID");

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor($this->session);
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);

        $jsonProcessor = new JsonFieldProcessor();
        $jsonProcessor->setFields(["attributes"]);
        $this->addPipelineProcessor($jsonProcessor);
    }

    /**
     * @param \Gdn_DatabaseStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        if (!$structure->tableExists("contentDraft")) {
            // We are setting up a site for the first time.
            // Default this to on.
            \Gdn::config()->saveToConfig("Feature." . self::FEATURE . ".Enabled", true);
        }

        $structure
            ->table("contentDraft")
            ->primaryKey("draftID")
            ->column("recordType", "varchar(64)", false, ["index", "index.record", "index.parentRecord"])
            ->column("recordID", "int", true, "index.record")
            ->column("parentRecordType", "varchar(15)", true)
            ->column("parentRecordID", "int", true, "index.parentRecord")
            ->column("attributes", "mediumtext")
            ->column("insertUserID", "int", false, "index")
            ->column("dateInserted", "datetime")
            ->column("updateUserID", "int")
            ->column("dateUpdated", "datetime")
            ->set();
        $structure
            ->table("contentDraft")
            ->createIndexIfNotExists("IX_contentDraft_parentRecordType_parentRecordID", [
                "parentRecordType",
                "parentRecordID",
            ]);
    }

    /**
     * Get draft count for particular user
     *
     * @param int $userID
     * @return int
     */
    public function draftsCount(int $userID): int
    {
        $countRecord = $this->createSql()
            ->from($this->getTable())
            ->select("*", "COUNT", "draftCount")
            ->where("insertUserID", $userID)
            ->groupBy("insertUserID")
            ->get()
            ->nextRow(DATASET_TYPE_ARRAY);

        return $countRecord["draftCount"] ?? 0;
    }

    /**
     * Delete a draft, while checking permissions for the deletion.
     *
     * Support for the legacy draft model or this one.
     *
     * @param int $draftID
     * @return void
     * @throws NotFoundException
     * @throws PermissionException
     */
    public function deleteDraftWithPermissionCheck(int $draftID): void
    {
        if (self::enabled()) {
            try {
                $draft = $this->selectSingle(["draftID" => $draftID]);
            } catch (NoResultsException $ex) {
                throw new NotFoundException("Draft", previous: $ex);
            }
            if ($draft["insertUserID"] !== $this->session->UserID) {
                $this->permission("community.moderate");
            }
            $this->delete(where: ["draftID" => $draftID]);
        } else {
            $draft = $this->legacyDraftModel->getID($draftID, DATASET_TYPE_ARRAY);
            if (!$draft) {
                throw new NotFoundException("Draft");
            }

            if ($draft["InsertUserID"] !== $this->session->UserID) {
                $this->permission("community.moderate");
            }

            $this->legacyDraftModel->deleteID($draftID);
            ModelUtils::validationResultToValidationException($this->legacyDraftModel);
        }
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @return array Return a Schema record.
     */
    public function normalizeLegacyDraft(array $dbRecord)
    {
        $parentRecordID = null;

        $commentAttributes = ["Body", "Format"];
        $discussionAttributes = ["Announce", "Body", "Closed", "Format", "Name", "Sink", "Tags", "Type", "GroupID"];
        if (array_key_exists("DiscussionID", $dbRecord) && !empty($dbRecord["DiscussionID"])) {
            $dbRecord["RecordType"] = "comment";
            $dbRecord["parentRecordType"] = "discussion";
            $parentRecordID = $dbRecord["DiscussionID"];
            $attributes = $commentAttributes;
        } else {
            if (array_key_exists("CategoryID", $dbRecord) && !empty($dbRecord["CategoryID"])) {
                $parentRecordID = $dbRecord["CategoryID"];
                $dbRecord["parentRecordType"] = "category";
            }
            $dbRecord["RecordType"] = "discussion";
            $attributes = $discussionAttributes;
        }
        $dbRecord["ParentRecordID"] = $parentRecordID;
        $dbRecord["Attributes"] = array_intersect_key($dbRecord, array_flip($attributes));

        // Remove redundant attribute columns on the row.
        foreach (array_merge($commentAttributes, $discussionAttributes) as $col) {
            unset($dbRecord[$col]);
        }

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
    }

    /**
     * Normalize a Schema record to match the database definition.
     *
     * @param array $schemaRecord Schema record.
     * @param string|null $recordType
     * @return array Return a database record.
     */
    public function convertToLegacyDraft(array $schemaRecord, string|null $recordType = null): array
    {
        // If the record type is not explicitly defined by the parameters, try to extract it from $body.
        if ($recordType === null && array_key_exists("recordType", $schemaRecord)) {
            $recordType = $schemaRecord["recordType"];
        }

        if (array_key_exists("attributes", $schemaRecord)) {
            $columns = [
                "announce",
                "body",
                "categoryID",
                "closed",
                "format",
                "name",
                "sink",
                "tags",
                "type",
                "groupID",
            ];
            $attributes = array_intersect_key($schemaRecord["attributes"], array_flip($columns));
            $schemaRecord = array_merge($schemaRecord, $attributes);
            unset($schemaRecord["attributes"]);
        }

        if (array_key_exists("tags", $schemaRecord)) {
            if (empty($schemaRecord["tags"])) {
                $schemaRecord["tags"] = null;
            } elseif (is_array($schemaRecord["tags"])) {
                $schemaRecord["tags"] = implode(",", $schemaRecord["tags"]);
            }
        }
        switch ($recordType) {
            case "comment":
                if (array_key_exists("parentRecordID", $schemaRecord)) {
                    $schemaRecord["DiscussionID"] = $schemaRecord["parentRecordID"];
                }
                $schemaRecord["Type"] = $schemaRecord["type"] ?? "comment";
                break;
            case "discussion":
                if (array_key_exists("parentRecordID", $schemaRecord)) {
                    $schemaRecord["CategoryID"] = $schemaRecord["parentRecordID"];
                }
                $schemaRecord["DiscussionID"] = null;
                $schemaRecord["Type"] = $schemaRecord["type"] ?? "Discussion";
                break;
        }
        unset($schemaRecord["recordType"], $schemaRecord["parentRecordID"]);

        $result = ApiUtils::convertInputKeys($schemaRecord);
        return $result;
    }

    /**
     * @return Permissions|null
     */
    protected function getPermissions(): ?Permissions
    {
        return $this->session->getPermissions();
    }
}
