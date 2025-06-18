<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\Web\Exception\ForbiddenException;
use Vanilla\Models\PipelineModel;
use Vanilla\Schema\RangeExpression;
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
     * @param array $postMeta
     * @return void
     * @throws \Throwable
     */
    public function updatePostMeta(string $recordType, int $recordID, array $postMeta)
    {
        if (empty($postMeta)) {
            return;
        }

        $this->database->runWithTransaction(function () use ($recordType, $recordID, $postMeta) {
            foreach ($postMeta as $postFieldID => $postFieldValue) {
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
        });
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
     * Join post fields with an array of discussion records.
     *
     * @param array $rowOrRows
     * @return void
     * @throws \Exception
     */
    public function joinPostMeta(array &$rowOrRows): void
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

        $discussionIDs = array_column($rows, "discussionID");
        $postTypeIDs = array_column($rows, "postTypeID");

        // Run the query to get post meta values and index them by recordID and postFieldID.
        $postMetaRecords = $this->select(["recordType" => "discussion", "recordID" => $discussionIDs]);
        $postMetaRecords = ArrayUtils::arrayColumnArrays($postMetaRecords, null, "recordID");
        $postMetaRecords = array_map(fn($record) => $this->condensePostMeta($record), $postMetaRecords);

        // Get post field definitions indexed by postTypeID and postFieldID
        $postFields = $this->postFieldModel->getPostFieldsByPostTypes([
            "ptpf.postTypeID" => $postTypeIDs,
            "isActive" => true,
        ]);
        $postFields = ArrayUtils::arrayColumnArrays($postFields, null, "postTypeID");
        $postFields = array_map(fn($field) => array_column($field, null, "postFieldID"), $postFields);

        // Get system fields that serve as catch-all fields for when posts are converted.
        $systemPostFields = $this->postFieldModel->getWhere(["isActive" => true, "isSystemHidden" => true]);
        $systemPostFields = array_column($systemPostFields, null, "postFieldID");

        // Join the post meta values with the records after normalization and permission checks.
        foreach ($rows as &$row) {
            $recordPostMeta = [];
            foreach ($postMetaRecords[$row["discussionID"]] ?? [] as $postFieldID => $value) {
                $postField = $postFields[$row["postTypeID"]][$postFieldID] ?? ($systemPostFields[$postFieldID] ?? null);
                if (!isset($postField)) {
                    continue;
                }
                if (!$this->canViewPostField($postField, $row["insertUserID"])) {
                    continue;
                }

                $value = $this->normalizeValueForOutput($value, $postField["dataType"]);
                $recordPostMeta[$postFieldID] = $value;
            }
            $row["postMeta"] = $recordPostMeta;
        }
    }

    /**
     * Adds post field values to discussion rows' `bodyPlainText` so that they are searchable.
     *
     * @param array $rowOrRows
     * @return void
     * @throws \Exception
     */
    public function updateBodyPlainText(array &$rowOrRows): void
    {
        if (ArrayUtils::isAssociative($rowOrRows)) {
            $rows = [&$rowOrRows];
        } else {
            $rows = &$rowOrRows;
        }

        $postFields = array_column($this->postFieldModel->getWhere(["isActive" => true]), null, "postFieldID");
        foreach ($rows as &$row) {
            if (array_key_exists("bodyPlainText", $row) && array_key_exists("postMeta", $row)) {
                $postFieldsToAppend = [];
                foreach ($row["postMeta"] as $postFieldID => $value) {
                    $postField = $postFields[$postFieldID] ?? null;

                    if (($postField["dataType"] ?? null) === "boolean") {
                        // It doesn't make sense to search for a boolean value. This will be handled as a filter.
                        continue;
                    }

                    $label = $postField["label"] ?? $postFieldID;
                    $value = self::normalizeValueForDisplay($value);
                    $postFieldsToAppend[] = "$label: $value";
                }
                $row["bodyPlainText"] .= !empty($postFieldsToAppend) ? "\n" . implode("\n", $postFieldsToAppend) : "";
            }
        }
    }

    /**
     * Get post fields for a single discussion record.
     *
     * @param int $recordID
     * @param string $postTypeID
     * @param array $filters
     * @return array|null
     * @throws \Exception
     */
    public function getPostMeta(int $recordID, string $postTypeID, array $filters = []): ?array
    {
        $postMetaRecords = $this->select(["recordType" => "discussion", "recordID" => $recordID]);
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
    public function movePostMeta(int $recordID, string $fromPostTypeID, string $toPostTypeID, array $postMeta): void
    {
        // Get the post fields for the destination post type.
        $postFields = $this->postFieldModel->getWhere(["postTypeID" => $toPostTypeID]);
        $postFieldsIndexed = array_column($postFields, null, "postFieldID");

        // Get the post fields for the origin post type.
        $fromPostFields = $this->postFieldModel->getWhere(["postTypeID" => $fromPostTypeID]);
        $fromPostFieldsIndexed = array_column($fromPostFields, null, "postFieldID");

        // First delete existing post meta.
        $this->delete(["recordType" => "discussion", "recordID" => $recordID]);

        uksort($postMeta, fn($key) => in_array($key, PostFieldModel::SPECIAL_CATCH_ALL_FIELDS) ? -1 : 0);

        $pendingFields = [];
        foreach ($postMeta as $postFieldID => $value) {
            if (isset($postFieldsIndexed[$postFieldID])) {
                // If the same field is in the destination post type, insert it with the same post field ID.
                $this->insert([
                    "recordType" => "discussion",
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
                "recordType" => "discussion",
                "recordID" => $recordID,
                "postFieldID" => $postFieldID,
                "value" => implode("\n", $fields),
            ]);
        }

        // Delete post field values from origin post type.
    }

    /**
     * Format post field value for displaying in plain text.
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
        if ($value instanceof \DateTimeImmutable) {
            return $value->format("Y-m-d");
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

    /**
     * Filter discussion records based on post field values.
     *
     * @param array $filterFields
     * @return array
     */
    public function discussionMetaPostFieldsFilter(array $filterFields): array
    {
        if (!PostTypeModel::isPostTypesFeatureEnabled()) {
            return [];
        }
        $availableViewFields = PostFieldModel::getAvailableViewFieldsForCurrentSessionUser();
        if (empty($filterFields) || empty($availableViewFields)) {
            return [];
        }
        $availableViewFields = array_column($availableViewFields, null, "postFieldID");

        $sql = $this->createSql();
        $sql->select("recordID")
            ->from("postMeta")
            ->where("recordType", "discussion");

        foreach ($filterFields as $filterField => $filterValue) {
            if (!isset($availableViewFields[$filterField])) {
                throw new ForbiddenException("You do not have permission to view $filterField.");
            }
            $datatype = $availableViewFields[$filterField]["dataType"];
            $formType = $availableViewFields[$filterField]["formType"];
            $this->generateQueryFilter($sql, $filterField, $filterValue, $datatype, $formType);
        }
        $result = $sql->get()->resultArray();
        if (!empty($result)) {
            $result = array_column($result, "recordID");
        }
        return $result;
    }

    /**
     * Query search filter for discussion filter .
     *
     * @param \Gdn_SQLDriver $sql
     * @param string $filterField
     * @param mixed $filterValue
     * @param string $datatype
     * @param string $formType
     * @return void
     */
    private function generateQueryFilter(
        \Gdn_SQLDriver &$sql,
        string $filterField,
        mixed $filterValue,
        string $datatype,
        string $formType
    ): void {
        switch ([$datatype, $formType]) {
            case [PostFieldModel::DATA_TYPES["BOOLEAN"], PostFieldModel::FORM_TYPES["CHECKBOX"]]:
            case [PostFieldModel::DATA_TYPES["NUMBER"], PostFieldModel::FORM_TYPES["NUMBER"]]:
                $value = (int) $filterValue;
                $sql->beginWhereGroup()
                    ->where("postFieldID", $filterField)
                    ->where("queryValue", $filterField . "." . $value)
                    ->endWhereGroup();
                break;
            case [PostFieldModel::DATA_TYPES["DATE"], PostFieldModel::FORM_TYPES["DATE"]]:
                $sql->beginWhereGroup()->where("postFieldID", $filterField);
                if ($filterValue instanceof RangeExpression) {
                    $dateRange = $filterValue->getValues();
                    foreach ($dateRange as $op => $range) {
                        if ($op === "=") {
                            $sql->where("queryValue", $filterField . "." . $range);
                        } else {
                            $sql->where("timestamp(value) $op", $range, false);
                        }
                    }
                    $sql->endWhereGroup();
                }
                break;
            case [PostFieldModel::DATA_TYPES["TEXT"], PostFieldModel::FORM_TYPES["TEXT"]]:
            case [PostFieldModel::DATA_TYPES["TEXT"], PostFieldModel::FORM_TYPES["TEXT_MULTILINE"]]:
                $sql->beginWhereGroup()
                    ->where("postFieldID", $filterField)
                    ->like("queryValue", $filterField . ".%" . $filterValue, $sql::LIKE_RIGHT)
                    ->endWhereGroup();
                break;
            case [PostFieldModel::DATA_TYPES["STRING_MUL"], PostFieldModel::FORM_TYPES["TOKENS"]]:
            case [PostFieldModel::DATA_TYPES["TEXT"], PostFieldModel::FORM_TYPES["DROPDOWN"]]:
                if (!is_array($filterValue)) {
                    $filterValue = [$filterValue];
                }

                foreach ($filterValue as &$value) {
                    $value = $filterField . "." . $value;
                }

                $sql->beginWhereGroup()
                    ->where("postFieldID", $filterField)
                    ->_whereIn("queryValue", $filterValue)
                    ->endWhereGroup();
                break;
            default:
                break;
        }
    }
}
