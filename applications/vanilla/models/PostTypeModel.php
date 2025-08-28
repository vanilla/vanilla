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
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Database\Operation\BooleanFieldProcessor;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\CurrentUserFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\FeatureFlagHelper;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;

class PostTypeModel extends FullRecordCacheModel
{
    const FEATURE_POST_TYPES_AND_POST_FIELDS = "customLayout.createPost";

    const FEATURE_POST_TYPES = self::FEATURE_POST_TYPES_AND_POST_FIELDS; // Shorter alias

    const LEGACY_TYPE_MAP = [
        "Discussion" => "discussion",
        "Question" => "question",
        "Idea" => "idea",
        "Poll" => "poll",
    ];

    /**
     * D.I.
     */
    public function __construct(
        private \RoleModel $roleModel,
        private EventManager $eventManager,
        private PostFieldModel $postFieldModel,
        private \CategoryModel $categoryModel,
        \Gdn_Cache $cache
    ) {
        parent::__construct("postType", $cache, [
            \Gdn_Cache::FEATURE_EXPIRY => 60 * 60, // 1 hour.
        ]);

        $this->addPipelineProcessor(new CurrentDateFieldProcessor(["dateInserted", "dateUpdated"], ["dateUpdated"]));
        $this->addPipelineProcessor(new BooleanFieldProcessor(["isOriginal", "isActive", "isDeleted"]));
        $this->addPipelineProcessor(new JsonFieldProcessor(["roleIDs", "categoryIDs"], 0));
        $userProcessor = new CurrentUserFieldProcessor(\Gdn::session());
        $userProcessor->setInsertFields(["insertUserID", "updateUserID"])->setUpdateFields(["updateUserID"]);
        $this->addPipelineProcessor($userProcessor);
        $this->addPipelineProcessor(new JsonFieldProcessor(["attributes"]));
    }

    /**
     * Where on parent post type for GDN_Discussion.
     *
     * @param \Gdn_SQLDriver $sql
     * @param array|string $whereType The post type to filter by. Example ['Discussion', 'Question']
     * @param string $prefix
     *
     * @return void
     */
    public static function whereParentPostType(\Gdn_SQLDriver &$sql, string|array $whereType, string $prefix = ""): void
    {
        if (!is_array($whereType)) {
            $whereType = [$whereType];
        }

        // Just in case, since the base type is always stored with an uppercase first letter, but often presented all lowercase
        $whereType = array_map("ucfirst", $whereType);

        $typeField = "{$prefix}Type";
        $sql->beginWhereGroup();
        if (in_array("Discussion", $whereType)) {
            $whereType[] = "";
            $sql->where("$typeField IS NULL")->orOp();
        }
        $sql->where($typeField, $whereType);

        $sql->endWhereGroup();
    }

