<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Vanilla\Database\CallbackWhereExpression;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\StringUtils;

class PostMetaModel extends PipelineModel
{
    const QUERY_VALUE_LENGTH = 500;

    /**
     * D.I.
     *
     * @param PostFieldModel $postFieldModel
     */
    public function __construct(private PostFieldModel $postFieldModel, private PostTypeModel $postTypeModel)
    {
        parent::__construct("postMeta");
    }

    /**
     * Structures the post meta table.
     *
     * @param \Gdn_DatabaseStructure $structure
     * @return void
     * @throws \Exception
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postMeta")
            ->primaryKey("postMetaID")
            ->column("recordType", "varchar(50)")
            ->column("recordID", "int")
            ->column("postFieldID", "varchar(100)")
            ->column("value", "text", true)
            ->column("queryValue", "varchar(500)")
            ->set();

        $structure
            ->table("postMeta")
            ->createIndexIfNotExists("IX_postMeta_recordType_recordID_postFieldID", [
                "recordType",
                "recordID",
                "postFieldID",
            ])
            ->createIndexIfNotExists("UX_postMeta_recordType_recordID_queryValue", [
                "recordType",
                "recordID",
                "queryValue",
            ]);
    }

    /**
     * Update post meta for a given `recordType` and `recordID`.
     *
     * @param string $recordType
     * @param int $recordID
     * @param array $postFields
     * @return void
     * @throws \Throwable
     */
    public function updatePostFields(string $recordType, int $recordID, array $postFields)
    {
        if (empty($postFields)) {
            return;
        }
        try {
            $this->database->beginTransaction();
            foreach ($postFields as $postFieldID => $postFieldValue) {
                $values = is_array($postFieldValue) ? $postFieldValue : [$postFieldValue];
                $this->delete([
                    "recordType" => $recordType,
                    "recordID" => $recordID,
                    "postFieldID" => $postFieldID,
                ]);
                foreach ($values as $value) {
                    $this->insert([
                        "recordType" => $recordType,
                        "recordID" => $recordID,
                        "postFieldID" => $postFieldID,
                        "value" => $value,
                    ]);
                }
            }
            $this->database->commitTransaction();
        } catch (\Throwable $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
    }

    public function insert(array $set, array $options = [])
    {
        $set["queryValue"] = $this->createQueryValue($set["postFieldID"], $set["value"]);
        $set["value"] = $this->normalizeValue($set["value"]);
        return parent::insert($set, $options);
    }

    /**
     * Normalizes value for storage.
     *
     * @param $value
     * @return string
     */
    private function normalizeValue($value)
    {
        if ($value instanceof \DateTimeInterface) {
            $value = $value->format(MYSQL_DATE_FORMAT);
        }
        if (is_bool($value)) {
            $value = (int) $value;
        }
        return StringUtils::stripUnicodeWhitespace($value);
    }

    /**
     * Normalize value for output based on `dataType`.
     *
     * @param mixed $value
     * @param string $dataType
     * @return mixed
     */
    private function normalizeValueForOutput(mixed $value, string $dataType): mixed
    {
        if ($dataType === "date") {
            try {
                return new \DateTimeImmutable($value, new \DateTimeZone("UTC"));
            } catch (\Throwable $e) {
                return null;
            }
        }
        return match ($dataType) {
            "boolean" => (bool) $value,
            "number" => (int) $value,
            "string[]" => !is_array($value) ? [$value] : $value,
            "number[]" => array_map("intval", !is_array($value) ? [$value] : $value),
            default => $value,
        };
    }

    /**
     * Checks if the current user can view a post field given the post field row and user ID of the user that created the record.
     *
     * @param array $postField Complete post field row.
     * @param int|null $insertUserID User ID of the user that created the record.
     * @return bool
     */
    private function canViewPostField(array $postField, ?int $insertUserID): bool
    {
        if (!$postField["isActive"]) {
            return false;
        }
        return match ($postField["visibility"]) {
            "public" => true,
            "private" => (!is_null($insertUserID) && \Gdn::session()->UserID === $insertUserID) ||
                \Gdn::session()->checkPermission("personalInfo.view"),
            "internal" => \Gdn::session()->checkPermission("internalInfo.view"),
            default => false,
        };
    }

    /**
     * Creates query value from the postFieldID and value.
     *
     * @param $postFieldID
     * @param $value
     * @return string
     */
    private function createQueryValue($postFieldID, $value)
    {
        $value = $this->normalizeValue($value);
        $queryValue = "$postFieldID.$value";
        $queryValue = substr($queryValue, 0, self::QUERY_VALUE_LENGTH);
        return $queryValue;
    }

    /**
     * Join post fields with an array of post records.
     *
     * @param array $rowOrRows
     * @return void
     * @throws \Exception
     */
    public function joinPostFields(array &$rowOrRows): void
    {
        if (!PostTypeModel::isPostTypesFeatureEnabled()) {
            return;
        }
        reset($rowOrRows);
        $single = is_string(key($rowOrRows));
        if ($single) {
            $rows = [&$rowOrRows];
        } else {
            $rows = &$rowOrRows;
        }

        // First build the query.
        $recordTypesForLookup = [];
        $sql = $this->createSql()
            ->from($this->getTable())
            ->select()
            ->select("recordType,'|',recordID", "concat", "recordKey");
        foreach ($rows as $row) {
            $sql->orWhere(["recordType" => $row["postTypeID"], "recordID" => $row["discussionID"]]);
            $recordTypesForLookup[] = $row["postTypeID"];
        }

        // Run the query to get post meta values and index them by recordType (postTypeID) + recordID and postFieldID.
        $postMetaRecords = $sql->get()->resultArray();
        $postMetaRecords = ArrayUtils::arrayColumnArrays($postMetaRecords, null, "recordKey");
        $postMetaRecords = array_map(fn($record) => $this->condensePostMeta($record), $postMetaRecords);

        // Get post field definitions indexed by postTypeID and postFieldID
        $postFields = $this->postFieldModel->getWhere(["postTypeID" => $recordTypesForLookup, "isActive" => true]);
        $postFields = ArrayUtils::arrayColumnArrays($postFields, null, "postTypeID");
        $postFields = array_map(fn($field) => array_column($field, null, "postFieldID"), $postFields);

        // Get system fields that serve as catch-all fields for when posts are converted.
        $systemPostFields = $this->postFieldModel->getWhere(["isActive" => true, "isSystemHidden" => true]);
        $systemPostFields = array_column($systemPostFields, null, "postFieldID");

        // Join the post meta values with the records after normalization and permission checks.
        foreach ($rows as &$row) {
            $recordKey = $row["postTypeID"] . "|" . $row["discussionID"];

            $recordPostFields = [];
            foreach ($postMetaRecords[$recordKey] ?? [] as $postFieldID => $value) {
                $postField = $postFields[$row["postTypeID"]][$postFieldID] ?? ($systemPostFields[$postFieldID] ?? null);
                if (!isset($postField)) {
                    continue;
                }
                if (!$this->canViewPostField($postField, $row["insertUserID"])) {
                    continue;
                }

                $value = $this->normalizeValueForOutput($value, $postField["dataType"]);
                $recordPostFields[$postFieldID] = $value;
            }
            $row["postFields"] = $recordPostFields;
        }
    }

    /**
     * Get post fields for a single post record.
     *
     * @param int $recordID
     * @param string $postTypeID
     * @param array $filters
     * @return array|null
     * @throws \Exception
     */
    public function getPostFields(int $recordID, string $postTypeID, array $filters = []): ?array
    {
        $postMetaRecords = $this->select(["recordType" => $postTypeID, "recordID" => $recordID]);
        $postMetaRecords = $this->condensePostMeta($postMetaRecords);

        $postFields = $this->postFieldModel->getWhere($filters + ["postTypeID" => $postTypeID, "isActive" => true]);
        $postFields = array_column($postFields, null, "postFieldID");

        // Get system fields that serve as catch-all fields for when posts are converted.
        $systemPostFields = $this->postFieldModel->getWhere(["isActive" => true, "isSystemHidden" => true]);
        $systemPostFields = array_column($systemPostFields, null, "postFieldID");

        $recordPostFields = [];
        foreach ($postMetaRecords as $postFieldID => $value) {
            $postField = $postFields[$postFieldID] ?? ($systemPostFields[$postFieldID] ?? null);
            if (!isset($postField)) {
                continue;
            }

            $value = $this->normalizeValueForOutput($value, $postField["dataType"]);
            $recordPostFields[$postFieldID] = $value;
        }
        return $recordPostFields;
    }

    /**
     * @param $postMeta
     * @return array
     */
    private function condensePostMeta($postMeta): array
    {
        $postMetaCondensed = [];
        foreach ($postMeta as $metaItem) {
            $metaName = $metaItem["postFieldID"];
            $metaValue = $metaItem["value"];

            if (isset($postMetaCondensed[$metaName])) {
                if (is_array($postMetaCondensed[$metaName])) {
                    $postMetaCondensed[$metaName][] = $metaValue;
                } else {
                    $postMetaCondensed[$metaName] = [$postMetaCondensed[$metaName], $metaValue];
                }
            } else {
                $postMetaCondensed[$metaName] = $metaValue;
            }
        }
        return $postMetaCondensed;
    }

    /**
     * Migrate post fields when moving a record from one post type to another.
     *
     * @param int $recordID
     * @param string $fromPostTypeID
     * @param string $toPostTypeID
     * @return void
     * @throws \Exception
     */
    public function movePostFields(int $recordID, string $fromPostTypeID, string $toPostTypeID): void
    {
        // Get the existing post meta for the record.
        $postMeta = $this->getPostFields($recordID, $fromPostTypeID);

        // Get the post fields for the destination post type.
        $postFields = $this->postFieldModel->getWhere(["postTypeID" => $toPostTypeID]);
        $postFieldsIndexed = array_column($postFields, null, "postFieldID");

        // Get the post fields for the origin post type.
        $fromPostFields = $this->postFieldModel->getWhere(["postTypeID" => $fromPostTypeID]);
        $fromPostFieldsIndexed = array_column($fromPostFields, null, "postFieldID");

        uksort($postMeta, fn($key) => in_array($key, PostFieldModel::SPECIAL_CATCH_ALL_FIELDS) ? -1 : 0);

        $pendingFields = [];
        foreach ($postMeta as $postFieldID => $value) {
            if (isset($postFieldsIndexed[$postFieldID])) {
                // If the same field is in the destination post type, insert it with the same post field ID.
                $this->insert([
                    "recordType" => $toPostTypeID,
                    "recordID" => $recordID,
                    "postFieldID" => $postFieldID,
                    "value" => $value,
                ]);
                continue;
            }

            if (in_array($postFieldID, PostFieldModel::SPECIAL_CATCH_ALL_FIELDS)) {
                // This was already in one of the built-in fields, add the value to the array as-is.
                $pendingFields[$postFieldID][] = $value;
                continue;
            }

            $fromPostFieldVisibility = $fromPostFieldsIndexed[$postFieldID]["visibility"];
            $fromPostFieldLabel = $fromPostFieldsIndexed[$postFieldID]["label"];

            $postFieldID = self::getPostFieldIDFromVisibility($fromPostFieldVisibility);

            // For all other fields, collect them to process into special built-in post fields.
            $pendingFields[$postFieldID][] = $fromPostFieldLabel . ": " . self::normalizeValueForDisplay($value);
        }

        // Insert collected post fields into post meta using special built-in post fields.
        foreach ($pendingFields as $postFieldID => $fields) {
            $this->insert([
                "recordType" => $toPostTypeID,
                "recordID" => $recordID,
                "postFieldID" => $postFieldID,
                "value" => implode("\n", $fields),
            ]);
        }

        // Delete post field values from origin post type.
        $this->delete(["recordType" => $fromPostTypeID, "recordID" => $recordID]);
    }

    /**
     * Convert value for displaying in a text post field.
     *
     * @param mixed $value
     * @return mixed|string
     */
    private static function normalizeValueForDisplay(mixed $value): mixed
    {
        if (is_array($value)) {
            return implode(", ", $value);
        }
        if (is_bool($value)) {
            return $value ? "Yes" : "No";
        }
        return $value;
    }

    /**
     * Get a built-in post field ID for the given visibility.
     *
     * @param string $visibility
     * @return string
     */
    private static function getPostFieldIDFromVisibility(string $visibility): string
    {
        return match ($visibility) {
            "public" => PostFieldModel::PUBLIC_DATA_FIELD_ID,
            "private" => PostFieldModel::PRIVATE_DATA_FIELD_ID,
            default => PostFieldModel::INTERNAL_DATA_FIELD_ID,
        };
    }
}
