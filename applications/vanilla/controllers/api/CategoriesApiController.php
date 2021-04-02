<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

use Garden\Schema\Schema;
use Garden\Web\Exception\ForbiddenException;
use Vanilla\Dashboard\Models\BannerImageModel;
use Vanilla\Forum\Navigation\ForumCategoryRecordType;
use Vanilla\Models\CrawlableRecordSchema;
use Vanilla\Models\DirtyRecordModel;
use Vanilla\Navigation\BreadcrumbModel;
use Vanilla\Permissions;
use Vanilla\Utility\InstanceValidatorSchema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;
use Vanilla\Navigation\Breadcrumb;
use Vanilla\Utility\ModelUtils;

/**
 * API Controller for the `/categories` resource.
 */
class CategoriesApiController extends AbstractApiController {

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

    /**
     * CategoriesApiController constructor.
     *
     * @param CategoryModel $categoryModel
     * @param BreadcrumbModel $breadcrumbModel
     */
    public function __construct(
        CategoryModel $categoryModel,
        BreadcrumbModel $breadcrumbModel
    ) {
        $this->categoryModel = $categoryModel;
        $this->breadcrumbModel = $breadcrumbModel;
    }

    /**
     * Get a category schema with minimal add/edit fields.
     *
     * @param string $type The type of schema.
     * @param array $extra Additional fields to include.
     * @return Schema Returns a schema object.
     */
    public function categoryPostSchema($type = '', array $extra = []) {
        if ($this->categoryPostSchema === null) {
            $fields = ['name', 'parentCategoryID?', 'urlcode', 'displayAs?', 'customPermissions?', 'description?', 'featured?'];
            $this->categoryPostSchema = $this->schema(
                Schema::parse(array_merge($fields, $extra))->add($this->schemaWithParent()),
                'CategoryPost'
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
    public function categorySchema($type = '') {
        if ($this->categorySchema === null) {
            $this->categorySchema = $this->schema($this->fullSchema(), 'Category');
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
    public function category($id) {
        $category = CategoryModel::categories($id);
        if (empty($category)) {
            throw new NotFoundException('Category');
        }
        return $category;
    }

    /**
     * Delete a category.
     *
     * @param int $id The ID of the category.
     * @throws NotFoundException if the category cannot be found.
     * @throws ServerException if the category has its CanDelete flag set to false.
     * @throws ServerException if the category has children.
     */
    public function delete($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema('in')->setDescription('Delete a category.');
        $out = $this->schema([], 'out');

        $row = $this->category($id);
        $children = $this->categoryModel->getChildTree($row['CategoryID']);
        if (!$row['CanDelete']) {
            throw new ServerException('The specified category cannot be deleted.', 500);
        }
        if (count($children) > 0) {
            throw new ServerException('Cannot delete categories with children.', 500);
        }
        $this->categoryModel->deleteID($id);
    }

    /**
     * Get a schema instance comprised of all available category fields.
     *
     * @return Schema Returns a schema object.
     */
    public function fullSchema() {
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
    public function get(int $id, array $query = []) {
        $query['id'] = $id;
        if (!$this->categoryModel::checkPermission($id, 'Vanilla.Discussions.View')) {
            throw new ForbiddenException('Category');
        }

        $in = $this->idParamSchema()->setDescription('Get a category.');
        $query = $in->validate($query);
        $expand = $query['expand'];

        $out = $this->schema(CrawlableRecordSchema::applyExpandedSchema($this->schemaWithParent(), 'category', $expand), 'out');

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
    public function get_edit($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema()->setDescription('Get a category for editing.');
        $out = $this->schema(Schema::parse([
            'categoryID', 'name', 'parentCategoryID', 'urlcode', 'description', 'displayAs'
        ])->add($this->fullSchema()), 'out');

        $row = $this->category($id);
        $row = $this->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Search categories.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function get_search(array $query) {
        $this->permission();

        $in = $this->schema([
            'query:s' => [
                'description' => 'Category name filter.',
                'minLength' => 0,
            ],
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->categoryModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(100),
            ],
            'expand?' => ApiUtils::getExpandDefinition(['parent', 'breadcrumbs'])
        ])->setDescription('Search categories.');
        $out = $this->schema([':a' => $this->schemaWithParent($query['expand'])], 'out');

        $query = $in->validate($query);

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);
        $rows = $this->categoryModel->searchByName(
            $query['query'],
            $this->isExpandField('parent', $query['expand']),
            $limit,
            $offset,
            $this->isExpandField('breadcrumbs', $query['expand']) ? ['breadcrumbs'] : []
        );

        foreach ($rows as $key => &$row) {
            $row = $this->normalizeOutput($row);
            $hasPermission = categoryModel::checkPermission($row['categoryID'], 'Vanilla.Discussions.View');
            if (!$hasPermission) {
                unset($rows[$key]);
            }
        }

        $result = $out->validate($rows);

        $paging = ApiUtils::morePagerInfo($result, '/api/v2/comments', $query, $in);

        return new Data($result, ['paging' => $paging]);
    }

    /**
     * Get an ID-only category record schema.
     *
     * @param string $type The type of schema.
     * @return Schema Returns a schema object.
     */
    public function idParamSchema($type = 'in') {
        if ($this->idParamSchema === null) {
            $this->idParamSchema = $this->schema(
                Schema::parse([
                    'id:i' => 'The category ID.',
                    'expand?' => ApiUtils::getExpandDefinition([]),
                ]),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List categories.
     *
     * @param array $query The query string.
     * @param bool $filter Apply permission based filter
     * @return Data
     */
    public function index(array $query, bool $filter = true) {
        $this->permission();

        $in = $this->schema([
            'categoryID?' => \Vanilla\Schema\RangeExpression::createSchema([':int']),
            'parentCategoryID:i?',
            'parentCategoryCode:s?',
            'followed:b?',
            'maxDepth:i?' => [
                'description' => '',
                'default' => 2,
            ],
            'archived:b|n' => [
                'default' => null
            ],
            'page:i?' => [
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->categoryModel->getMaxPages(),
            ],
            'limit:i?' => [
                'default' => $this->categoryModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => ApiUtils::getMaxLimit(),
            ],
            'expand?' => ApiUtils::getExpandDefinition([]),
            'featured:b?',
            'dirtyRecords:b?'
        ], 'in')
            ->addValidator('', \Vanilla\Utility\SchemaUtils::onlyOneOf(['categoryID', 'archived', 'followed', 'featured']))
            ->addValidator('', \Vanilla\Utility\SchemaUtils::onlyOneOf(['categoryID', 'parentCategoryID', 'parentCategoryCode']))
            ->setDescription('List categories.')
        ;

        $query = $in->validate($query);
        $expand = $query['expand'];

        $out = $this->schema([
            ':a' => CrawlableRecordSchema::applyExpandedSchema($this->schemaWithChildren(), 'category', $expand)
        ], 'out');

        if (array_key_exists('parentCategoryID', $query)) {
            $parent = $this->category($query['parentCategoryID']);
        } elseif (array_key_exists('parentCategoryCode', $query)) {
            $parent = $this->category($query['parentCategoryCode']);
        } else {
            // The root category config sets the DisplayAs of the root category.
            $parent = c('Vanilla.RootCategory', []) + $this->category(-1);
        }

        $joinUserCategory = $this->categoryModel->joinUserCategory();
        $this->categoryModel->setJoinUserCategory(true);

        [$offset, $limit] = offsetLimit("p{$query['page']}", $query['limit']);

        $where = [];
        $joinDirtyRecords = $query[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($joinDirtyRecords) {
            $where[DirtyRecordModel::DIRTY_RECORD_OPT] = $joinDirtyRecords;
        }

        if (!empty($query['categoryID'])) {
            /** @var \Vanilla\Schema\RangeExpression $range */
            $range = $query['categoryID'];

            $categoryIDs = $this->categoryModel->getVisibleCategoryIDs();
            if ($categoryIDs === true) {
                $range = $range->withFilteredValue('>', 0);
            } else {
                $range = $range->withFilteredValue('=', $categoryIDs);
            }

            $where['categoryID'] = $range;

            [$categories, $totalCountCallBack] = $this->getCategoriesWhere($where, $limit, $offset, 'CategoryID', $filter);
        } elseif ($query['followed'] ?? false) {
            $where['Followed'] = true;
            [$categories, $totalCountCallBack] = $this->getCategoriesWhere($where, $limit, $offset);
        } elseif ($query['featured'] ?? false) {
            $where['Featured'] = true;
            // Filter by parent.
            if ($parent['CategoryID'] !== -1) {
                $filterCategories = $this->categoryModel->getTree(
                    $parent['CategoryID'],
                    [
                        'maxdepth' => $query['maxDepth'],
                    ]
                );

                $filterCategoryIDs = array_column($filterCategories, 'CategoryID');
                $filterCategoryIDs = array_filter($filterCategoryIDs, function (int $id) {
                    return $id !== -1;
                });

                $where['categoryID'] = $filterCategoryIDs;
            }
            [$categories, $totalCountCallBack] = $this->getCategoriesWhere($where, $limit, $offset, 'SortFeatured');
        } elseif ($parent['DisplayAs'] === 'Flat') {
            $options = [];
            if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
                $options[DirtyRecordModel::DIRTY_RECORD_OPT] = $where[DirtyRecordModel::DIRTY_RECORD_OPT];
            }
            $categories = $this->categoryModel->getTreeAsFlat(
                $parent['CategoryID'],
                $offset,
                $limit,
                $options
            );

            $totalCountCallBack = function () use ($parent) {
                return $parent['CountCategories'];
            };
        } else {
            $options = [];
            $options['maxdepth'] = $query['maxDepth'] ?? 2;
            if (isset($where[DirtyRecordModel::DIRTY_RECORD_OPT])) {
                $options[DirtyRecordModel::DIRTY_RECORD_OPT] = $where[DirtyRecordModel::DIRTY_RECORD_OPT];
            }
            $categories = $this->categoryModel->getTree(
                $parent['CategoryID'],
                $options
            );

            // Filter tree by the category "archived" fields.
            if ($query['archived'] !== null) {
                $categories = $this->archiveFilter($categories, $query['archived'] ? 0 : 1);
            }
        }
        $this->categoryModel->setJoinUserCategory($joinUserCategory);

        foreach ($categories as &$category) {
            $category = $this->normalizeOutput($category, $expand);
        }

        $categories = $out->validate($categories);

        if (isset($totalCountCallBack)) {
            $paging = ApiUtils::numberedPagerInfo($totalCountCallBack(), '/api/v2/categories', $query, $in);
        } else {
            $paging = [];
        }

        return new Data($categories, ['paging' => $paging]);
    }

    /**
     * Recursively filter a list of categories by their archived flag.
     *
     * @param array $categories
     * @param int $archived
     * @return array
     */
    private function archiveFilter(array $categories, $archived) {
        $result = [];
        foreach ($categories as $index => $category) {
            // Discard, based on archived value.
            if (array_key_exists('Archived', $category) && $category['Archived'] === $archived) {
                continue;
            }
            // Process any children.
            if (!empty($category['Children'])) {
                $category['Children'] = $this->archiveFilter($category['Children'], $archived);
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
    public function patch($id, array $body) {
        $this->permission('Garden.Settings.Manage');

        $this->idParamSchema('in');
        $in = $this->categoryPostSchema('in', [
            'description',
            'parentCategoryID' => 'Parent category ID. Changing a category\'s parent will rebuild the category tree.'
        ])->setDescription('Update a category.');
        $out = $this->schemaWithParent(false, 'out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->category($id);

        if (array_key_exists('parentCategoryID', $body)) {
            $this->updateParent($id, $body['parentCategoryID']);
            unset($body['parentCategoryID']);
        }

        if (!empty($body)) {
            if (array_key_exists('customPermissions', $body)) {
                $this->categoryModel->save([
                    'CategoryID' => $id,
                    'CustomPermissions' => $body['customPermissions']
                ]);
                unset($body['customPermissions']);
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
     * Add a category.
     *
     * @param array $body The request body.
     * @throws ServerException if the category could not be created.
     * @return array
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->categoryPostSchema(), 'in')->setDescription('Add a category.');
        $out = $this->schema($this->schemaWithParent(), 'out');

        $body = $in->validate($body);

        $categoryData = $this->normalizeInput($body);
        $id = $this->categoryModel->save($categoryData);
        $this->validateModel($this->categoryModel);

        if (!$id) {
            throw new ServerException('Unable to add category.', 500);
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
    public function put_follow($id, array $body) {
        $this->permission('Garden.SignIn.Allow');

        $schema = ['followed:b' => 'The category-follow status for the current user.'];
        $in = $this->schema($schema, 'in');
        $out = $this->schema($schema, 'out');

        $category = $this->category($id);
        $body = $in->validate($body);
        $userID = $this->getSession()->UserID;
        $followed = $this->categoryModel->getFollowed($userID);

        // Is this a new follow?
        if ($body['followed'] && !array_key_exists($id, $followed)) {
            $this->permission('Vanilla.Discussions.View', $category['PermissionCategoryID']);
            if (count($followed) >= $this->categoryModel->getMaxFollowedCategories()) {
                throw new ClientException('Already following the maximum number of categories.');
            }
        }

        $this->categoryModel->follow($userID, $id, $body['followed']);

        $result = $out->validate([
            'followed' => $this->categoryModel->isFollowed($userID, $id)
        ]);
        return $result;
    }

    /**
     * Normalize request data to be passed to a model.
     *
     * @param array $request
     * @return array
     */
    public function normalizeInput(array $request) {
        $request = ApiUtils::convertInputKeys($request);

        if (array_key_exists('Urlcode', $request)) {
            $request['UrlCode'] = $request['Urlcode'];
            unset($request['Urlcode']);
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
    public function normalizeOutput(array $dbRecord, $expand = []) {
        $row = $this->categoryModel->normalizeRow($dbRecord, $expand);
        $row['breadcrumbs'] = $this->breadcrumbModel->getForRecord(new ForumCategoryRecordType($dbRecord['CategoryID']));
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
    private function updateParent($categoryID, $parentCategoryID) {
        if ($categoryID == $parentCategoryID) {
            throw new ClientException('A category cannot be the parent of itself.');
        }

        if ($parentCategoryID < 1 && $parentCategoryID !== -1) {
            throw new ClientException('parentCategoryID must be -1 or greater than zero.');
        }

        // Make sure the parent exists.
        try {
            $this->category($parentCategoryID);
        } catch (Exception $e) {
            throw new ClientException('The new parent category could not be found.');
        }

        // Make sure the category exists.
        $this->category($categoryID);

        $childTree = CategoryModel::flattenTree($this->categoryModel->getChildTree($categoryID));
        $children = array_column($childTree, 'CategoryID');
        if (in_array($parentCategoryID, $children)) {
            throw new ClientException('Cannot move a category under one of its own children.');
        }

        $this->categoryModel->setField($categoryID, 'ParentCategoryID', $parentCategoryID);

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
    public function schemaWithChildren() {
        $schema = $this->fullSchema();

        $schema->merge(Schema::parse([
            'depth:i',
            'children:a?' => $this->fullSchema()->merge(Schema::parse([
                'depth:i',
                'children:a'
            ]))
        ]));
        return $schema;
    }

    /**
     * Get a category schema with an additional field for a parent record.
     *
     * @param bool $expand
     * @return Schema
     */
    public function schemaWithParent($expand = false, $type = '') {
        $attributes = ['parentCategoryID:i|n' => 'Parent category ID.'];
        if ($expand) {
            $attributes['parent:o?'] = Schema::parse(['categoryID', 'name', 'urlcode', 'url'])
                ->add($this->fullSchema());
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
    private function getCategoriesWhere(array $where, $limit, $offset, $order = '', bool $filter = true): array {
        $dirtyRecords = $where[DirtyRecordModel::DIRTY_RECORD_OPT] ?? false;
        if ($dirtyRecords) {
            $this->categoryModel->applyDirtyWheres();
            unset($where[DirtyRecordModel::DIRTY_RECORD_OPT]);
            $categories = $this->categoryModel->getWhere($where, $order, '', $limit, $offset)
                ->resultArray();
        } else {
            $categories = $this->categoryModel
                ->getWhere($where, $order, '', $limit, $offset)
                ->resultArray();
        }

        // Index by ID for category calculation functions.
        $categories = array_column($categories, null, 'CategoryID');
        // Drop off the root category.
        unset($categories[-1]);

        $categories = $this->categoryModel->flattenCategories($categories);
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
}