    /**
     * Query post types with filters.
     *
     * @param array $where
     * @param array $options
     * @return array
     * @throws \Exception
     */
    public function getWhere(array $where = [], array $options = []): array
    {
        $where[] = new CallbackWhereExpression(function (\Gdn_SQLDriver $sql) {
            $baseTypes = $this->getAvailableBasePostTypes();
            $sql->beginWhereGroup()
                ->where("postTypeID", $baseTypes)
                ->orWhere("parentPostTypeID", $baseTypes)
                ->endWhereGroup();
        });
        $rows = $this->select($where, $options);
        $this->normalizeRows($rows);
        $this->joinCategories($rows);
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
     * Join implicitly associated category counts.
     *
     * @param array $rows
     * @return void
     */
    private function joinCategories(array &$rows): void
    {
        $associations = $this->getCategoryAssociations();
        $associationsByPostTypeID = ArrayUtils::arrayColumnArrays($associations, "categoryID", "postTypeID");
        $explicitCategoryIDs = array_column($associations, "categoryID");
        $allCategories = $this->categoryModel->getVisibleCategories(["forceArrayReturn" => true]);
        $implicitCategoryIDs = array_diff(array_column($allCategories, "CategoryID"), $explicitCategoryIDs);

        $postableCategories = array_filter(
            $this->categoryModel->getVisibleCategories([
                "filterDiscussionsAdd" => true,
                "forceArrayReturn" => true,
            ]),
            fn($category) => $category["CategoryID"] === -1 || strtolower($category["DisplayAs"]) === "discussions"
        );
        $postableCategoryIDs = array_column($postableCategories, "CategoryID");

        // Allow addons to modify the allowed categories.
        [$implicitCategoryIDs, $postableCategoryIDs] = $this->eventManager->fireFilter(
            "postTypeModel_getAdditionalCategoryIDs",
            [$implicitCategoryIDs, $postableCategoryIDs]
        );

        foreach ($rows as &$row) {
            if (!empty($row["categoryIDs"])) {
                // If we have strict category restrictions from the post type.
                $row["availableCategoryIDs"] = $row["categoryIDs"];
            } else {
                // All categories that have an explicit association with the post type
                // plus all categories that have no explicit associations with any post type.
                $row["availableCategoryIDs"] = array_merge(
                    $associationsByPostTypeID[$row["postTypeID"]] ?? [],
                    $implicitCategoryIDs
                );
            }
            $row["postableCategoryIDs"] = array_values(
                array_intersect($row["availableCategoryIDs"], $postableCategoryIDs)
            );
            $row["countCategories"] = count(array_filter($row["postableCategoryIDs"], fn($cat) => $cat > -1));
        }
    }

    /**
     * Query and return post type/category associations all at once.
     *
     * @return array
     * @throws \Exception
     */
    public function getCategoryAssociations(array $where = []): array
    {
        return $this->modelCache->getCachedOrHydrate(
            [
                "function" => __FUNCTION__, // For uniqueness.
                "where" => $where,
            ],
            function () use ($where) {
                return $this->createSql()
                    ->select()
                    ->from("postTypeCategoryJunction")
                    ->where($where)
                    ->get()
                    ->resultArray();
            }
        );
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
            $row["roleIDs"] = $row["roleIDs"] ?? [];
            $row["categoryIDs"] = $row["categoryIDs"] ?? [];
            $row["baseType"] = $this->resolveBasePostType($row);
            $row["postButtonIcon"] = $row["attributes"]["postButtonIcon"] ?? null;
            unset($row["attributes"]);
        }
    }

