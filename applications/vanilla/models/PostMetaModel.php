<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

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
    public function __construct(private PostFieldModel $postFieldModel)
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
                    $value = $this->normalizeValue($value);
                    $queryValue = $this->createQueryValue($postFieldID, $value);
                    $this->insert([
                        "recordType" => $recordType,
                        "recordID" => $recordID,
                        "postFieldID" => $postFieldID,
                        "value" => $value,
                        "queryValue" => $queryValue,
                    ]);
                }
            }
            $this->database->commitTransaction();
        } catch (\Throwable $e) {
            $this->database->rollbackTransaction();
            throw $e;
        }
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

    public function joinPostFields(array &$rowOrRows)
    {
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
        $postFields = $this->postFieldModel->select(["postTypeID" => $recordTypesForLookup, "isActive" => true]);
        $postFields = ArrayUtils::arrayColumnArrays($postFields, null, "postTypeID");
        $postFields = array_map(fn($field) => array_column($field, null, "postFieldID"), $postFields);

        // Join the post meta values with the records after normalization and permission checks.
        foreach ($rows as &$row) {
            $recordKey = $row["postTypeID"] . "|" . $row["discussionID"];

            $recordPostFields = [];
            foreach ($postMetaRecords[$recordKey] ?? [] as $postFieldID => $value) {
                $postField = $postFields[$row["postTypeID"]][$postFieldID] ?? null;
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
     * @param $postMeta
     * @return array
     */
    private function condensePostMeta($postMeta)
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
}
