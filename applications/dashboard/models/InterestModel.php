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
use Gdn;
use Gdn_SQLDriver;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\Model;
use Vanilla\Models\PipelineModel;
use Vanilla\Web\ApiFilterMiddleware;

class InterestModel extends PipelineModel
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
        private \UserMetaModel $userMetaModel
    ) {
        parent::__construct("interest");

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
        $rows = $this->getWhere(["interestID" => $id], [Model::OPT_LIMIT => 1]);
        return $rows[0] ?? null;
    }

    /**
     * Returns base query for fetching records.
     *
     * @return \Gdn_SQLDriver
     */
    public function getWhereQuery(): \Gdn_SQLDriver
    {
        $sql = $this->createSql()->from($this->getTable());

        $sql->where("isDeleted", 0);

        return $sql;
    }

    /**
     * Query interests with filters.
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getWhere(array $where, array $options = []): array
    {
        $sql = $this->getWhereQuery();

        $this->applyFiltersToQuery($sql, $where);

        $sql->applyModelOptions($options);

        $rows = $sql->get()->resultArray();

        $rows = $this->normalizeRows($rows);

        $this->joinTags($rows);
        $this->joinCategories($rows);
        $this->joinProfileFields($rows);

        return $rows;
    }

    /**
     * @param array $rows
     * @return void
     */
    protected function joinTags(array &$rows): void
    {
        $tags = array_column($rows, "tagIDs");
        $allTagIDs = array_unique(array_merge(...$tags));
        $tags = $this->tagModel->getTagsByIDs($allTagIDs);
        $tags = array_column($tags, "FullName", "TagID");

        foreach ($rows as &$row) {
            $row["tags"] = [];
            foreach ($row["tagIDs"] as $tagID) {
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
     * Query interests count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        $sql = $this->getWhereQuery();

        $this->applyFiltersToQuery($sql, $where);

        return $sql->getPagingCount("interestID");
    }

    /**
     * This returns either default interests or interests matching profile fields.
     *
     * @param array $profileFieldValues
     * @return array
     */
    private function getByProfileFieldValues(array $profileFieldValues): array
    {
        $sql = $this->getWhereQuery();

        $sql->beginWhereGroup();
        $sql->where("isDefault", 1);

        if (!empty($profileFieldValues)) {
            $profileFieldValuesJson = $sql->quote(json_encode($profileFieldValues));
            $sql->orWhere("JSON_OVERLAPS(profileFieldMapping, $profileFieldValuesJson)", null, false, false);
        }

        $sql->endWhereGroup();

        $rows = $sql->get()->resultArray();
        return $this->normalizeRows($rows);
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
            $tagIDs = array_merge($tagIDs, $row["tagIDs"]);
        }
        $categoryIDs = array_unique($categoryIDs);
        $tagIDs = array_unique($tagIDs);

        return [$categoryIDs, $tagIDs];
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
     * Normalizes data for display.
     *
     * @param array $rows
     * @return array
     */
    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            $row["profileFieldMapping"] = $row["profileFieldMapping"] ?? [];
            if (is_string($row["profileFieldMapping"])) {
                $row["profileFieldMapping"] = json_decode($row["profileFieldMapping"], true);
            }
            $row["categoryIDs"] = $row["categoryIDs"] ?? [];
            if (is_string($row["categoryIDs"])) {
                $row["categoryIDs"] = json_decode($row["categoryIDs"], true);
            }
            $row["tagIDs"] = $row["tagIDs"] ?? [];
            if (is_string($row["tagIDs"])) {
                $row["tagIDs"] = json_decode($row["tagIDs"], true);
            }
        }
        return $rows;
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
