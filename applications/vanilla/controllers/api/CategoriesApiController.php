<?php
/**
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Schema\ValidationException;
use Garden\Schema\ValidationField;
use Garden\Web\Exception\ForbiddenException;
use Garden\Web\Exception\HttpException;
use Vanilla\Dashboard\Models\InterestModel;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Database\CallbackWhereExpression;
use Vanilla\Exception\PermissionException;
use Vanilla\FeatureFlagHelper;
use Vanilla\Forum\Models\DiscussionPermissions;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Scheduler\LongRunnerAction;
use Vanilla\Schema\RangeExpression;
use Vanilla\SchemaFactory;
use Vanilla\Site\SiteSectionModel;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Utility\ArrayUtils;
use Vanilla\Utility\ModelUtils;
use Vanilla\Utility\SchemaUtils;
use Vanilla\Utility\TreeBuilder;
use Garden\Web\Pagination;
use Vanilla\Community\Schemas\PostFragmentSchema;

/**
 * API Controller for the `/categories` resource.
 */
class CategoriesApiController extends AbstractApiController
{
    /** @var Schema */
    private $categoryPostSchema;

    /** @var  Schema */
    private $categorySchema;

    /** @var Schema */
    private $categoryPreferencesSchema;

    /** @var Schema */
    private $idParamSchema;

    public const OUTPUT_FORMAT_TREE = "tree";
    public const OUTPUT_FORMAT_FLAT = "flat";
    public const OUTPUT_FORMATS = [self::OUTPUT_FORMAT_TREE, self::OUTPUT_FORMAT_FLAT];

    public const OUTPUT_PREFERENCE_FOLLOW = "preferences.followed";

    public const OUTPUT_PREFERENCE_DISCUSSION_APP = "preferences.popup.posts";

    public const OUTPUT_PREFERENCE_DISCUSSION_EMAIL = "preferences.email.posts";

    public const OUTPUT_PREFERENCE_COMMENT_APP = "preferences.popup.comments";

    public const OUTPUT_PREFERENCE_COMMENT_EMAIL = "preferences.email.comments";

    public const OUTPUT_PREFERENCE_DIGEST = "preferences.email.digest";

    /** @var string */
    const ERRORINDEXMSG = "The following fields: {page, limit, outputFormat=flat} is incompatible with {maxDepth, outputFormat=tree}";

