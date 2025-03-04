<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use CategoryModel;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Web\ApiFilterMiddleware;

class InterestModel extends FullRecordCacheModel
{
    const SUGGESTED_CONTENT_FEATURE_FLAG = "SuggestedContent";

    const CONF_SUGGESTED_CONTENT_ENABLED = "suggestedContent.enabled";

    const CONF_SUGGESTED_CONTENT_MAX_TAG_COUNT = "suggestedContent.maxTagCount";

    /**
     * D.I.
     */
    public function __construct(
        private ProfileFieldModel $profileFieldModel,
        private \TagModel $tagModel,
        \Gdn_Cache $cache
    ) {
        parent::__construct("interest", $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 60 * 60, // 1 hour.
        ]);

        $booleanFields = new BooleanFieldProcessor(["isDefault", "isDeleted"]);
        $this->addPipelineProcessor($booleanFields);

        $objectFields = new JsonFieldProcessor(["profileFieldMapping"]);
        $this->addPipelineProcessor($objectFields);

        $arrayFields = new JsonFieldProcessor(["categoryIDs", "tagIDs"], 0);
        $this->addPipelineProcessor($arrayFields);

        $dateProcessor = new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);

        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
    }

    /**
     * Checks if Suggested Content is enabled.
     *
     * @return void
     * @throws ClientException
     */
    public function ensureSuggestedContentEnabled(): void
    {
        if (!$this::isSuggestedContentEnabled()) {
            throw new ClientException("Suggested Content is not enabled");
        }
    }

    /**
     * Whether Suggested Content is enabled.
     *
     * @return bool
     */
    public static function isSuggestedContentEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(self::SUGGESTED_CONTENT_FEATURE_FLAG) &&
            \Gdn::config(self::CONF_SUGGESTED_CONTENT_ENABLED);
    }

    /**
     * @param int $id
     * @return array|null
     * @throws \Exception
     */
    public function getInterest(int $id): ?array
    {
        $row = $this->selectSingle(["interestID" => $id, "isDeleted" => 0]);
        $rows = [&$row];

        $this->joinTags($rows);
        $this->joinCategories($rows);
        $this->joinProfileFields($rows);

        return $row;
    }

    /**
     * Query interests with filters.
     *
     * @param array $where
     * @return array
     */
    public function getWhere(array $where = []): array
    {
        $rows = $this->select(["isDeleted" => 0]);

        $rows = $this->matchFilters($rows, $where);

        $this->joinTags($rows);
        $this->joinCategories($rows);
        $this->joinProfileFields($rows);

        return $rows;
    }

    /**
     * Expand tag information for the tagIDs column.
     *
     * @param array $rows
     * @return void
     */
    protected function joinTags(array &$rows): void
    {
        $tags = array_filter(array_column($rows, "tagIDs"));
        $allTagIDs = array_unique(array_merge(...$tags));
        $tags = $this->tagModel->getTagsByIDs($allTagIDs, false);
        $tags = array_column($tags, "FullName", "TagID");

        foreach ($rows as &$row) {
            $row["tags"] = [];
            foreach ($row["tagIDs"] ?? [] as $tagID) {
                if (isset($tags[$tagID])) {
                    $row["tags"][] = [
                        "tagID" => $tagID,
                        "fullName" => $tags[$tagID],
                    ];
                }
            }
        }
    }

    /**
     * Expand category information for categoryIDs column.
     *
     * @param array $rows
     * @return void
     */
    protected function joinCategories(array &$rows): void
    {
        $categories = CategoryModel::categories();
        foreach ($rows as &$row) {
            $row["categories"] = [];

            foreach ($row["categoryIDs"] as $categoryID) {
                if (isset($categories[$categoryID])) {
                    $row["categories"][] = [
                        "categoryID" => $categoryID,
                        "name" => $categories[$categoryID]["Name"],
                    ];
                }
            }
        }
    }

    /**
     * Expand profile field information for the profileFieldMapping column.
     *
     * @param array $rows
     * @return void
     */
    protected function joinProfileFields(array &$rows): void
    {
        $indexProfileFields = $this->profileFieldModel->getEnabledProfileFieldsIndexed();
        foreach ($rows as &$row) {
            $row["profileFields"] = [];
            foreach ($row["profileFieldMapping"] as $apiName => $value) {
                if (isset($indexProfileFields[$apiName])) {
                    $profileField = [
                        "apiName" => $apiName,
                        "label" => $indexProfileFields[$apiName]["label"],
                        "mappedValue" => $value,
                    ];
                    if (isset($indexProfileFields[$apiName]["dropdownOptions"])) {
                        $profileField["options"] = $indexProfileFields[$apiName]["dropdownOptions"];
                    }
                    $row["profileFields"][] = $profileField;
                }
            }
        }
    }

    /**
     * This returns either default interests or interests matching profile fields.
     *
     * @param array $profileFieldValues
     * @return array
     */
    private function getByProfileFieldValues(array $profileFieldValues): array
    {
        $rows = $this->select(["isDeleted" => 0]);

        return array_values(
            array_filter($rows, function ($row) use ($profileFieldValues) {
                if ($row["isDefault"]) {
                    // Default interests are always returned.
                    return true;
                }

                if ($this->hasProfileFieldValues($row, $profileFieldValues)) {
                    return true;
                }

                return false;
            })
        );
    }

    /**
     * Return arrays of associated record IDs that match a user's interests.
     *
     * @param int $userID
     * @return array
     */
    public function getRecordIDsByUserID(int $userID): array
    {
        $profileFieldValues = $this->profileFieldModel->getUserProfileFields($userID);
        $rows = $this->getByProfileFieldValues($profileFieldValues);

        $categoryIDs = [];
        $tagIDs = [];
        foreach ($rows as $row) {
            $categoryIDs = array_merge($categoryIDs, $row["categoryIDs"]);
            $tagIDs = array_merge($tagIDs, $row["tagIDs"] ?? []);
        }
        $categoryIDs = array_unique($categoryIDs);
        $tagIDs = array_unique($tagIDs);

        return [$categoryIDs, $tagIDs];
    }

    /**
     * Applies filters against a result set and returns the filtered array.
     *
     * @param $rows
     * @param array $filters
     * @return array
     */
    private function matchFilters($rows, array $filters = []): array
    {
        return array_values(
            array_filter($rows, function ($row) use ($filters) {
                foreach (["categoryIDs", "tagIDs"] as $field) {
                    if (isset($filters[$field]) && empty(array_intersect($row[$field] ?? [], $filters[$field]))) {
                        return false;
                    }
                }

                if (isset($filters["profileFields"])) {
                    $profileFieldMapping = $row["profileFieldMapping"] ?? [];
                    if (empty(array_intersect_key($profileFieldMapping, array_flip($filters["profileFields"])))) {
                        return false;
                    }
                }

                if (isset($filters["isDefault"]) && $filters["isDefault"] !== $row["isDefault"]) {
                    return false;
                }

                if (isset($filters["name"]) && !str_contains($row["name"], $filters["name"])) {
                    return false;
                }

                return true;
            })
        );
    }

    /**
     * Returns true if the interest row contains a profile field mapping matching the given user profile fields.
     *
     * @param array $row An interest record.
     * @param array $profileFieldValues Profile fields for a specific user.
     * @return bool
     */
    private function hasProfileFieldValues(array $row, array $profileFieldValues): bool
    {
        $activeProfileFields = $this->profileFieldModel->getEnabledProfileFieldsIndexed();
        $profileFieldMapping = $row["profileFieldMapping"] ?? [];
        foreach ($profileFieldMapping as $name => $mapping) {
            if (!isset($activeProfileFields[$name])) {
                // Only consider enabled profile fields.
                continue;
            }

            $profileFieldValue = $profileFieldValues[$name] ?? null;
            $dataType = $activeProfileFields[$name]["dataType"];

            $mapping = is_array($mapping) ? $mapping : [$mapping];
            foreach ($mapping as $value) {
                switch ($dataType) {
                    case "boolean":
                        // Special handling for boolean (checkbox) fields.
                        $value = in_array($value, ["true", true], true);
                        if ($value === true && !$profileFieldValue) {
                            // User profile field value is false or doesn't exist.
                            return false;
                        }
                        if ($value === false && $profileFieldValue === true) {
                            // User profile field value is true.
                            return false;
                        }
                        break;
                    default:
                        $profileFieldValue = is_array($profileFieldValue) ? $profileFieldValue : [$profileFieldValue];
                        if (empty(array_intersect($profileFieldValue, $mapping))) {
                            return false;
                        }
                }
            }
        }
        return true;
    }

    /**
     * Applies common filter conditions to the query.
     *
     * @param \Gdn_MySQLDriver $sql
     * @param array $where
     * @return void
     */
    private function applyFiltersToQuery(\Gdn_MySQLDriver $sql, array $where = []): void
    {
        if (!empty($where["categoryIDs"])) {
            $joinedCategoryIDs = implode(",", array_map("intval", $where["categoryIDs"]));
            $sql->where("JSON_OVERLAPS(categoryIDs, '[$joinedCategoryIDs]')", null, false, false);
            unset($where["categoryIDs"]);
        }

        if (!empty($where["tagIDs"])) {
            $joinedTagIDs = implode(",", array_map("intval", $where["tagIDs"]));
            $sql->where("JSON_OVERLAPS(tagIDs, '[$joinedTagIDs]')", null, false, false);
            unset($where["tagIDs"]);
        }

        if (!empty($where["profileFields"])) {
            $sanitizeKey = fn($value) => "'$.\"" . trim($sql->quote($value), "'") . "\"'";
            $profileFields = array_map($sanitizeKey, $where["profileFields"]);
            $joinedProfileFields = implode(",", $profileFields);
            $sql->where("JSON_CONTAINS_PATH(profileFieldMapping, 'one',  $joinedProfileFields)", null, false, false);
            unset($where["profileFields"]);
        }

        if (isset($where["name"])) {
            $sql->like("name", $where["name"], "right");
            unset($where["name"]);
        }

        unset($where["page"], $where["limit"]);
        $sql->where($where);
    }

    /**
     * Applies validator callbacks to input schemas.
     *
     * @param Schema $schema
     * @return Schema
     */
    public function applyValidators(Schema $schema, ?int $id = null): Schema
    {
        $schema
            ->addValidator("profileFieldMapping", function ($profileFieldMapping, ValidationField $field) {
                $enabledProfileFields = $this->profileFieldModel->getEnabledProfileFieldsIndexed();
                $unknownProfileFields = array_diff(array_keys($profileFieldMapping), array_keys($enabledProfileFields));
                if (!empty($unknownProfileFields)) {
                    $field->addError("Profile fields not found: " . implode(", ", $unknownProfileFields));
                }
            })
            ->addValidator("categoryIDs", function ($categories, ValidationField $field) {
                $unknownCategories = array_diff($categories, array_keys(CategoryModel::categories()));
                if (!empty($unknownCategories)) {
                    $field->addError("Categories not found: " . implode(", ", $unknownCategories));
                }
            })
            ->addValidator("tagIDs", function ($tags, ValidationField $field) {
                $foundTags = $this->tagModel->getTagsByIDs($tags, false);
                $unknownTags = array_diff($tags, array_column($foundTags, "TagID"));
                if (!empty($unknownTags)) {
                    $field->addError("Tags not found: " . implode(", ", $unknownTags));
                }
            })
            ->addValidator("apiName", function ($apiName, ValidationField $field) {
                if (preg_match("/[.\s\/]/", $apiName)) {
                    $field->addError("Whitespace, slashes, and periods are not allowed");
                }
                $apiFilterMiddleware = \Gdn::getContainer()->get(ApiFilterMiddleware::class);
                if (in_array(strtolower($apiName), $apiFilterMiddleware->getBlacklistFields())) {
                    $field->addError("The value \"$apiName\" is not allowed for this field");
                }
            })
            ->addValidator(
                "apiName",
                $this->createUniqueFieldValidator(
                    "This interest API name is already in use. Use a unique API name.",
                    $id
                )
            )
            ->addValidator(
                "name",
                $this->createUniqueFieldValidator("This interest name is already in use. Use a unique name.", $id)
            );
        return $schema;
    }

    /**
     * Validator that checks if the table already contains a record with the given field value.
     *
     * @return \Closure
     */
    public function createUniqueFieldValidator(string $errorMessage, ?int $id = null): \Closure
    {
        return function ($value, ValidationField $field) use ($errorMessage, $id) {
            $where = [
                $field->getName() => $value,
                "isDeleted <>" => 1,
            ];

            if (!empty($id)) {
                $where["interestID <>"] = $id;
            }

            $count = $this->createSql()->getCount($this->getTable(), $where);
            if ($count !== 0) {
                $field->addError($errorMessage);
            }
        };
    }

    /**
     * Structures the interest table.
     *
     * @param \Gdn_DatabaseStructure $structure
     * @param bool $explicit
     * @param bool $drop
     * @return void
     * @throws \Exception
     */
    public static function structure(
        \Gdn_DatabaseStructure $structure,
        bool $explicit = false,
        bool $drop = false
    ): void {
        $structure
            ->table("interest")
            ->primaryKey("interestID")
            ->column("name", "varchar(100)")
            ->column("apiName", "varchar(100)")
            ->column("profileFieldMapping", "json", true)
            ->column("categoryIDs", "json", true)
            ->column("tagIDs", "json", true)
            ->column("isDefault", "tinyint", 0)
            ->column("isDeleted", "tinyint", 0)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->set($explicit, $drop);
    }
}
