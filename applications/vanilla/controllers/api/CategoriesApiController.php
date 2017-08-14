<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;
use Vanilla\Utility\CapitalCaseScheme;

/**
 * API Controller for the `/categories` resource.
 */
class CategoriesApiController extends AbstractApiController {

    /** @var CapitalCaseScheme */
    private $caseScheme;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var Schema */
    private $categoryPostSchema;

    /** @var  Schema */
    private $categorySchema;

    /** @var Schema */
    private $idParamSchema;

    /**
     * CategoriessApiController constructor.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel) {
        $this->categoryModel = $categoryModel;
        $this->caseScheme = new CapitalCaseScheme();
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
            $fields = ['name', 'parentCategoryID', 'urlCode'];
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
            'description:s' => [
                'description' => 'The description of the category.',
                'minLength' => 0,
                'allowNull' => true
            ],
            'urlCode:s' => 'The URL code of the category.',
            'url:s' => 'The URL to the category.',
            'countDiscussions:i' => 'Total discussions in the category.',
            'countComments:i' => 'Total comments in the category.',
            'countAllDiscussions:i' => 'Total of all discussions in a category and its children.',
            'countAllComments:i' => 'Total of all comments in a category and its children.'
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
        $this->prepareRow($row);

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
            'categoryID', 'name', 'parentCategoryID', 'urlCode', 'description'
        ])->add($this->fullSchema()), 'out');

        $row = $this->category($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Search categories.
     *
     * @param array $query The query string.
     * @return array
     */
    public function get_search(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'query:s' => 'Category name filter.',
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->categoryModel->getMaxPages()
            ],
            'limit:i?' => [
                'description' => 'The number of items per page.',
                'default' => $this->categoryModel->getDefaultLimit(),
                'minimum' => 1,
                'maximum' => 200
            ],
            'expand:b?' => [
                'default' => false,
                'description' => 'Expand with the parent record.'
            ]
        ]);
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
            $this->prepareRow($row);
        }

        $result = $out->validate($rows);
        return $result;
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
     * @return array
     */
    public function index(array $query) {
        $this->permission('Garden.SignIn.Allow');

        $in = $this->schema([
            'parentCategoryID:i?' => 'Parent category ID.',
            'parentCategoryCode:s?' => 'Parent category URL code.',
            'maxDepth:i?' => [
                'description' => '',
                'default' => 2
            ],
            'page:i?' => [
                'description' => 'Page number.',
                'default' => 1,
                'minimum' => 1,
                'maximum' => $this->categoryModel->getMaxPages()
            ],
        ], 'in')->setDescription('List categories.');
        $out = $this->schema([':a' => $this->schemaWithChildren()], 'out');

        $query = $in->validate($query);
        if (array_key_exists('parentCategoryID', $query)) {
            $parent = $this->category($query['parentCategoryID']);
        } elseif (array_key_exists('parentCategoryCode', $query)) {
            $parent = $this->category($query['parentCategoryCode']);
        } else {
            $parent = $this->category(-1);
        }

        if ($parent['DisplayAs'] === 'Flat') {
            list($offset, $limit) = offsetLimit("p{$query['page']}", $this->categoryModel->getDefaultLimit());
            $categories = $this->categoryModel->getTreeAsFlat(
                $parent['CategoryID'],
                $offset,
                $limit
            );
        } else {
            $categories = $this->categoryModel->getTree($parent['CategoryID'], ['maxdepth' => $query['maxDepth']]);
        }

        return $out->validate($categories);
    }

    /**
     * Prepare data for output.
     *
     * @param array $row
     */
    public function prepareRow(array &$row) {
        if ($row['ParentCategoryID'] <= 0) {
            $row['ParentCategoryID'] = null;
        }
        $row['Description'] = $row['Description'] ?: '';
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

        $in = $this->categoryPostSchema('in', ['description'])->setDescription('Update a category.');
        $out = $this->schemaWithParent('out');

        $body = $in->validate($body, true);
        // If a row associated with this ID cannot be found, a "not found" exception will be thrown.
        $this->category($id);
        $categoryData = $this->caseScheme->convertArrayKeys($body);
        $this->categoryModel->setField($id, $categoryData);
        $row = $this->category($id);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add a category.
     *
     * @param array $body The request body.
     * @throws ServerException if the category could not be created.
     * @return Data
     */
    public function post(array $body) {
        $this->permission('Garden.Settings.Manage');

        $in = $this->schema($this->categoryPostSchema(), 'in')->setDescription('Add a category.');
        $out = $this->schema($this->schemaWithParent(), 'out');

        $body = $in->validate($body);

        $categoryData = $this->caseScheme->convertArrayKeys($body);
        $id = $this->categoryModel->save($categoryData);

        if (!$id) {
            throw new ServerException('Unable to add category.', 500);
        }

        $row = $this->category($id);
        $this->prepareRow($row);
        $result = $out->validate($row);
        return new Data($result, 201);
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
    public function schemaWithParent($expand = false) {
        $attributes = ['parentCategoryID:i|n' => 'Parent category ID.'];
        if ($expand) {
            $attributes['parent:o?'] = Schema::parse(['categoryID', 'name', 'urlCode', 'url'])
                ->add($this->fullSchema());
        }
        $schema = $this->fullSchema();
        $result = $schema->merge(Schema::parse($attributes));
        return $result;
    }
}
