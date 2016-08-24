<?php

class FlatCategoryModule extends Gdn_Module {

    const DEFAULT_LIMIT = 10;

    /**
     * @var string|int Target category's slug or ID.
     */
    public $categoryID;

    /**
     * @var array The category for this module instance.
     */
    private $category;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

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
        $children = [];

        if (is_array($category)) {
            $children = $this->categoryModel->getTreeAsFlat(
                $category['CategoryID'],
                0,
                $this->getLimit()
            );
        }

        return $children;
    }

    public function getLimit() {
        $limit = $this->limit;

        return is_numeric($limit) && $limit > 0 ? (int)$limit : $this::DEFAULT_LIMIT;
    }
}
