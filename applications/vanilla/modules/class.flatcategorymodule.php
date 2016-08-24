<?php

class FlatCategoryModule extends Gdn_Module {

    const DEFAULT_LIMIT = 10;

    /**
     * @var string|int
     */
    public $categoryID;

    /**
     * @var array
     */
    private $category;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * @var array
     */
    private $children;

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
    }

    /**
     * Returns the name of the asset where this component should be rendered.
     *
     * @return string
     */
    public function assetTarget() {
        return 'Content';
    }

    public function getCategory() {
        if (!isset($this->category)) {
            $this->category = CategoryModel::categories($this->categoryID);
        }

        return $this->category;
    }

    public function getChildren() {
        $category = $this->getCategory();

        if (!$this->children) {
            $this->children = $this->categoryModel->getTreeAsFlat(
                $category['CategoryID'],
                0,
                $this->getLimit()
            );
            $this->categoryModel->joinRecent($this->children);
        }

        return $this->children;
    }

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
        $this->setData('Categories', $this->getChildren());
        $this->setData('DoHeadings', c('Vanilla.Categories.DoHeadings'));
        $this->setData('Layout', c('Vanilla.Categories.Layout', 'modern'));
        $this->setData('ParentCategory', $this->getCategory());

        if (!$this->data('ParentCategory') || !$this->data('Categories')) {
            return '';
        }

        switch ($this->data('Layout')) {
            case 'table':
                $this->setView('flatcategory-table');
                break;
            case 'modern':
            default:
                $this->setView('flatcategory-modern');
        }

        require_once Gdn::controller()->fetchViewLocation('helper_functions', 'categories', 'vanilla');
        return parent::toString();
    }
}
