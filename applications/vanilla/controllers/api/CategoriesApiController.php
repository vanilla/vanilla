<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Schema\RangeExpression;
use Vanilla\Site\SiteSectionModel;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\TreeBuilder;

/**
 * API Controller for the `/categories` resource.
 */
class CategoriesApiController extends AbstractApiController
{
    /** @var CategoryModel */
    private $categoryModel;

    /** @var Schema */
    private $categoryPostSchema;

    /** @var  Schema */
    private $categorySchema;

    /** @var Schema */
    private $idParamSchema;

    /** @var BreadcrumbModel */
    private $breadcrumbModel;

    /** @var LongRunner */
    private $runner;

    public const OUTPUT_FORMAT_TREE = "tree";
    public const OUTPUT_FORMAT_FLAT = "flat";
    public const OUTPUT_FORMATS = [self::OUTPUT_FORMAT_TREE, self::OUTPUT_FORMAT_FLAT];

    /** @var string */
    const ERRORINDEXMSG = "The following fields: {page, limit, outputFormat=flat} is incompatible with {maxDepth, outputFormat=tree}";

    /** @var SiteSectionModel */
    private $siteSectionModel;

    /**
     * CategoriesApiController constructor.
     *
     * @param CategoryModel $categoryModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param LongRunner $runner
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        CategoryModel $categoryModel,
        BreadcrumbModel $breadcrumbModel,
        LongRunner $runner,
        SiteSectionModel $siteSectionModel
    ) {
        $this->categoryModel = $categoryModel;
        $this->breadcrumbModel = $breadcrumbModel;
        $this->runner = $runner;
        $this->siteSectionModel = $siteSectionModel;
    }

    /**
     * Get a category schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @param array $extra Additional fields to include.
     * @return Schema Returns a schema object.
     */
    public function categoryPostSchema($type = "", array $extra = [])
    {
        if ($this->categoryPostSchema === null) {
            $fields = [
                "name",
                "parentCategoryID?",
                "urlcode",
                "displayAs?",
                "customPermissions?",
                "description?",
                "featured?",
                "iconUrl?",
                "bannerUrl?",
                "pointsCategoryID?",
            ];
            $this->categoryPostSchema = $this->schema(
                Schema::parse(array_merge($fields, $extra))->add($this->schemaWithParent()),
                "CategoryPost"
            );
        }
        return $this->schema($this->categoryPostSchema, $type);
    }

    /**
     * Get the full category schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function categorySchema($type = "")
    {
        if ($this->categorySchema === null) {
            $this->categorySchema = $this->schema($this->fullSchema(), "Category");
        }
        return $this->schema($this->categorySchema, $type);
    }

    /**
     * Lookup a single category by its numeric ID or its URL code.
     *
     * @param int|string $id The category ID or URL code.
     * @throws NotFoundException if the category cannot be found.
     * @return array
     */
    public function category($id)
    {
        $category = CategoryModel::categories($id);
        if (empty($category)) {
            throw new NotFoundException("Category");
        }
        return $category;
    }

    /**
     * Delete a category.
     *
     * @param int $id The ID of the category.
     * @return Data Returns the result of the response.
     * @throws NotFoundException if the category cannot be found.
     * @throws ServerException if the category has its CanDelete flag set to false.
     * @throws ServerException if the category has children.
     */
    public function delete($id, array $query = []): Data
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema(
            [
                "batch:b?" => ["default" => false],
                "newCategoryID:i?",
            ],
            "in"
        );
        $out = $this->schema([], "out");

        $query = $in->validate($query);
        $row = $this->category($id);
        $children = $this->categoryModel->getChildTree($row["CategoryID"]);
        if (!$row["CanDelete"]) {
            throw new ServerException("The specified category cannot be deleted.", 500);
        }
        if (count($children) > 0) {
            throw new ServerException("Cannot delete categories with children.", 500);
        }

