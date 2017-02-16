<?php

/**
 * Class CategoryFilterModule
 *
 * Searches for categories whose names match a given string.
 */
class CategoryFilterModule extends Gdn_Module {

    /**
     * Default limit for categories.
     */
    const DEFAULT_LIMIT = 50;

    /**
     * @var string|int Category identifier.  Can be the slug or the category's ID.
     */
    private $categoryID;

    /**
     * @var array An associative array representing a category record. The parent category that we retrieve records for.
     */
    private $category;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * @var array An array of category records beneath the current category.
     */
    private $flatCategoryChildren;

    /**
     * @var string Filter output by name.
     */
    private $filter;

    /**
     * @var int Limit on the number of categories displayed.
     */
    private $limit;

    /**
     * @var boolean Whether to include display-as-heading categories.
     */
    private $showHeadings;

    /**
     * CategoryFilterModule constructor.
     *
     * @param string|Gdn_Controller $sender
     * @param bool|string $applicationFolder
     */
    public function __construct($sender = '', $applicationFolder = false) {
        parent::__construct($sender, $applicationFolder);

        $this->categoryModel = new CategoryModel();
        $this->limit = $this::DEFAULT_LIMIT;
        $this->showHeadings = true;

        $supportedViews = ['categoryfilter-dashboard'];

        // If this is coming from the module controller, populate some properties by query parameters.
        if ($sender instanceof ModuleController) {
            $paramWhitelist = [
                'categoryID' => Gdn::request()->get('categoryID', Gdn::request()->get('CategoryID')),
                'filter' => Gdn::request()->get('filter', Gdn::request()->get('Filter')),
                'limit' => Gdn::request()->get('limit', Gdn::request()->get('Limit')),
                'showHeadings' => Gdn::request()->get('showHeadings', Gdn::request()->get('ShowHeadings'))
            ];

            foreach ($paramWhitelist as $property => $value) {
                if ($value) {
                    $this->$property = $value;
                }
            }

            $view = Gdn::request()->get('view', Gdn::request()->get('View'));

            if ($view && in_array($view, $supportedViews)) {
                $this->setView($view);
            }
        }

        if (!$this->categoryID) {
            $this->categoryID = val('CategoryID', $this->_Sender->data('Category', []), -1);
        }
    }

    /**
     * @return int|string
     */
    public function getCategoryID() {
        return $this->categoryID;
    }

    /**
     * @param int|string $categoryID
     * @return $this
     */
    public function setCategoryID($categoryID) {
        $this->categoryID = $categoryID;
        return $this;
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
    private function getFlatCategoryChildren() {
        $category = $this->getCategory();

        if ($category && !$this->flatCategoryChildren) {
            $this->flatCategoryChildren = $this->categoryModel->getTreeAsFlat(
                val('CategoryID', $category),
                0,
                $this->getLimit(),
                $this->getFilter()
            );
            $this->categoryModel->joinRecent($this->flatCategoryChildren);
        }

        return $this->flatCategoryChildren;
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
     * @param string $filter
     * @return $this
     */
    public function setFilter($filter) {
        $this->filter = $filter;
        return $this;
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
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit) {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Renders the filter input box for the dashboard view.
     *
     * @param array $options
     * @return string
     */
    public function categoryFilterBox(array $options = []) {
        $form = new Gdn_Form('');

        $containerSelector = isset($options['containerSelector']) ? $options['containerSelector'] : '.category-filter-container';
        $cssClass = isset($options['cssClass']) ? $options['cssClass'] : 'form-control';
        $useSearchInput = isset($options['useSearchInput']) ? $options['useSearchInput'] : true;
        $hideContainerSelector = isset($options['hideContainerSelector']) ? $options['hideContainerSelector'] : '';

        $attr = [
            'class' => 'js-category-filter-input '.$cssClass,
            'placeholder' => t('Search'),
            'data-category-id' => $this->categoryID,
            'data-limit' => $this->getLimit(),
            'data-container' => $containerSelector
        ];

        if ($hideContainerSelector) {
            $attr['data-hide-container'] = $hideContainerSelector;
        }

        if ($useSearchInput) {
            return $form->searchInput('', '', $attr);
        }

        return $form->input('', '', $attr);
    }

    /**
     * Filters a flattened category list.
     *
     * @param array $categories A flattened list of categories to filter by name.
     * @return array A filtered list of categories.
     */
    private function filterCategories(array $categories) {
        $count = 0;
        $limit = $this->getLimit();
        $filter = $this->getFilter();
        $filteredCategories = [];

        if ($filter === null) {
            return array_slice($categories, 0, $limit);
        } else {
            $filter = strtolower($this->getFilter());
        }

        foreach ($categories as $category) {
            if ($count === $limit) {
                continue;
            }

            // See if we show heading-type categories
            $condition = $this->showHeadings || val('DisplayAs', $category) !== 'Heading';

            $name = strtolower(val('Name', $category));

            if ($condition && (strpos($name, $filter) !== false)) {
                $filteredCategories[] = $category;
                $count++;
            }
        }

        return $filteredCategories;
    }

    /**
     * @throws Exception
     */
    private function getCategories() {
        $category = $this->getCategory();
        $parentDisplayAs = val('DisplayAs', $category);

        if ($parentDisplayAs === 'Flat') {
            $categories = $this->getFlatCategoryChildren();
        } else {
            $options = ['maxdepth' => 10, 'collapsecategories' => true];
            $categories = $this->categoryModel->getChildTree($this->getCategoryID(), $options);
            $categories = $this->categoryModel->flattenTree($categories);
            $categories = $this->filterCategories($categories);
        }

        return $categories;
    }

    /**
     * Returns the component as a string to be rendered to the screen.
     *
     * @return string
     */
    public function toString() {
        // Setup
        $this->setData('Layout', c('Vanilla.Categories.Layout', 'modern'));
        $this->setData('ParentCategory', $this->getCategory());
        $this->setData('Categories', $this->getCategories());

        // Vanilla's category helper functions are beneficial in creating markdown in the views.
        require_once Gdn::controller()->fetchViewLocation('helper_functions', 'categories', 'vanilla');
        require_once Gdn::controller()->fetchViewLocation('category-settings-functions', 'vanillasettings', 'vanilla');
        return parent::toString();
    }
}
