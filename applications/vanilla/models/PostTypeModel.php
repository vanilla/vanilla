<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Forum\Models;

use Garden\EventManager;
use Garden\Schema\Invalid;
use Garden\Schema\Schema;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ClientException;
use Vanilla\Addon;
use Vanilla\AddonManager;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\InsertProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\PipelineModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;

class PostTypeModel extends PipelineModel
{
    const FEATURE_POST_TYPES_AND_POST_FIELDS = "customLayout.createPost";

    const FEATURE_POST_TYPES = self::FEATURE_POST_TYPES_AND_POST_FIELDS; // Shorter alias

    const LEGACY_TYPE_MAP = [
        "Discussion" => "discussion",
        "Question" => "question",
        "Idea" => "idea",
    ];

    /**
     * D.I.
     */
    public function __construct(
        private \RoleModel $roleModel,
        private EventManager $eventManager,
        private PostFieldModel $postFieldModel
    ) {
        parent::__construct("postType");

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isOriginal", "isActive", "isDeleted"]));
        $this->addPipelineProcessor(new JsonFieldProcessor(["roleIDs"], 0));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["attributes"]));
    }

    /**
     * @param array $types
     * @return array
     */
    public static function prepareTypeArray(array $types): array
    {
        return array_map("strtolower", $types);
    }

    /**
     * Add join to the query for post types.
     * @param \Gdn_SQLDriver $sql
     * @param array $where
     * @param string $joinType
     * @return void
     */
    public static function addJoin(\Gdn_SQLDriver &$sql, array $whereType = null, string $joinType = ""): void
    {
        $sql->join(
            "postType pt",
            "d.postTypeID = pt.postTypeID and pt.isActive=true and pt.isDeleted = false",
            $joinType
        );
        if (is_array($whereType)) {
            $whereType = PostTypeModel::prepareTypeArray($whereType);
            $sql->beginWhereGroup()
                ->where("pt.postTypeID", $whereType)
                ->orWhere("pt.parentPostTypeID", $whereType)
                ->endWhereGroup();
        }
    }

    /**
     * Get base query for querying post types.
     *
     * @param array $where
     * @return \Gdn_SQLDriver
     */
    private function getWhereQuery(array $where): \Gdn_SQLDriver
    {
        $baseTypes = $this->getAvailableBasePostTypes();

        $where = array_combine(array_map(fn($k) => str_contains($k, ".") ? $k : "pt.$k", array_keys($where)), $where);
        $sql = $this->createSql()
            ->select("pt.*")
            ->select("ptcj.categoryID", "JSON_ARRAYAGG", "categoryIDs")
            ->from("postType pt")
            ->leftJoin("postTypeCategoryJunction ptcj", "pt.postTypeId = ptcj.postTypeID")
            ->where($where)
            ->where(
                new CallbackWhereExpression(function (\Gdn_SQLDriver $sql) use ($baseTypes) {
                    $sql->beginWhereGroup()
                        ->where("pt.postTypeID", $baseTypes)
                        ->orWhere("pt.parentPostTypeID", $baseTypes)
                        ->endWhereGroup();
                })
            )
            ->groupBy("pt.postTypeID");

        return $sql;
    }

    /**
     * Query post types with filters.
     *
     * @param array $where
     * @param array $options
     * @return array|null
     * @throws \Exception
     */
    public function getWhere(array $where, array $options = [])
    {
        $sql = $this->getWhereQuery($where);

        $sql->applyModelOptions($options);

        $rows = $sql->get()->resultArray();
        $this->normalizeRows($rows);
        $this->joinParentPostTypes($rows);

        return $rows;
    }

    /**
     * Join parent post types with the record set by parentPostTypeID.
     *
     * @param array $rows
     * @return void
     */
    private function joinParentPostTypes(array &$rows): void
    {
        $postTypesIndexed = array_column($rows, null, "postTypeID");
        $fetch = function ($recordIDs) use ($postTypesIndexed) {
            $result = [];
            foreach ($recordIDs as $recordID) {
                if (isset($postTypesIndexed[$recordID])) {
                    $result[$recordID] = $postTypesIndexed[$recordID];
                }
            }
            return $result;
        };
        ModelUtils::leftJoin($rows, ["parentPostTypeID" => "parentPostType"], $fetch);
    }

    /**
     * @param array $rowsOrRow
     */
    public function normalizeRows(array &$rowsOrRow): void
    {
        if (ArrayUtils::isAssociative($rowsOrRow)) {
            $rows = [&$rowsOrRow];
        } else {
            $rows = &$rowsOrRow;
        }

        foreach ($rows as &$row) {
            $categoryIDs = array_filter(json_decode($row["categoryIDs"]));
            $row["categoryIDs"] = $categoryIDs;
            $row["countCategories"] = count($categoryIDs);
            $row["roleIDs"] = is_string($row["roleIDs"]) ? json_decode($row["roleIDs"]) : $row["roleIDs"];
            $row["isOriginal"] = (bool) $row["isOriginal"];
            $row["isActive"] = (bool) $row["isActive"];
            $row["isDeleted"] = (bool) $row["isDeleted"];
            $row["baseType"] = $row["isOriginal"] ? $row["postTypeID"] : $row["parentPostTypeID"];

            $attributes = is_string($row["attributes"]) ? json_decode($row["attributes"], true) : null;
            $row["postButtonIcon"] = $attributes["postButtonIcon"] ?? null;
            unset($row["attributes"]);
        }
    }

    /**
     * Query post type count with filters.
     *
     * @param array $where
     * @return int
     */
    public function getWhereCount(array $where): int
    {
        return $this->getWhereQuery($where)->getPagingCount("pt.postTypeID");
    }

    /**
     * Returns the schema for displaying post types.
     *
     * @return Schema
     */
    public function outputSchema(): Schema
    {
        $schema = Schema::parse([
            "postTypeID",
            "name",
            "parentPostTypeID",
            "isOriginal",
            "isActive",
            "isDeleted",
            "postButtonLabel",
            "postButtonIcon?",
            "postHelperText",
            "roleIDs",
            "categoryIDs",
            "countCategories",
            "dateInserted",
            "dateUpdated",
            "insertUserID",
            "updateUserID",
        ]);
        return $schema->merge(Schema::parse(["parentPostType?" => $schema]));
    }

    /**
     * Common schema for both post and patch requests.
     *
     * @return Schema
     */
    private function commonSchema(): Schema
    {
        $schema = Schema::parse([
            "name:s",
            "postButtonLabel:s?",
            "postHelperText:s?",
            "roleIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "categoryIDs:a?" => [
                "items" => [
                    "type" => "integer",
                ],
            ],
            "postFieldIDs:a?" => [
                "items" => [
                    "type" => "string",
                ],
            ],
            "isActive:b?" => ["default" => false],
            "isDeleted:b?" => ["default" => false],
        ])
            ->addValidator("categoryIDs", \CategoryModel::createCategoryIDsValidator())
            ->addValidator("roleIDs", [$this->roleModel, "roleIDsValidator"])
            ->addValidator("postFieldIDs", function ($postFieldIDs, ValidationField $field) {
                $existingPostFields = $this->postFieldModel->getWhere(["postFieldID" => $postFieldIDs]);
                $existingPostFieldIDs = array_column($existingPostFields, "postFieldID");
                $invalidPostFieldIDs = array_diff($postFieldIDs, $existingPostFieldIDs);
                if (!empty($invalidPostFieldIDs)) {
                    $field->addError("The following post fields are invalid: " . implode(",", $invalidPostFieldIDs));
                    return Invalid::value();
                }
                return true;
            });
        return $schema;
    }

    /**
     * Returns the schema for creating post types.
     *
     * @return Schema
     */
    public function postSchema(): Schema
    {
        $schema = $this->commonSchema()
            ->merge(Schema::parse(["postTypeID:s", "parentPostTypeID:s"]))
            ->merge($this->patchSchema())
            ->addValidator("postTypeID", function ($postTypeID, ValidationField $field) {
                if (preg_match("#[.\s/|A-Z]#", $postTypeID)) {
                    $field->addError("Whitespace, slashes, periods and uppercase letters are not allowed");
                    return Invalid::value();
                }
                return true;
            })
            ->addValidator("postTypeID", $this->createUniquePostTypeValidator())
            ->addValidator("parentPostTypeID", function ($value, ValidationField $field) {
                $postType = $this->getWhere(["postTypeID" => $value, "isOriginal" => true], [self::OPT_LIMIT => 1]);

                if (empty($postType)) {
                    $field->addError("The selected parent post type does not exist");
                    return Invalid::value();
                }
                return true;
            });

        return $schema;
    }

    /**
     * Returns the schema for updating post types.
     *
     * @return Schema
     */
    public function patchSchema(): Schema
    {
        return $this->commonSchema();
    }

    /**
     * Validator that checks if the table already contains a record with the given field value.
     *
     * @return callable
     */
    public function createUniquePostTypeValidator(): callable
    {
        return function ($value, ValidationField $field) {
            $postType = $this->select(["postTypeID" => $value], [self::OPT_LIMIT => 1])[0] ?? null;

            if (!empty($postType)) {
                $field->addError(
                    $postType["isDeleted"]
                        ? "This identifier is already used by a deleted post type."
                        : "This identifier is already used. Use a unique identifier."
                );
                return Invalid::value();
            }
            return true;
        };
    }

    /**
     * Create an initial post type if it doesn't exist.
     *
     * @todo At the moment this method replaces (inserts or updates). Later we will only want to insert if the
     *   post type does not exist to keep customizations of the base types.
     * @param array $row
     *
     * @return void
     */
    public function createInitialPostType(array $row): void
    {
        if ($this->database->structure()->CaptureOnly) {
            return;
        }

        $hasExisting = $this->createSql()->getCount($this->getTable(), ["postTypeID" => $row["postTypeID"]]) > 0;
        if ($hasExisting) {
            $this->update($row, ["postTypeID" => $row["postTypeID"]]);
        } else {
            $this->insert($row);
        }
    }

    /**
     * Structures the postType table.
     *
     * @param \Gdn_MySQLStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table("postType")
            ->primaryKey("postTypeID", "varchar(100)", false)
            ->column("name", "varchar(100)")
            ->column("parentPostTypeID", "varchar(100)", true, "index")
            ->column("postButtonLabel", "varchar(100)", true)
            ->column("postHelperText", "varchar(100)", true)
            ->column("layoutViewType", "varchar(100)", true)
            ->column("isOriginal", "tinyint", 0)
            ->column("isSystemHidden", "tinyint", 0)
            ->column("isActive", "tinyint", 0)
            ->column("isDeleted", "tinyint", 0)
            ->column("roleIDs", "json", true)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->column("attributes", "json", true)
            ->set(true);

        // Add default post types.
        if (!$structure->CaptureOnly) {
            self::createInitialPostTypes();
        }

        // Stores allowed post type IDs for each category.
        $structure
            ->table("postTypeCategoryJunction")
            ->primaryKey("postTypeCategoryJunctionID")
            ->column("categoryID", "int", keyType: "index")
            ->column("postTypeID", "varchar(100)", keyType: "index")
            ->set();
    }

    /**
     * Create initial post types.
     *
     * @return void
     */
    private static function createInitialPostTypes(): void
    {
        $postTypeModel = \Gdn::getContainer()->get(PostTypeModel::class);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "redirect",
            "name" => "Redirect",
            "postButtonLabel" => "",
            "layoutViewType" => "",
            "isOriginal" => true,
            "isActive" => false,
            "isSystemHidden" => true,
            "attributes" => [],
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "page",
            "name" => "Page",
            "postButtonLabel" => "",
            "layoutViewType" => "",
            "isOriginal" => true,
            "isActive" => false,
            "isSystemHidden" => true,
            "attributes" => [],
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "discussion",
            "name" => "Discussion",
            "postButtonLabel" => "New Discussion",
            "layoutViewType" => "discussion",
            "isOriginal" => true,
            "isActive" => true,
            "attributes" => [
                "postButtonIcon" => "new-discussion",
            ],
        ]);

        $postTypeModel->createInitialPostType([
            "postTypeID" => "poll",
            "name" => "Poll",
            "isOriginal" => true,
            "layoutViewType" => "discussion",
            "isActive" => false,
            "attributes" => null,
        ]);
        $postTypeModel->createInitialPostType([
            "postTypeID" => "event",
            "name" => "Event",
            "layoutViewType" => "event",
            "isOriginal" => true,
            "isActive" => false,
            "attributes" => null,
        ]);
    }

    /**
     * Checks if the post types feature is enabled.
     *
     * @return void
     * @throws ClientException
     */
    public static function ensurePostTypesFeatureEnabled()
    {
        if (!self::isPostTypesFeatureEnabled()) {
            throw new ClientException("Post Types & Post Fields is not enabled.");
        }
    }

    /**
     * Checks if the post types feature is enabled.
     *
     * @return bool
     */
    public static function isPostTypesFeatureEnabled(): bool
    {
        return FeatureFlagHelper::featureEnabled(self::FEATURE_POST_TYPES_AND_POST_FIELDS);
    }

    /**
     * Convert array of legacy discussion types to post type IDs.
     *
     * @param array $types
     * @return array
     */
    public static function convertFromLegacyTypes(array $types): array
    {
        $result = [];
        foreach ($types as $type) {
            if (isset(self::LEGACY_TYPE_MAP[$type])) {
                $result[] = self::LEGACY_TYPE_MAP[$type];
            }
        }
        return $result;
    }

    /**
     * Returns a list of allowed post types indexed by associated category ID.
     *
     * @return array
     * @throws \Exception
     */
    public function getPostTypesByCategory(array $filters = []): array
    {
        $enabledPostTypes = $this->getWhere(["isDeleted" => false]);
        $enabledPostTypes = array_column($enabledPostTypes, "postTypeID");

        $sql = $this->createSql()
            ->select("ptcj.categoryID")
            ->select("ptcj.postTypeID", "JSON_ARRAYAGG", "postTypeIDs")
            ->from("postTypeCategoryJunction ptcj")
            ->where("ptcj.postTypeID", $enabledPostTypes)
            ->groupBy("ptcj.categoryID");

        $sql->where($filters);

        $rows = $sql->get()->resultArray();

        $rows = array_column($rows, "postTypeIDs", "categoryID");
        $rows = array_map("json_decode", $rows);

        return $rows;
    }

    /**
     * Override insert method to save associated record data.
     *
     * {@inheritDoc}
     */
    public function insert(array $set, array $options = [])
    {
        $return = parent::insert($set, $options);
        $this->saveAssociatedRecords($set["postTypeID"], $set);
        return $return;
    }

    /**
     * Update a single record by post type ID and save associated record data.
     *
     * @param string $postTypeID
     * @param array $body
     * @return void
     * @throws \Exception
     */
    public function updateByID(string $postTypeID, array $body): void
    {
        $this->update($body, ["postTypeID" => $postTypeID]);
        $this->saveAssociatedRecords($postTypeID, $body);
    }

    /**
     * Return a single record by post type ID.
     *
     * @param string $postTypeID
     * @return array|null
     * @throws \Exception
     */
    public function getByID(string $postTypeID): ?array
    {
        return $this->getWhere(["postTypeID" => $postTypeID], [self::OPT_LIMIT => 1])[0] ?? null;
    }

    /**
     * Save associated data for creating and updating post types.
     *
     * @param string $postTypeID
     * @param array $body
     * @return void
     */
    public function saveAssociatedRecords(string $postTypeID, array $body = []): void
    {
        if (isset($body["categoryIDs"])) {
            $this->putCategoriesForPostType($postTypeID, $body["categoryIDs"]);
        }
        if (isset($body["postFieldIDs"])) {
            $this->putPostFieldsForPostType($postTypeID, $body["postFieldIDs"]);
        }
    }

    /**
     * Save allowed post types for a given category.
     *
     * @param int $categoryID
     * @param string[] $postTypeIDs
     * @return void
     */
    public function putPostTypesForCategory(int $categoryID, array $postTypeIDs): void
    {
        $this->createSql()->delete("postTypeCategoryJunction", ["categoryID" => $categoryID]);

        $rows = [];
        foreach ($postTypeIDs as $postTypeID) {
            $rows[] = [
                "categoryID" => $categoryID,
                "postTypeID" => $postTypeID,
            ];
        }

        $this->createSql()->insert("postTypeCategoryJunction", $rows);
    }

    /**
     * Put associations between post fields and post types.
     *
     * @param string $postTypeID
     * @param array $postFieldIDs
     * @return void
     */
    public function putPostFieldsForPostType(string $postTypeID, array $postFieldIDs): void
    {
        $sql = $this->createSql();
        $sql->delete("postTypePostFieldJunction", ["postTypeID" => $postTypeID]);

        $rows = [];
        $sort = 0;
        foreach ($postFieldIDs as $postFieldID) {
            $rows[] = [
                "postFieldID" => $postFieldID,
                "postTypeID" => $postTypeID,
                "sort" => $sort++,
            ];
        }

        $sql->insert("postTypePostFieldJunction", $rows);
    }

    /**
     * Save all categories that allow the given post type.
     *
     * @param string $postTypeID
     * @param int[] $categoryIDs
     * @return void
     */
    public function putCategoriesForPostType(string $postTypeID, array $categoryIDs): void
    {
        $this->createSql()->delete("postTypeCategoryJunction", ["postTypeID" => $postTypeID]);

        $rows = [];
        foreach ($categoryIDs as $categoryID) {
            $rows[] = [
                "categoryID" => $categoryID,
                "postTypeID" => $postTypeID,
            ];
        }

        $this->database->runWithTransaction(function () use ($categoryIDs, $rows) {
            $this->createSql()
                ->update("Category")
                ->set("hasRestrictedPostTypes", true)
                ->where("CategoryID", $categoryIDs)
                ->put();

            $this->createSql()->insert("postTypeCategoryJunction", $rows);
        });
    }

    /**
     * Return possible base post types including types from enabled addons.
     *
     * @return mixed
     */
    private function getAvailableBasePostTypes(): array
    {
        $baseTypes = ["discussion"];

        return $this->eventManager->fireFilter("PostTypeModel_getAvailableBasePostTypes", $baseTypes);
    }

    /**
     * Get a list of all available post types.
     *
     * @param array $where
     * @return array|null
     * @throws \Exception
     */
    public function getAvailablePostTypes(array $where = []): ?array
    {
        return $this->getWhere(
            $where + [
                "isActive" => true,
                "isDeleted" => false,
                "isSystemHidden" => false,
            ]
        );
    }

    /**
     * Get a list of all available post types.
     *
     * @param array $where
     * @return array|null
     * @throws \Exception
     */
    public function getAvailableSystemPostTypes(array $where = []): ?array
    {
        return $this->getWhere(
            $where + [
                "isSystemHidden" => true,
            ]
        );
    }

    /**
     * Get a list of allowed post types filtered by role and category.
     *
     * @param array $category A raw category record from the database.
     * @return array|null
     * @throws \Exception
     */
    public function getAllowedPostTypes(array $category): ?array
    {
        $postTypes = $this->getAvailablePostTypes();
        $currentUserRoles = \Gdn::userModel()->getRoleIDs(\Gdn::session()->UserID);

        return array_filter($postTypes, function ($postType) use ($category, $currentUserRoles) {
            if (!empty($postType["roleIDs"]) && empty(array_intersect($currentUserRoles, $postType["roleIDs"]))) {
                return false;
            }

            $hasRestrictedPostTypes = $category["hasRestrictedPostTypes"] ?? false;
            if ($hasRestrictedPostTypes && !in_array($category["CategoryID"], $postType["categoryIDs"])) {
                return false;
            }

            return true;
        });
    }
}
