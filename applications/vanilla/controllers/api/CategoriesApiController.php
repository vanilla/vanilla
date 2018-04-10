<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\ApiUtils;

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

    /**
     * CategoriesApiController constructor.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel) {
        $this->categoryModel = $categoryModel;
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
            $fields = ['name', 'parentCategoryID?', 'urlcode', 'displayAs?', 'customPermissions?'];
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
    protected function fullSchema() {
        return Schema::parse([
            'categoryID:i' => 'The ID of the category.',
            'name:s' => 'The name of the category.',
            'description:s|n' => [
                'description' => 'The description of the category.',
                'minLength' => 0,
            ],
            'parentCategoryID:i|n' => 'Parent category ID.',
            'customPermissions:b' => 'Are custom permissions set for this category?',
            'isArchived:b' => 'The archived state of this category.',
            'urlcode:s' => 'The URL code of the category.',
            'url:s' => 'The URL to the category.',
            'displayAs:s' => [
                'description' => 'The display style of the category.',
                'enum' => ['categories', 'discussions', 'flat', 'heading'],
                'default' => 'discussions'
            ],
            'countCategories:i' => 'Total number of child categories.',
            'countDiscussions:i' => 'Total discussions in the category.',
            'countComments:i' => 'Total comments in the category.',
            'countAllDiscussions:i' => 'Total of all discussions in a category and its children.',
            'countAllComments:i' => 'Total of all comments in a category and its children.',
            'followed:b?' => 'Is the category being followed by the current user?'
        ]);
    }

    /**
     * Get a single category.
     *
     * @param int $id The ID of the category.
     * @throws NotFoundException if unable to find the category.
     * @return array
     */
    public function get($id) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->idParamSchema()->setDescription('Get a category.');
        $out = $this->schema($this->schemaWithParent(), 'out');

        $row = $this->category($id);
        $row = $this->normalizeOutput($row);

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
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'query:s' => 'Category name filter.',
            'page:i?' => [
                'description' => 'Page number. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination).',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->categoryModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->categoryModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 200
            ],
            'expand:b?' => [
                'default' => false,
                'description' => 'Expand with the parent record.'
            ]
        ])->setDescription('Search categories.');
        $out = $this->schema([':a' => $this->schemaWithParent($query['expand'])], 'out');

        $query = $in->validate($query);

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);
        $rows = $this->categoryModel->searchByName(
            $query['query'],
            $query['expand'],
            $limit,
            $offset
        );

        foreach ($rows as &$row) {
            $row = $this->normalizeOutput($row);
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
                Schema::parse(['id:i' => 'The category ID.']),
                $type
            );
        }
        return $this->schema($this->idParamSchema, $type);
    }

    /**
     * List categories.
     *
     * @param array $query The query string.
     * @return Data
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'parentCategoryID:i?' => 'Parent category ID.',
            'parentCategoryCode:s?' => 'Parent category URL code.',
            'followed:b' => [
                'default' => false,
                'description' => 'Only list categories followed by the current user.',
            ],
            'maxDepth:i?' => [
                'description' => '',
                'default' => 2,
            ],
            'archived:b|n' => [
                'description' => 'Filter by archived status of a category. True for archived only. False for no archived categories. Not compatible with followed filter.',
                'default' => false
            ],
            'page:i?' => [
                'description' => 'Page number. Works with flat and followed categories. See [Pagination](https://docs.vanillaforums.com/apiv2/#pagination)',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->categoryModel->getMaxPages(),
            ],
            'limit:i?' => [
                'description' => 'Desired number of items per page.',
                'default' => $this->categoryModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 100,
            ],
        ], 'in')->setDescription('List categories.');
        $out = $this->schema([':a' => $this->schemaWithChildren()], 'out');

        $query = $in->validate($query);
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

        list($offset, $limit) = offsetLimit("p{$query['page']}", $query['limit']);

        if ($query['followed']) {
            $categories = $this->categoryModel
                ->getWhere(['Followed' => true], '', 'asc', $limit, $offset)
                ->resultArray();

            // Index by ID for category calculation functions.
            $categories = array_column($categories, null, 'CategoryID');
            $categories = $this->categoryModel->flattenCategories($categories);
            // Reset indexes for proper output detection as an indexed array.
            $categories = array_values($categories);

            $totalCountCallBack = function() {
                return $this->categoryModel->getCount(['Followed' => true]);
            };
        } elseif ($parent['DisplayAs'] === 'Flat') {
            $categories = $this->categoryModel->getTreeAsFlat(
                $parent['CategoryID'],
                $offset,
                $limit
            );

            $totalCountCallBack = function() use ($parent) {
                return $parent['CountCategories'];
            };
        } else {
            $categories = $this->categoryModel->getTree(
                $parent['CategoryID'],
                [
                    'maxdepth' => $query['maxDepth'],
                ]
            );

            // Filter tree by the category "archived" fields.
            if ($query['archived'] !== null) {
                $categories = $this->archiveFilter($categories, $query['archived'] ? 0 : 1);
            }
        }
        $this->categoryModel->setJoinUserCategory($joinUserCategory);
        $categories = array_map([$this, 'normalizeOutput'], $categories);

        $result = $out->validate($categories);

        if (isset($totalCountCallBack)) {
            $paging = ApiUtils::numberedPagerInfo($totalCountCallBack(), '/api/v2/categories', $query, $in);
        } else {
            $paging = [];
        }

        return new Data($result, ['paging' => $paging]);
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
     * @return array Return a Schema record.
     */
    public function normalizeOutput(array $dbRecord) {
        if ($dbRecord['ParentCategoryID'] <= 0) {
            $dbRecord['ParentCategoryID'] = null;
        }
        $dbRecord['CustomPermissions'] = ($dbRecord['PermissionCategoryID'] === $dbRecord['CategoryID']);
        $dbRecord['Description'] = $dbRecord['Description'] ?: '';
        $dbRecord['DisplayAs'] = strtolower($dbRecord['DisplayAs']);

        if (!empty($dbRecord['Children']) && is_array($dbRecord['Children'])) {
            $dbRecord['Children'] = array_map([$this, 'normalizeOutput'], $dbRecord['Children']);
        }

        $dbRecord['isArchived'] = $dbRecord['Archived'];

        $schemaRecord = ApiUtils::convertOutputKeys($dbRecord);
        return $schemaRecord;
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
    private function updateParent($categoryID, $parentCategoryID, $rebuildTree = true) {
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

        if ($rebuildTree) {
            $this->categoryModel->rebuildTree();
            $this->categoryModel->recalculateTree();
        }

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
}
