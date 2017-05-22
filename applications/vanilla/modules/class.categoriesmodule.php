<?php
/**
 * Categories module
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the discussion categories.
 */
class CategoriesModule extends Gdn_Module {

    /** @var int Inclusive. */
    public $startDepth = 1;

    /** @var int Inclusive. */
    public $endDepth;

    /** @var bool Whether or not to collapse categories that contain other categories. */
    public $collapseCategories = true;

    /**
     * @var int|null The ID of the root category.
     */
    public $root = null;

    public function __construct($Sender = '') {
        parent::__construct($Sender);
        $this->_ApplicationFolder = 'vanilla';

        $this->Visible = c('Vanilla.Categories.Use') && !c('Vanilla.Categories.HideModule');
    }

    public function assetTarget() {
        return 'Panel';
    }

    /**
     * Get the data for this module.
     */
    protected function getData() {
        // Allow plugins to set different data.
        $this->fireEvent('GetData');
        if ($this->Data) {
            return;
        }

        $categoryModel = new CategoryModel();
        $categories = $categoryModel
            ->setJoinUserCategory(true)
            ->getChildTree($this->root, ['collapseCategories' => $this->collapseCategories]);
        $categories = CategoryModel::flattenTree($categories);

        $categories = array_filter($categories, function ($category) {
            return val('PermsDiscussionsView', $category) && val('Following', $category);
        });

        $data = new Gdn_DataSet($categories, DATASET_TYPE_ARRAY);
        $data->datasetType(DATASET_TYPE_OBJECT);
        $this->Data = $data;
    }

    public function filterDepth(&$Categories, $startDepth, $endDepth) {
        if ($startDepth != 1 || $endDepth) {
            foreach ($Categories as $i => $Category) {
                if (val('Depth', $Category) < $startDepth || ($endDepth && val('Depth', $Category) > $endDepth)) {
                    unset($Categories[$i]);
                }
            }
        }
    }

    public function toString() {
        if (!$this->Data) {
            $this->GetData();
        }

        $this->filterDepth($this->Data->result(), $this->startDepth, $this->endDepth);

        return parent::ToString();
    }
}
