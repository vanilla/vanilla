<?php
/**
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

use Garden\Schema\Schema;
use Garden\Web\Data;
use Garden\Web\Exception\NotFoundException;
use Garden\Web\Exception\ServerException;

/**
 * API Controller for the `/categories` resource.
 */
class CategoriesAPIController extends AbstractApiController {

    /** @var CategoryModel */
    private $categoryModel;

    /** @var  Schema */
    private $categorySchema;

    /**
     * CommentsApiController constructor.
     *
     * @param CategoryModel $categoryModel
     */
    public function __construct(CategoryModel $categoryModel) {
        $this->categoryModel = $categoryModel;
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
                'minLength' => 0
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
}
