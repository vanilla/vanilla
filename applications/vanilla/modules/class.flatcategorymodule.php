<?php

class FlatCategoryModule extends Gdn_Module {

    /**
     * Default limit for categories.
     */
    const DEFAULT_LIMIT = 10;

    /**
     * @var string|int Category identifier.  Can be the slug or the category's ID.
     */
    public $categoryID;

    /**
     * @var array An associative array representing a category record.
     */
    private $category;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * @var array An array of category records beneath the current category.
     */
    private $children;

    /**
     * @var string Filter output by name.
     */
    public $filter;

    /**
     * @var int Limit on the number of categories displayed.
     */
    public $limit;

    /**
     * FlatCategoryModule constructor.
     *
     * @param string|Gdn_Controller $sender
     * @param bool|string $applicationFolder
     */
    public function __construct($sender = '', $applicationFolder = false) {
        parent::__construct($sender, $applicationFolder);

        $this->categoryModel = new CategoryModel();
        $this->limit = $this::DEFAULT_LIMIT;

        // If this is coming from the module controller, populate some properties by query parameters.
        if ($sender instanceof ModuleController) {
            $paramWhitelist = [
                'categoryID' => Gdn::request()->get('categoryID', Gdn::request()->get('CategoryID')),
                'filter' => Gdn::request()->get('filter', Gdn::request()->get('Filter')),
                'limit' => Gdn::request()->get('limit', Gdn::request()->get('Limit'))
            ];

            foreach ($paramWhitelist as $property => $value) {
                if ($value) {
                    $this->$property = $value;
                }
            }
        }
    }

    /**
     * Returns the name of the asset where this component should be rendered.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    /**
     * Get data for the configured category.
     *
     * @return array|null Array if the configured category is valid.  Otherwise, null.
     */
    public function getCategory() {
        if (!isset($this->category)) {
            $this->category = CategoryModel::categories($this->categoryID);
        }

        return $this->category;
    }

    /**
     * Get a list of immediate children for the configured category.
     *
     * @return array|null
     */
    public function getChildren() {
        $category = $this->getCategory();

        if ($category && !$this->children) {
            $this->children = $this->categoryModel->getTreeAsFlat(
                val('CategoryID', $category),
                0,
                $this->getLimit(),
                $this->getFilter()
            );
            $this->categoryModel->joinRecent($this->children);
        }

        return $this->children;
    }

    /**
     * Get the value to be used for filtering categories by name, if any.
     *
     * @return null|string
     */
    public function getFilter() {
        $filter = null;

        if ($this->filter) {
            $filter = (string)$this->filter;
        }

        return $filter;
    }

    /**
     * Get the configured record limit.
     *
     * @return int
     */
    public function getLimit() {
        $limit = $this->limit;

        return is_numeric($limit) && $limit > 0 ? (int)$limit : $this::DEFAULT_LIMIT;
    }

    /**
     * Returns the component as a string to be rendered to the screen.
     *
     * @return string
     */
    public function toString() {
        // Setup
        $this->setData('Categories', $this->getChildren());
        $this->setData('Layout', c('Vanilla.Categories.Layout', 'modern'));
        $this->setData('ParentCategory', $this->getCategory());

        // If our category isn't valid, or we have no child categories to display, then display nothing.
        if (!$this->data('ParentCategory') || !$this->data('Categories')) {
            return '';
        }

        // Vanilla's category helper functions are beneficial in creating markdown in the views.
        require_once Gdn::controller()->fetchViewLocation('helper_functions', 'categories', 'vanilla');
        return parent::toString();
    }
}