        $options = [];
        if ($query["batch"]) {
            $this->runner->setMode(LongRunner::MODE_ASYNC);
        }

        $deleteOptions = [];
        if (array_key_exists("newCategoryID", $query)) {
            $deleteOptions["newCategoryID"] = $query["newCategoryID"];
        }
        $args = [$id, $deleteOptions];

        $response = $this->runner->runApi(
            new LongRunnerAction(CategoryModel::class, "deleteIDIterable", $args, $options)
        );
        if ($response->getStatus() === 200) {
            $response->setStatus(204);
        }
        return $response;
    }

    /**
     * Get a schema instance comprised of all available category fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullSchema()
    {
        return $this->categoryModel->schema();
    }

    /**
     * Get a single category.
     *
     * @param int $id The ID of the category.
     * @param array $query
     *
     * @return array
     * @throws NotFoundException If unable to find the category.
     */
    public function get(int $id, array $query = [])
    {
        $query["id"] = $id;
        if (!$this->categoryModel::checkPermission($id, "Vanilla.Discussions.View")) {
            throw new ForbiddenException("Category");
        }

        $in = $this->idParamSchema()->setDescription("Get a category.");
        $query = $in->validate($query);
        $expand = $query["expand"];

        $out = $this->schema(
            CrawlableRecordSchema::applyExpandedSchema($this->schemaWithParent(), "category", $expand),
            "out"
        );

        $row = $this->category($id);
        $row = $this->normalizeOutput($row, $expand);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a category for editing.
     *
     * @param int $id The ID of the category.
     * @throws NotFoundException if unable to find the category.
     * @return array
     */
    public function get_edit($id)
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->idParamSchema()->setDescription("Get a category for editing.");
        $out = $this->schema(
            Schema::parse([
                "categoryID",
                "name",
                "parentCategoryID",
                "urlcode",
                "description",
                "displayAs",
                "iconUrl",
                "bannerUrl",
            ])->add($this->fullSchema()),
            "out"
        );

        $row = $this->category($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a user's preferences for a single category.
     *
     * @param int $id
     * @param int $userID
     * @return Data
     */
    public function get_preferences(int $id, int $userID): Data
    {
        if ($this->getSession()->UserID === $userID) {
            $permission = "Garden.SignIn.Allow";
        } else {
            $permission = ["Garden.Users.Edit", "Moderation.Profiles.Edit"];
        }
        $this->permission($permission);

        $out = $this->categoryModel->preferencesSchema();

        $this->category($id);

        $preferences = $this->categoryModel->getPreferencesByCategoryID($userID, $id);
        $result = $out->validate($preferences);

        return new Data($result);
    }

    /**
     * Search categories.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function get_search(array $query)
    {
        $this->permission();

        $in = $this->schema([
            "query:s" => [
                "description" => "Category name filter.",
                "minLength" => 0,
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "parentCategoryID:i?",
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => $this->categoryModel->getDefaultLimit(),
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(100),
            ],
            "expand?" => ApiUtils::getExpandDefinition(["parent", "breadcrumbs"]),
        ])->setDescription("Search categories.");
        $expand = $query["expand"] ?? [];
        $out = $this->schema([":a" => $this->schemaWithParent($expand)], "out");

        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $results = $this->categoryModel->searchByName(
            $query["query"],
            $query["parentCategoryID"] ?? null,
            $this->isExpandField("parent", $expand),
            $limit,
            $offset
        );

        foreach ($results as $key => $row) {
            $results[$key] = $this->normalizeOutput($row);
        }

        $result = $out->validate($results);

        $paging = ApiUtils::morePagerInfo($result, "/api/v2/categories/search", $query, $in);

        return new Data($result, ["paging" => $paging]);
    }

    /**
     * Get an ID-only category record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = "in")
    {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                    "id:i" => "The category ID.",
                    "expand?" => ApiUtils::getExpandDefinition([]),
                ]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * @return Schema Returns a schema object.
     */
    public function getIndexSchema(): Schema
    {
        return $this->schema(
            [
                "categoryID?" => \Vanilla\Schema\RangeExpression::createSchema([":int"]),
                "parentCategoryID:i?",
                "parentCategoryCode:s?",
                "followed:b?",
                "maxDepth:i?" => [
                    "description" => "",
                ],
                "archived:b|n" => [
                    "default" => null,
                ],
                "page:i?" => [
                    "minimum" => 1,
                    "maximum" => $this->categoryModel->getMaxPages(),
                ],
                "limit:i?" => [
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                ],
                "expand?" => ApiUtils::getExpandDefinition([]),
                "featured:b?",
                "dirtyRecords:b?",
                "outputFormat:s?" => [
                    "enum" => self::OUTPUT_FORMATS,
                ],
                "siteSectionID:s?" => [
                    "description" => 'Filter categories by site-section-id (subcommunity).
                     The subcommunityID or folder can be used.
                     The query looks like:
                     siteSectionID=$SubcommunityID:{id} ie. 1
                     siteSectionID=$SubcommunityID:{folder} ie. ',
                ],
            ],
            "in"
        )
            ->addValidator(
                "",
                \Vanilla\Utility\SchemaUtils::onlyOneOf(["categoryID", "parentCategoryID", "parentCategoryCode"])
            )
            ->setDescription("List categories.");
    }

    /**
     * Lookup the ParentCategory.
     *
     * @param array $query
     * @return array
     * @throws NotFoundException If unable to find the category.
     */
    public function getIndexParentCategoryID(array $query): array
    {
        if (array_key_exists("parentCategoryID", $query)) {
            $parent = $this->category($query["parentCategoryID"]);
        } elseif (array_key_exists("parentCategoryCode", $query)) {
            $parent = $this->category($query["parentCategoryCode"]);
        } elseif (!empty($query["siteSectionID"])) {
            $siteSection = $this->siteSectionModel->getByID($query["siteSectionID"]);
            $categoryID = $siteSection ? $siteSection->getCategoryID() : [];
            $parent = $categoryID ? $this->category($categoryID) : ["CategoryID" => 0];
        } else {
            $parent = [];
        }
        return $parent;
    }

    /**
     * Get the category format.
     *
     * @param array $query
     * @param array $parentCategory
     * @return string
     */
    public function getIndexFormat(array $query, array $parentCategory): string
    {
        if (array_key_exists("outputFormat", $query)) {
            return $query["outputFormat"];
        }

        // Check the parent category.
        if (array_key_exists("DisplayAs", $parentCategory) && $parentCategory["DisplayAs"] == "Flat") {
            return "flat";
        }

        $defaultToFlatKeys = ["categoryID", "dirtyRecords", "page", "limit", "followed", "featured"];

        $keys = array_keys($query);
        $flatKeysFound = array_intersect($keys, $defaultToFlatKeys);

        if (count($flatKeysFound) > 0) {
            return self::OUTPUT_FORMAT_FLAT;
        } else {
            return self::OUTPUT_FORMAT_TREE;
        }
    }

    /**
     * List categories.
     *
     * @param array $query The query string.
     * @param bool $filter Apply permission based filter
     * @return Data
     */
    public function index(array $query, bool $filter = true): Data
    {
        $this->permission();
        $in = $this->getIndexSchema();
        $query = $in->validate($query);
        $this->checkMixedQuery($query);

        $expand = $query["expand"];
        $out = $this->schema(
            [
                ":a" => CrawlableRecordSchema::applyExpandedSchema($this->schemaWithChildren(), "category", $expand),
            ],
            "out"
        );

        $parentCategory = $this->getIndexParentCategoryID($query);
        $format = $this->getIndexFormat($query, $parentCategory);
        $this->categoryModel->setJoinUserCategory(false);

        $page = isset($query["page"]) ? $query["page"] : 1;
        $limit = isset($query["limit"]) ? $query["limit"] : 30;
        [$offset, $limit] = offsetLimit("p{$page}", $limit);
        if ($format === self::OUTPUT_FORMAT_TREE) {
            $limit = "";
            $page = 1;
        }

        $where = [];
        $sort = "";

        if ($query["featured"] ?? false) {
            $where["Featured"] = $query["featured"] ? 1 : 0;
            $sort = "SortFeatured";
        }

        if ($query["dirtyRecords"] ?? false) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = true;
        }

        if ($format === "tree") {
            $maxDepth = $query["maxDepth"] ?? 2;
            $parentCategoryDepth = $parentCategory["Depth"] ?? 0;
            $maxDepth = $maxDepth + $parentCategoryDepth;
            $where["Depth <="] = $maxDepth;
        }

        /** @var RangeExpression $categoryIDs */
        $categoryIDs = $query["categoryID"] ?? new RangeExpression(">", 0);

        // Apply permission filtering.
        $visibleIDs = $this->categoryModel->getVisibleCategoryIDs();
        if ($visibleIDs === true) {
            $categoryIDs = $categoryIDs->withFilteredValue(">", 0);
        } else {
            $categoryIDs = $categoryIDs->withFilteredValue("=", $visibleIDs);
        }

        // Apply "followed" filtering.
        if ($query["followed"] ?? false) {
            $followedRecords = $this->categoryModel->getFollowed($this->getSession()->UserID);
            $followedIDs = array_column($followedRecords, "CategoryID");
            $categoryIDs = $categoryIDs->withFilteredValue("=", $followedIDs);
        }

        // Parent category filtering.
        if (!empty($parentCategory)) {
            $descendantIDs = $this->categoryModel->getCategoriesDescendantIDs([$parentCategory["CategoryID"]]);
            $categoryIDs = $categoryIDs->withFilteredValue("=", $descendantIDs);
        }

        $where["CategoryID"] = $categoryIDs;

        [$categories, $totalCountCallBack] = $this->getCategoriesWhere($where, $limit, $offset, $sort, $filter);

        // Filter tree by the category "archived" fields.
        if (!isset($query["followed"]) && $query["archived"] !== null) {
            $categories = $this->archiveFilter($categories, $query["archived"] ? 0 : 1);
        }

        foreach ($categories as &$category) {
            $category = $this->normalizeOutput($category, $expand);
        }
        $categories = $out->validate($categories);

        if ($format === "tree") {
            $categories = $this->treeNormalizedBuilder()
                ->setRootID($parentCategory["ParentCategoryID"] ?? null)
                ->buildTree($categories);
        } elseif ($sort === "") {
            $categories = $this->treeNormalizedBuilder()->sort($categories);
        }

        if (isset($totalCountCallBack) && $format === self::OUTPUT_FORMAT_FLAT) {
            $query["page"] = $page;
            $query["limit"] = $limit;
            $paging = ApiUtils::numberedPagerInfo($totalCountCallBack(), "/api/v2/categories", $query, $in);
        } else {
            $paging = [];
        }

        return new Data($categories, ["paging" => $paging]);
    }

    /**
     * Get a user's category preferences.
     *
     * @param int $userID
     * @return Data
     */
    public function index_preferences(int $userID): Data
    {
        if ($this->getSession()->UserID === $userID) {
            $permission = "Garden.SignIn.Allow";
        } else {
            $permission = ["Garden.Users.Edit", "Moderation.Profiles.Edit"];
        }
        $this->permission($permission);

        $out = $this->schema([
            ":a" => [
                "items" => $this->categoryModel->fragmentWithPreferencesSchema(),
            ],
        ]);

        $preferences = $this->categoryModel->getPreferences($userID);
        $result = $out->validate(array_values($preferences));
        return new Data($result);
    }

    /**
     * Recursively filter a list of categories by their archived flag.
     *
     * @param array $categories
     * @param int $archived
     * @return array
     */
    private function archiveFilter(array $categories, $archived)
    {
        $result = [];
        foreach ($categories as $index => $category) {
            // Discard, based on archived value.
            if (array_key_exists("Archived", $category) && $category["Archived"] === $archived) {
                continue;
            }
            // Process any children.
            if (!empty($category["Children"])) {
                $category["Children"] = $this->archiveFilter($category["Children"], $archived);
            }
            // If the category made it this far, include it.
            $result[] = $category;
        }
        return $result;
    }

    /**
     * Update a category.
     *
     * @param int $id The ID of the category.
     * @param array $body The request body.
     * @throws NotFoundException if unable to find the category.
     * @return array
     */
    public function patch($id, array $body)
    {
        $this->permission("Garden.Settings.Manage");

        $this->idParamSchema("in");
        $in = $this->categoryPostSchema("in", [
            "description",
            "parentCategoryID" => 'Parent category ID. Changing a category\'s parent will rebuild the category tree.',
        ])->setDescription("Update a category.");
        $out = $this->schemaWithParent(false, "out");

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->category($id);

        if (array_key_exists("parentCategoryID", $body)) {
            $this->updateParent($id, $body["parentCategoryID"]);
            unset($body["parentCategoryID"]);
        }

        if (!empty($body)) {
            if (array_key_exists("customPermissions", $body)) {
                $this->categoryModel->save([
                    "CategoryID" => $id,
                    "CustomPermissions" => $body["customPermissions"],
                ]);
                unset($body["customPermissions"]);
            }
            $categoryData = $this->normalizeInput($body);
            $this->categoryModel->setField($id, $categoryData);
        }

        $row = $this->category($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Set a user's category preferences.
     *
     * @param int $id
     * @param int $userID
     * @param array $body
     * @return Data
     * @throws \Vanilla\Exception\PermissionException Throws a permission exception in the following cases:
     * 1. the editing user is editing their own preferences, but does not have the SignIn.Allow permission.
     * 2. the editing user is trying to set another user's preferences, but does not have either the
     *      Garden.Users.Edit or the Moderation.Profiles.Edit permission.
     * 3. if opting for email notifications when the user in question doesn't have the Garden.Email.View permission.
     */
    public function patch_preferences(int $id, int $userID, array $body): Data
    {
        if ($this->getSession()->UserID === $userID) {
            $permission = "Garden.SignIn.Allow";
            $isSelfService = true;
        } else {
            $permission = ["Garden.Users.Edit", "Moderation.Profiles.Edit"];
            $isSelfService = false;
        }
        $this->permission($permission);

        $in = $this->schema([
            CategoryModel::PREFERENCE_KEY_NOTIFICATION,
            CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS,
        ])->add($this->categoryModel->preferencesSchema());
        $out = $this->categoryModel->preferencesSchema();

        $this->category($id);

        $body = $in->validate($body, true);

        // Make sure the user whose preferences are being changed has the Email.View permission if opting for email
        // notifications. Throw an error if they don't.
        if (
            isset($body[CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS]) &&
            $body[CategoryModel::PREFERENCE_KEY_USE_EMAIL_NOTIFICATIONS]
        ) {
            $hasEmailViewPerm = $isSelfService
                ? $this->getPermissions()->has("Garden.Email.View")
                : Gdn::getContainer()
                    ->get(UserModel::class)
                    ->getPermissions($userID)
                    ->has("Garden.Email.View");
            if (!$hasEmailViewPerm) {
                throw new \Vanilla\Exception\PermissionException("Garden.Email.View");
            }
        }

        $this->categoryModel->setPreferences($userID, $id, $body);

        $preferences = $this->categoryModel->getPreferencesByCategoryID($userID, $id);
        $result = $out->validate($preferences);
        return new Data($result);
    }

    /**
     * Add a category.
     *
     * @param array $body The request body.
     * @throws ServerException if the category could not be created.
     * @return array
     */
    public function post(array $body)
    {
        $this->permission("Garden.Settings.Manage");

        $in = $this->schema($this->categoryPostSchema(), "in")->setDescription("Add a category.");
        $out = $this->schema($this->schemaWithParent(), "out");

        $body = $in->validate($body);

        $categoryData = $this->normalizeInput($body);
        $id = $this->categoryModel->save($categoryData);
        $this->validateModel($this->categoryModel);

        if (!$id) {
            throw new ServerException("Unable to add category.", 500);
        }

        $row = $this->category($id);
        $row = $this->normalizeOutput($row);
        $result = $out->validate($row);
        return $result;
    }

    /**
     * Set the "follow" status on a category for the current user.
     *
     * @param int $id The target category's ID.
     * @return array
     */
    public function put_follow($id, array $body)
    {
        $this->permission("Garden.SignIn.Allow");

        $schema = ["followed:b" => "The category-follow status for the current user."];
        $in = $this->schema($schema, "in");
        $out = $this->schema($schema, "out");

        $category = $this->category($id);
        $body = $in->validate($body);
        $userID = $this->getSession()->UserID;

        $this->permission("Vanilla.Discussions.View", $category["PermissionCategoryID"]);

        $this->categoryModel->follow($userID, $id, $body["followed"]);
        ModelUtils::validationResultToValidationException($this->categoryModel);

        $result = $out->validate([
            "followed" => $this->categoryModel->isFollowed($userID, $id),
        ]);
        return $result;
    }

    /**
     * Normalize request data to be passed to a model.
     *
     * @param array $request
     * @return array
     */
    public function normalizeInput(array $request)
    {
        if (array_key_exists("bannerUrl", $request)) {
            $request["BannerImage"] = $request["bannerUrl"];
            unset($request["bannerUrl"]);
        }

        if (array_key_exists("iconUrl", $request)) {
            $request["Photo"] = $request["iconUrl"];
            unset($request["iconUrl"]);
        }

        $request = ApiUtils::convertInputKeys($request);

        if (array_key_exists("Urlcode", $request)) {
            $request["UrlCode"] = $request["Urlcode"];
            unset($request["Urlcode"]);
        }

        return $request;
    }

    /**
     * Normalize a database record to match the Schema definition.
     *
     * @param array $dbRecord Database record.
     * @param array|string|bool $expand Expand options.
     *
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord, $expand = [])
    {
        $row = $this->categoryModel->normalizeRow($dbRecord, $expand);
        $row["breadcrumbs"] = $this->breadcrumbModel->getForRecord(
            new ForumCategoryRecordType($dbRecord["CategoryID"])
        );
        return $row;
    }

    /**
     * Move a category under a new parent.
     *
     * @param int $categoryID The ID of the category.
     * @param int $parentCategoryID The new parent category ID.
     * @param bool $rebuildTree Should the tree be rebuilt after moving?
     * @throws NotFoundException if unable to find the category.
     * @throws ClientException if the parent and category are the same.
     * @throws ClientException if the parent category ID is invalid.
     * @throws ClientException if the target parent category does not exist.
     * @throws ClientException if trying to move a category under one of its own children.
     * @return array The updated category row.
     */
    private function updateParent($categoryID, $parentCategoryID)
    {
        if ($categoryID == $parentCategoryID) {
            throw new ClientException("A category cannot be the parent of itself.");
        }

        if ($parentCategoryID < 1 && $parentCategoryID !== -1) {
            throw new ClientException("parentCategoryID must be -1 or greater than zero.");
        }

        // Make sure the parent exists.
        try {
            $this->category($parentCategoryID);
        } catch (Exception $e) {
            throw new ClientException("The new parent category could not be found.");
        }

        // Make sure the category exists.
        $this->category($categoryID);

        $childTree = CategoryModel::flattenTree($this->categoryModel->getChildTree($categoryID));
        $children = array_column($childTree, "CategoryID");
        if (in_array($parentCategoryID, $children)) {
            throw new ClientException("Cannot move a category under one of its own children.");
        }

        $this->categoryModel->setField($categoryID, "ParentCategoryID", $parentCategoryID);

        $this->categoryModel->rebuildTree();
        $this->categoryModel->recalculateTree();

        $result = $this->category($categoryID);
        $result = $this->normalizeOutput($result);
        return $result;
    }

    /**
     * Get a category schema with an additional field for an array of children.
     *
     * @return Schema
     */
    public function schemaWithChildren()
    {
        $schema = clone $this->fullSchema();
        $childSchema = clone $schema;

        $schema->merge(
            Schema::parse([
                "depth:i",
                "children:a?" => $childSchema->merge(Schema::parse(["depth:i", "children:a", "sort:i"])),
                "sort:i?",
            ])
        );
        return $schema;
    }

    /**
     * Get a category schema with an additional field for a parent record.
     *
     * @param bool $expand
     * @return Schema
     */
    public function schemaWithParent($expand = false, $type = "")
    {
        $attributes = ["parentCategoryID:i|n" => "Parent category ID."];
        if ($expand) {
            $attributes["parent:o?"] = Schema::parse(["categoryID", "name", "urlcode", "url"])->add(
                $this->fullSchema()
            );
        }
        $schema = $this->fullSchema();
        $result = $schema->merge(Schema::parse($attributes));
        return $this->schema($result, $type);
    }

    /**
     * Extracted from `index()`.
     *
     * @param array $where
     * @param int|null $limit
     * @param int|null $offset
     * @param string $order
     * @param bool $filter Apply permission based filter
     * @return array
     */
    private function getCategoriesWhere(array $where, $limit, $offset, $order = "", bool $filter = true): array
    {
        $dirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($dirtyRecords) {
            $this->categoryModel->applyDirtyWheres();
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
            $categories = $this->categoryModel->getWhere($where, $order, "", $limit, $offset)->resultArray();
        } else {
            $categories = $this->categoryModel->getWhere($where, $order, "", $limit, $offset)->resultArray();
        }

        // Index by ID for category calculation functions.
        $categories = array_column($categories, null, "CategoryID");
        // Drop off the root category.
        unset($categories[-1]);

        categoryModel::joinUserData($categories);
        categoryModel::calculateData($categories);

        // Reset indexes for proper output detection as an indexed array.
        $categories = array_values($categories);

        if ($filter) {
            // Filter permissions
            $categories = CategoryModel::filterExistingCategoryPermissions($categories);
        }

        $totalCountCallBack = function () use ($where) {
            return $this->categoryModel->getCount($where);
        };
        return [$categories, $totalCountCallBack];
    }

    /**
     * Validate that Tree and Flat format parameters are mutually exclusives.
     *
     * @param array $query
     */
    private function checkMixedQuery($query): void
    {
        $formatIsFlat =
            isset($query["page"]) ||
            isset($query["limit"]) ||
            (isset($query["outputFormat"]) && $query["outputFormat"] === "flat");
        $formatIsTree =
            isset($query["maxDepth"]) || (isset($query["outputFormat"]) && $query["outputFormat"] === "tree");

        if ($formatIsFlat && $formatIsTree) {
            trigger_error(self::ERRORINDEXMSG, E_USER_WARNING);
        }
    }

    /**
     * Return a normalized TreeBuilder for the /index api endpoint.
     *
     * @return TreeBuilder
     */
    private function treeNormalizedBuilder(): TreeBuilder
    {
        $builder = TreeBuilder::create("categoryID", "parentCategoryID")
            ->setAllowUnreachableNodes(true)
            ->setRootID(null)
            ->setChildrenFieldName("children")
            ->setSorter(function (array $catA, array $catB) {
                return ($catA["sort"] ?? 0) <=> ($catB["sort"] ?? 0);
            });
        return $builder;
    }
}