    /**
     * Given a postTypeID or a row, resolve the base post type. Throws if we need to fetch a post type and it's not found.
     *
     * @param array|string $rowOrPostTypeID
     * @return string
     *
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function resolveBasePostType(array|string $rowOrPostTypeID): string
    {
        $row = is_array($rowOrPostTypeID) ? $rowOrPostTypeID : $this->selectSingle(["postTypeID" => $rowOrPostTypeID]);

        return $row["isOriginal"] ? $row["postTypeID"] : $row["parentPostTypeID"];
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
            "availableCategoryIDs",
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
            ->merge(Schema::parse(["postTypeID:s", "parentPostTypeID:s", "postButtonLabel:s"]))
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
        return $this->commonSchema()->merge(Schema::parse(["postButtonLabel:s?" => ["minLength" => 1]]));
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
        if (!$hasExisting) {
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
            ->column("categoryIDs", "json", true)
            ->column("dateInserted", "datetime")
            ->column("dateUpdated", "datetime", true)
            ->column("insertUserID", "int")
            ->column("updateUserID", "int", true)
            ->column("attributes", "json", true)
            ->set(true);

        // Stores allowed post type IDs for each category.
        $structure
            ->table("postTypeCategoryJunction")
            ->primaryKey("postTypeCategoryJunctionID")
            ->column("categoryID", "int", keyType: "index")
            ->column("postTypeID", "varchar(100)", keyType: "index")
            ->set();

        // Add default post types.
        if (!$structure->CaptureOnly) {
            // todo: Remove following conditional block after 003 release deployed to all sites.
            if (!\Gdn::config("postTypes.2025_003.wasMigrated")) {
                \Gdn::database()
                    ->createSql()
                    ->truncate("postType");
                if ($structure->tableExists("postMeta")) {
                    \Gdn::database()
                        ->createSql()
                        ->truncate("postMeta");
                }
                \Gdn::getContainer()
                    ->get(PostTypeModel::class)
                    ->clearCache();

                \Gdn::config()->saveToConfig("postTypes.2025_003.wasMigrated", true);
            }

            // todo: Remove following conditional block after 003 release deployed to all sites.
            // Migrate category associations. Specifically "discussion", "idea", "question", and "poll".
            if (!\Gdn::config("postTypes.2025_003.wasMigratedV2")) {
                $categories = \CategoryModel::categories();

                foreach ($categories as $category) {
                    $permissionCategory = \CategoryModel::permissionCategory($category);

                    // Note: A regression may have caused new categories to be saved without storing the
                    // permissionCategoryID as itself or an ancestor. So there may be categories created before the
                    // bug that has the correct permission category, and new categories where allowed discussion types
                    // are stored in the category record itself.
                    if (
                        $permissionCategory["CategoryID"] > -1 &&
                        is_array($permissionCategory["AllowedDiscussionTypes"])
                    ) {
                        $allowedDiscussionTypes = $permissionCategory["AllowedDiscussionTypes"];
                    } elseif (is_array($category["AllowedDiscussionTypes"])) {
                        $allowedDiscussionTypes = $category["AllowedDiscussionTypes"];
                    } else {
                        continue;
                    }

                    $types = array_filter(
                        $allowedDiscussionTypes,
                        fn($type) => is_string($type) &&
                            in_array(strtolower($type), ["discussion", "idea", "question", "poll"], true)
                    );
                    $types = array_map("strtolower", $types);

                    $postTypeModel = \Gdn::getContainer()->get(self::class);
                    $postTypeModel->putPostTypesForCategory($category["CategoryID"], $types, true);
                    $postTypeModel->clearCache();
                }

                \Gdn::config()->saveToConfig("postTypes.2025_003.wasMigratedV2", true);
            }

            self::createInitialPostTypes();
        }
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
            "postTypeID" => "discussion",
            "name" => "Discussion",
            "postButtonLabel" => "New Discussion",
            "layoutViewType" => "discussion",
            "isOriginal" => true,
            "isActive" => true,
            "attributes" => [
                "postButtonIcon" => "create-discussion",
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
     * Get allowed categories for the given postTypeID.
     *
     * @param string $postTypeID
     * @param string $status One of "active", "inactive", or "both".
     * @return array
     */
    public function getAllowedCategoryIDsByPostTypeID(string $postTypeID, string $status = "active"): array
    {
        $allowedPostTypes = $this->getAllowedPostTypes([
            "isActive" => match ($status) {
                "active" => [true],
                "inactive" => [false],
                "both" => [true, false],
            },
        ]);
        $postType = array_find($allowedPostTypes, fn($item) => $item["postTypeID"] === $postTypeID);
        return $postType["postableCategoryIDs"] ?? [];
    }

    /**
     * Gets explicitly associated post types for a given category ID.
     *
     * @param int $categoryID
     * @return array
     * @throws \Exception
     */
    public function getPostTypesByCategoryID(int $categoryID): array
    {
        $postTypes = $this->getAvailablePostTypes();
        $categoryPostTypes = self::indexPostTypesByCategory($postTypes);
        return $categoryPostTypes[$categoryID] ?? [];
    }

    /**
     * Override insert method to save associated record data.
     *
     * {@inheritdoc}
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
        if (isset($body["postFieldIDs"])) {
            $this->putPostFieldsForPostType($postTypeID, $body["postFieldIDs"]);
        }
    }

    /**
     * Save allowed post types for a given category.
     *
     * @param int $categoryID
     * @param string[] $postTypeIDs
     * @param bool $addOnly Whether to only add new associations for a category or replace all.
     * @param array $removePostTypeIDs Post type IDs that should be removed and not replaced. Only if addOnly is true.
     * @return void
     * @throws \Throwable
     */
    public function putPostTypesForCategory(
        int $categoryID,
        array $postTypeIDs,
        bool $addOnly = false,
        array $removePostTypeIDs = []
    ): void {
        $this->database->runWithTransaction(function () use ($categoryID, $postTypeIDs, $addOnly, $removePostTypeIDs) {
            if ($addOnly) {
                $this->createSql()->delete("postTypeCategoryJunction", [
                    "categoryID" => $categoryID,
                    "postTypeID" => array_merge($postTypeIDs, $removePostTypeIDs),
                ]);
            } else {
                $this->createSql()->delete("postTypeCategoryJunction", ["categoryID" => $categoryID]);
            }

            $rows = [];
            foreach ($postTypeIDs as $postTypeID) {
                $rows[] = [
                    "categoryID" => $categoryID,
                    "postTypeID" => $postTypeID,
                ];
            }

            $this->createSql()->insert("postTypeCategoryJunction", $rows);
        });

        $this->clearCache();
    }