    /**
     * CategoriesApiController constructor.
     *
     * @param CategoryModel $categoryModel
     * @param BreadcrumbModel $breadcrumbModel
     * @param LongRunner $runner
     * @param SiteSectionModel $siteSectionModel
     */
    public function __construct(
        private CategoryModel $categoryModel,
        private BreadcrumbModel $breadcrumbModel,
        private LongRunner $runner,
        private SiteSectionModel $siteSectionModel,
        private InterestModel $interestModel,
        private PostTypeModel $postTypeModel,
        private DiscussionPermissions $discussionPermissions
    ) {
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
                "allowedDiscussionTypes:a?",
            ];
            $this->categoryPostSchema = $this->schema(
                Schema::parse(array_merge($fields, $extra))->add($this->schemaWithParent()),
                "CategoryPost"
            );
            $this->categoryPostSchema->addValidator("allowedDiscussionTypes", function ($data, ValidationField $field) {
                $allowedDiscussionTypes = array_keys(DiscussionModel::discussionTypes());
                $result = array_diff($data, $allowedDiscussionTypes);

                if (!empty($result) || empty($data)) {
                    $validTypes = implode(", ", $allowedDiscussionTypes);
                    $field->addError("Validation Failed", [
                        "messageCode" => "allowedDiscussionTypes can only contain the following values: $validTypes",
                        "code" => 403,
                    ]);
                }
            });
            if (PostTypeModel::isPostTypesFeatureEnabled()) {
                $validPostTypes = $this->postTypeModel->getAvailablePostTypes();
                $this->categoryPostSchema
                    ->merge(
                        Schema::parse([
                            "hasRestrictedPostTypes:b?",
                            "allowedPostTypeIDs:a?" => [
                                "items" => [
                                    "type" => "string",
                                    "enum" => array_column($validPostTypes, "postTypeID"),
                                ],
                            ],
                        ])
                    )
                    ->addFilter("", function ($data, ValidationField $field) {
                        if (!ArrayUtils::isArray($data)) {
                            return $data;
                        }

                        if (isset($data["hasRestrictedPostTypes"]) && !$data["hasRestrictedPostTypes"]) {
                            // Make sure we clear associated post types.
                            $data["allowedPostTypeIDs"] = [];
                        }
                        unset($data["hasRestrictedPostTypes"]);
                        return $data;
                    })
                    ->addFilter("allowedPostTypeIDs", fn($allowedPostTypeIDs) => array_unique($allowedPostTypeIDs))
                    ->addFilter("", SchemaUtils::onlyOneOf(["allowedDiscussionTypes", "allowedPostTypeIDs"]));
            }
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
     * @return array
     * @throws NotFoundException if the category cannot be found.
     */
    public function category($id)
    {
        $category = CategoryModel::categories($id);
        if (empty($category)) {
            throw new NotFoundException("Category");
        }
        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            $categories = [&$category];
            CategoryModel::joinPostTypes($categories);
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

        // Create and dispatch a category deletion event.
        $deleteEvent = new \Vanilla\Community\Events\CategoryDeleteEvent($id, array_merge($deleteOptions, $options));
        $this->getEventManager()->dispatch($deleteEvent);

        $longRunnerArgs = [$id, $deleteOptions];

        $actions = array_merge(
            [new LongRunnerAction(CategoryModel::class, "deleteIDIterable", $longRunnerArgs, $options)],
            $deleteEvent->getActions() ?? [] // Merge in any additional longrunner actions.
        );

        $response = $this->runner->runApi(new \Vanilla\Scheduler\LongRunnerMultiAction($actions, $options));
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
        $expand = $query["expand"] ?? [];

        $out = $this->schema(
            CrawlableRecordSchema::applyExpandedSchema($this->schemaWithParent(), "category", $expand),
            "out"
        );

        $row = $this->category($id);

        $rows = [&$row];
        $this->joinCategoryExpandFields($rows, $expand);
        $row = $this->normalizeOutput($row, $expand);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Get a category for editing.
     *
     * @param int $id The ID of the category.
     * @return array
     * @throws NotFoundException if unable to find the category.
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

        $out = $this->categoryPreferencesSchema("out");

        $this->category($id);

        $preferences = $this->categoryModel->getPreferencesByCategoryID($userID, $id);
        $normalizedPreferences = $this->categoryModel->normalizePreferencesOutput($preferences);
        $result = $out->validate($normalizedPreferences);

        return new Data($result);
    }

    /**
     * Search categories.
     *
     * @param array $query The query string.
     * @return Data
     * @throws HttpException
     * @throws PermissionException
     * @throws ValidationException
     */
    public function get_search(array $query): Data
    {
        $this->permission();

        $in = $this->schema([
            "query:s" => [
                "description" => "Category name filter.",
                "minLength" => 0,
            ],
            "displayAs:a?" => [
                "description" => "DisplayAs filter.",
                "items" => [
                    "type" => "string",
                    "enum" => [
                        Categorymodel::DISPLAY_NESTED,
                        Categorymodel::DISPLAY_DISCUSSIONS,
                        Categorymodel::DISPLAY_FLAT,
                        CategoryModel::DISPLAY_HEADING,
                    ],
                ],
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "parentCategoryID:i?",
            "siteSectionID:s?" => "Filter categories by site-section-id (subcommunity)",
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => $this->categoryModel->getDefaultLimit(),
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(100),
            ],
            "layoutViewType:s?" => [
                "description" => "Layout View Type.",
                "minLength" => 0,
            ],
            "filterDiscussionsAdd:b?" => "Filter out categories that cannot be posted into.",
            "expand?" => ApiUtils::getExpandDefinition(["parent", "breadcrumbs"]),
        ])->setDescription("Search categories.");
        $expand = $query["expand"] ?? [];
        $out = $this->schema([":a" => $this->schemaWithParent($expand)], "out");

        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        $where = [];
        if ($query["layoutViewType"] ?? false) {
            if ($query["layoutViewType"] == CategoryModel::LAYOUT_NESTED_CATEGORY_LIST) {
                $where["DisplayAs"] = [Categorymodel::DISPLAY_NESTED, Categorymodel::DISPLAY_FLAT];
            } elseif ($query["layoutViewType"] == CategoryModel::LAYOUT_DISCUSSION_CATEGORY_PAGE) {
                $where["DisplayAs"] = Categorymodel::DISPLAY_DISCUSSIONS;
            }
        }

        if ($query["displayAs"] ?? false) {
            $where["DisplayAs"] = $query["displayAs"];
        }

        $parentCategory = $this->getIndexParentCategoryID($query);

        $results = $this->categoryModel->searchByName(
            $query["query"],
            $parentCategory["CategoryID"] ?? null,
            $where,
            $this->isExpandField("parent", $expand),
            $limit,
            $offset,
            filterDiscussionsAdd: $query["filterDiscussionsAdd"] ?? false
        );

        foreach ($results as $key => $row) {
            $results[$key] = $this->normalizeOutput($row);
        }

        $result = $out->validate(array_values($results));

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
                    "expand?" => ApiUtils::getExpandDefinition(["lastPost", "permissions"]),
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
                "followedUserID:i?",
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
                "sort:s?" => [
                    "enum" => ApiUtils::sortEnum("categoryID", "name", "dateFollowed"),
                    "description" => "Sort the results by the specified field.",
                ],
                "limit:i?" => [
                    "minimum" => 1,
                    "maximum" => ApiUtils::getMaxLimit(),
                ],
                "expand?" => ApiUtils::getExpandDefinition(["lastPost", "preferences", "permissions"]),
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
                "includeParentCategory:b?" => [
                    "description" =>
                        "Whether to include the parent category when used with parentCategoryID, parentCategoryCode, or siteSectionID.",
                    "default" => false,
                ],
                "layoutViewType:s?",
                "postTypeID:s?",
                "postTypeStatus:s?" => [
                    "enum" => ["active", "inactive", "both"],
                    "default" => "active",
                ],
                "filterDiscussionsAdd:b?" => "Filter out categories that cannot be posted into.",
            ],
            "in"
        )
            ->addValidator("", SchemaUtils::onlyOneOf(["categoryID", "parentCategoryID", "parentCategoryCode"]))
            ->addValidator("", SchemaUtils::onlyOneOf(["followed", "followedUserID"]))
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
     * @throws NotFoundException
     * @throws ValidationException
     * @throws HttpException
     * @throws PermissionException
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

        $page = $query["page"] ?? 1;
        $limit = $query["limit"] ?? 30;

        $where = [];
        $sort = "";

        if ($query["featured"] ?? false) {
            $where["Featured"] = $query["featured"] ? 1 : 0;
            $sort = "SortFeatured";
        }

        if ($query["dirtyRecords"] ?? false) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = true;
        }

        if ($query["layoutViewType"] ?? false) {
            switch ($query["layoutViewType"]) {
                case Categorymodel::LAYOUT_NESTED_CATEGORY_LIST:
                    $where["DisplayAs"] = [Categorymodel::DISPLAY_NESTED, Categorymodel::DISPLAY_FLAT];
                    $format = self::OUTPUT_FORMAT_FLAT;
                    break;
                case Categorymodel::LAYOUT_DISCUSSION_CATEGORY_PAGE:
                    $where["DisplayAs"] = Categorymodel::DISPLAY_DISCUSSIONS;
                    $format = self::OUTPUT_FORMAT_FLAT;
                    break;
                case Categorymodel::LAYOUT_CATEGORY_LIST:
                    $where["DisplayAs"] = "!= " . Categorymodel::DISPLAY_HEADING;
                    $format = self::OUTPUT_FORMAT_FLAT;
                    break;
            }
        }

        [$offset, $limit] = offsetLimit("p{$page}", $limit);
        if ($format === self::OUTPUT_FORMAT_TREE) {
            $limit = "";
            $page = 1;
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
        $visibleIDs = $this->categoryModel->getVisibleCategoryIDs([
            "filterDiscussionsAdd" => $query["filterDiscussionsAdd"] ?? false,
        ]);

        if ($visibleIDs === true) {
            $categoryIDs = $categoryIDs->withFilteredValue(">", 0);
        } else {
            $categoryIDs = $categoryIDs->withFilteredValue("=", $visibleIDs);
        }

        // Apply "followed" filtering.
        if (($query["followed"] ?? false) || ($query["followedUserID"] ?? false)) {
            if (!empty($query["followedUserID"])) {
                if ($query["followedUserID"] != $this->getSession()->UserID) {
                    $row = Gdn::userModel()->getID($query["followedUserID"], DATASET_TYPE_ARRAY);
                    if (!$row || $row["Deleted"] > 0) {
                        throw new NotFoundException("User");
                    }
                    $this->permission(["Garden.Users.Edit", "Moderation.Profiles.Edit"]);
                }
            }
            $followed = 1;
            $followedUserID = !empty($query["followedUserID"]) ? $query["followedUserID"] : $this->getSession()->UserID;
            $where["userID"] = $followedUserID;
            $followedRecords = $this->categoryModel->getFollowed($followedUserID);
            $followedIDs = array_column($followedRecords, "CategoryID");
            $categoryIDs = $categoryIDs->withFilteredValue("=", $followedIDs);
        }

        // Parent category filtering.
        if (!empty($parentCategory)) {
            $descendantIDs = $this->categoryModel->getCategoriesDescendantIDs([$parentCategory["CategoryID"]]);
            if (!$query["includeParentCategory"]) {
                $descendantIDs = array_filter($descendantIDs, function (int $id) use ($parentCategory) {
                    return $id !== $parentCategory["CategoryID"];
                });
            }

            $categoryIDs = $categoryIDs->withFilteredValue("=", $descendantIDs);
        }

        // Additional filtering by post type
        if (!empty($query["postTypeID"])) {
            $allowedCategoriesIDsByPostType = $this->postTypeModel->getAllowedCategoryIDsByPostTypeID(
                $query["postTypeID"],
                $query["postTypeStatus"] ?? "active"
            );
            $categoryIDs = $categoryIDs->withFilteredValue("=", $allowedCategoriesIDsByPostType);
        }

        $where["CategoryID"] = $categoryIDs;

        [$orderField, $orderDirection] = \Vanilla\Models\LegacyModelUtils::orderFieldDirection($query["sort"] ?? "");
        if (!empty($orderField)) {
            $sort = empty($sort) ? $orderField : $orderField . "," . $sort;
        }

        [$categories, $totalCountCallBack] = $this->getCategoriesWhere(
            $where,
            $limit,
            $offset,
            $sort,
            $orderDirection,
            $filter
        );

        // Filter tree by the category "archived" fields.
        if (!isset($followed) && $query["archived"] !== null) {
            $categories = $this->archiveFilter($categories, $query["archived"] ? 0 : 1);
        }
        $this->joinCategoryExpandFields($categories, (array) $expand, $followedUserID ?? null);
        foreach ($categories as $key => $category) {
            $categories[$key] = $this->normalizeOutput($category, $expand);
        }
        $categories = $out->validate(array_values($categories));

        if ($format === "tree") {
            $categories = $this->treeNormalizedBuilder()
                ->setAllowUnreachableNodes(true)
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

        return new Data($categories, Pagination::tryCursorPagination($paging, $query, $categories, "categoryID"));
    }

    /**
     * Expand on Category
     *
     * @param array $rows
     * @param array|bool|string $expand
     * @return void
     */
    private function joinCategoryExpandFields(array &$rows, $expand, ?int $userID = null): void
    {
        if (ModelUtils::isExpandOption("lastPost", $expand)) {
            CategoryModel::joinRecentPosts($rows, null);
            // Expand associated rows.
            $userModel = Gdn::getContainer()->get(UserModel::class);
            $userModel->expandUsers($rows, ["LastUserID"]);
        }
        if (ModelUtils::isExpandOption("preferences", $expand)) {
            //need to allow querying another USer
            $userID = $userID ?: $this->getSession()->UserID;
            $userPreferences = $this->categoryModel->getPreferences($userID);
            foreach ($rows as $key => $row) {
                if (empty($userPreferences[$row["CategoryID"]])) {
                    continue;
                } else {
                    $rows[$key]["preferences"] = $this->categoryModel->normalizePreferencesOutput(
                        $userPreferences[$row["CategoryID"]]["preferences"]
                    );
                }
            }
        }

        if (ModelUtils::isExpandOption("permissions", $expand)) {
            foreach ($rows as &$row) {
                $categoryID = $row["CategoryID"];
                $row["permissions"] = $this->discussionPermissions->getForCategory($categoryID);
            }
        }
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
                "items" => $this->fragmentWithPreferencesSchema(),
            ],
        ]);

        $preferences = $this->categoryModel->getPreferences($userID);

        $normalizedPreferences = [];
        foreach ($preferences as $key => $preferenceSet) {
            $preferenceSet["preferences"] = $this->categoryModel->normalizePreferencesOutput(
                $preferenceSet["preferences"]
            );
            $normalizedPreferences[$key] = $preferenceSet;
        }
        $result = $out->validate(array_values($normalizedPreferences));
        return new Data($result);
    }

    /**
     * Get a category fragment schema with the addition of a user preferences field.
     *
     * @return Schema
     */
    public function fragmentWithPreferencesSchema(): Schema
    {
        $fragmentSchema = $this->categoryModel->fragmentSchema();
        $preferencesSchema = SchemaFactory::parse(
            [
                "preferences" => $this->categoryPreferencesSchema(),
            ],
            "CategoryFragmentPreferences"
        );
        $result = $fragmentSchema->merge($preferencesSchema);
        return $result;
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
     * @return array
     * @throws NotFoundException if unable to find the category.
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
        $this->category($id);

        if (array_key_exists("parentCategoryID", $body)) {
            $this->updateParent($id, $body["parentCategoryID"]);
            unset($body["parentCategoryID"]);
        }

        $categoryData = $this->normalizeInput($body);
        $categoryData["CategoryID"] = $body["CategoryID"] ?? (int) $id;
        $this->categoryModel->save($categoryData);

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
     * @throws PermissionException Throws a permission exception in the following cases:
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

        $apiSchema = $this->categoryPreferencesSchema("in");

        $this->category($id);

        $body = $apiSchema->validate($body, true);

        // Make sure the user whose preferences are being changed has the Email.View permission if opting for email
        // notifications or email digest. Throws an error if they don't.
        if (
            !empty($body[self::OUTPUT_PREFERENCE_DISCUSSION_EMAIL]) ||
            !empty($body[self::OUTPUT_PREFERENCE_COMMENT_EMAIL]) ||
            !empty($body[self::OUTPUT_PREFERENCE_DIGEST])
        ) {
            $hasEmailViewPerm = $isSelfService
                ? $this->getPermissions()->has("Garden.Email.View")
                : Gdn::getContainer()
                    ->get(UserModel::class)
                    ->getPermissions($userID)
                    ->has("Garden.Email.View");
            if (!$hasEmailViewPerm) {
                throw new PermissionException("Garden.Email.View");
            }
        }

        $normalizedBody = $this->categoryModel->normalizePreferencesInput($body);

        $this->categoryModel->setPreferences($userID, $id, $normalizedBody);

        $preferences = $this->categoryModel->getPreferencesByCategoryID($userID, $id);

        $normalizedOutput = $this->categoryModel->normalizePreferencesOutput($preferences);
        $result = $apiSchema->validate($normalizedOutput);
        return new Data($result);
    }

    /**
     * Add a category.
     *
     * @param array $body The request body.
     * @return array
     * @throws ServerException if the category could not be created.
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
     * @param array $body
     * @return array
     * @throws ClientException
     * @throws HttpException
     * @throws NotFoundException
     * @throws PermissionException
     * @throws ValidationException
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
     * Endpoint to get suggested categories based on interests.
     *
     * @return Data
     */
    public function get_suggested(array $query = []): Data
    {
        $this->interestModel->ensureSuggestedContentEnabled();
        $this->permission("Garden.SignIn.Allow");

        $in = $this->schema([
            "excludedCategoryIDs:a?" => [
                "items" => ["type" => "integer"],
            ],
            "page:i?" => [
                "description" => "Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).",
                "default" => 1,
                "minimum" => 1,
            ],
            "limit:i?" => [
                "description" => "Desired number of items per page.",
                "default" => 5,
                "minimum" => 1,
                "maximum" => ApiUtils::getMaxLimit(),
            ],
        ]);
        $query = $in->validate($query);
        $out = $this->schema([":a" => $this->fullSchema()], "out");

        // Get a list of suggested category IDs.
        [$suggestedCategoryIDs] = $this->interestModel->getRecordIDsByUserID($this->getSession()->UserID);

        $this->categoryModel->setJoinUserCategory(false);

        $where = [];

        [$offset, $limit] = offsetLimit("p{$query["page"]}", $query["limit"]);

        /** @var RangeExpression $categoryIDs */
        $categoryIDs = $query["categoryID"] ?? new RangeExpression(">", 0);

        // Exclude categories already followed.
        $followedRecords = $this->categoryModel->getFollowed($this->getSession()->UserID);
        $followedIDs = array_column($followedRecords, "CategoryID");
        $suggestedCategoryIDs = array_diff($suggestedCategoryIDs, $followedIDs);

        if (isset($query["excludedCategoryIDs"])) {
            $suggestedCategoryIDs = array_diff($suggestedCategoryIDs, $query["excludedCategoryIDs"]);
        }

        // Apply permission filtering.
        $visibleIDs = $this->categoryModel->getVisibleCategoryIDs();
        if ($visibleIDs === true) {
            $categoryIDs = $categoryIDs->withFilteredValue("=", $suggestedCategoryIDs);
        } else {
            $categoryIDs = $categoryIDs->withFilteredValue("=", array_intersect($suggestedCategoryIDs, $visibleIDs));
        }

        $where["CategoryID"] = $categoryIDs;

        [$categories, $totalCountCallBack] = $this->getCategoriesWhere($where, $limit, $offset);

        foreach ($categories as $key => $category) {
            $categories[$key] = $this->normalizeOutput($category);
        }
        $categories = $out->validate(array_values($categories));
        $categories = $this->treeNormalizedBuilder()->sort($categories);

        $paging = ApiUtils::numberedPagerInfo($totalCountCallBack(), "/api/v2/categories/suggested", $query, $in);

        return new Data($categories, ["paging" => $paging]);
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

        $request = ApiUtils::convertInputKeys($request, ["allowedPostTypeIDs"]);

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
     * @return array The updated category row.
     * @throws ClientException if the parent and category are the same.
     * @throws ClientException if the parent category ID is invalid.
     * @throws ClientException if the target parent category does not exist.
     * @throws ClientException if trying to move a category under one of its own children.
     * @throws NotFoundException if unable to find the category.
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
                "lastPost?" => \Vanilla\SchemaFactory::get(PostFragmentSchema::class, "PostFragment"),
                "preferences?" => $this->categoryPreferencesSchema(),
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
        $attributes = [
            "parentCategoryID:i|n" => "Parent category ID.",
            "lastPost?" => \Vanilla\SchemaFactory::get(PostFragmentSchema::class, "PostFragment"),
        ];
        if ($expand) {
            $attributes["parent:o?"] = Schema::parse(["categoryID", "name", "urlcode", "url"])->add(
                $this->fullSchema()
            );
        }
        $schema = $this->categorySchema();
        $result = $schema->merge(Schema::parse($attributes));
        return $this->schema($result, $type);
    }

    /**
     * Get the api schema for category notification preferences.
     *
     * @param string $type
     * @return Schema
     */
    public function categoryPreferencesSchema(string $type = ""): Schema
    {
        $schema = $this->schema(
            Schema::parse([
                self::OUTPUT_PREFERENCE_FOLLOW . ":b",
                self::OUTPUT_PREFERENCE_DISCUSSION_APP . ":b",
                self::OUTPUT_PREFERENCE_DISCUSSION_EMAIL . ":b",
                self::OUTPUT_PREFERENCE_COMMENT_APP . ":b",
                self::OUTPUT_PREFERENCE_COMMENT_EMAIL . ":b",
            ]),
            $type
        );

        if (CategoryModel::isDigestEnabled()) {
            $schema->add(Schema::parse([self::OUTPUT_PREFERENCE_DIGEST . ":b?"]), true);
        }

        $this->categoryPreferencesSchema = $this->schema($schema, "CategoryPreferences");

        return $this->schema($this->categoryPreferencesSchema, $type);
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
    private function getCategoriesWhere(
        array $where,
        $limit,
        $offset,
        $order = "",
        $orderDirection = "",
        bool $filter = true
    ): array {
        $dirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        $userID = null;
        if ($order == "dateFollowed") {
            $sort = $order;
            $order = "";
        }
        if (isset($where["userID"])) {
            $userID = $where["userID"];
            unset($where["userID"]);
        }
        if ($dirtyRecords) {
            $this->categoryModel->applyDirtyWheres();
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
            $categories = $this->categoryModel
                ->getWhere($where, $order, $orderDirection, $limit, $offset)
                ->resultArray();
        } else {
            $categories = $this->categoryModel
                ->getWhere($where, $order, $orderDirection, $limit, $offset)
                ->resultArray();
        }

        // Index by ID for category calculation functions.
        $categories = array_column($categories, null, "CategoryID");
        // Drop off the root category.
        unset($categories[-1]);

        \CategoryModel::joinUserData($categories, true, $userID);

        if (isset($sort) && $sort == "dateFollowed" && count($categories) > 1) {
            $compare = function ($compare1, $compare2) use ($orderDirection) {
                $field = "DateFollowed";
                $element1 = $orderDirection == "asc" ? $compare1[$field] : $compare2[$field];
                $element2 = $orderDirection == "asc" ? $compare2[$field] : $compare1[$field];
                $element1 = strtotime($element1);
                $element2 = strtotime($element2);
                return $element1 <=> $element2;
            };
            usort($categories, $compare);
        }

        foreach ($categories as &$category) {
            CategoryModel::calculate($category);
        }

        if (PostTypeModel::isPostTypesFeatureEnabled()) {
            CategoryModel::joinPostTypes($categories);
        }

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