    /**
     * Put associations between post fields and post types.
     *
     * @param string $postTypeID
     * @param array $postFieldIDs
     * @param bool $addOnly
     * @return void
     */
    public function putPostFieldsForPostType(string $postTypeID, array $postFieldIDs, bool $addOnly = false): void
    {
        $sql = $this->createSql();

        if ($addOnly) {
            $sql->delete("postTypePostFieldJunction", ["postTypeID" => $postTypeID, "postFieldID" => $postFieldIDs]);
            $sort = $this->postFieldModel->getMaxSort($postTypeID);
        } else {
            $sql->delete("postTypePostFieldJunction", ["postTypeID" => $postTypeID]);
            $sort = 0;
        }

        foreach ($postFieldIDs as $postFieldID) {
            $sql->insert("postTypePostFieldJunction", [
                "postFieldID" => $postFieldID,
                "postTypeID" => $postTypeID,
                "sort" => $sort++,
            ]);
        }
        $this->postFieldModel->clearCache();
    }

    /**
     * Return possible base post types including types from enabled addons.
     *
     * @return string[]
     */
    public function getAvailableBasePostTypes(): array
    {
        $baseTypes = ["discussion"];

        return $this->eventManager->fireFilter("PostTypeModel_getAvailableBasePostTypes", $baseTypes);
    }

    /**
     * Get a list of all available post types.
     *
     * @param array $where
     * @return array
     * @throws \Exception
     */
    public function getAvailablePostTypes(array $where = []): array
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
     * Get a list of allowed post types filtered by role and category.
     *
     * @param array $category A raw category record from the database.
     * @return array|null
     * @throws \Exception
     */
    public function getAllowedPostTypesByCategory(array $category): ?array
    {
        $postTypes = $this->getAllowedPostTypes();

        return array_filter($postTypes, function ($postType) use ($category) {
            $categoryID = $category["CategoryID"] ?? ($category["categoryID"] ?? null);
            if (!in_array($categoryID, $postType["availableCategoryIDs"])) {
                return false;
            }

            return true;
        });
    }

    /**
     * Get all post types the current user is able to post.
     *
     * @param array $where Additional filters.
     * @return array
     * @throws \Exception
     */
    public function getAllowedPostTypes(array $where = []): array
    {
        $postTypes = $this->getAvailablePostTypes($where);
        $currentUserRoles = \Gdn::userModel()->getRoleIDs(\Gdn::session()->UserID);

        if (\Gdn::session()->checkPermission("settings.manage")) {
            // Don't need to do any role filtering with this permission.
            return $postTypes;
        }

        return array_filter($postTypes, function ($postType) use ($currentUserRoles) {
            if (!empty($postType["roleIDs"]) && empty(array_intersect($currentUserRoles, $postType["roleIDs"]))) {
                return false;
            }
            return true;
        });
    }

    /**
     * Takes an array of normalized post types and pivots around the categoryIDs array,
     * returning an array of post types indexed by the categoryID it is associated with.
     *
     * @param array $postTypes
     * @return array
     */
    public static function indexPostTypesByCategory(array $postTypes): array
    {
        $result = [];
        foreach ($postTypes as $postType) {
            foreach ($postType["availableCategoryIDs"] as $categoryID) {
                $result[$categoryID][] = $postType;
            }
        }
        return $result;
    }
}
