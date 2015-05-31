<?php
/**
 * Categories module
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
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

    public function __construct($Sender = '') {
        parent::__construct($Sender);
        $this->_ApplicationFolder = 'vanilla';

        $this->Visible = C('Vanilla.Categories.Use') && !C('Vanilla.Categories.HideModule');
    }

    public function AssetTarget() {
        return 'Panel';
    }

    /**
     * Get the data for this module.
     */
    protected function GetData() {
        // Allow plugins to set different data.
        $this->FireEvent('GetData');
        if ($this->Data) {
            return;
        }

        $Categories = CategoryModel::Categories();
        $Categories2 = $Categories;

        // Filter out the categories we aren't watching.
        foreach ($Categories2 as $i => $Category) {
            if (!$Category['PermsDiscussionsView'] || !$Category['Following']) {
                unset($Categories[$i]);
            }
        }

        $Data = new Gdn_DataSet($Categories);
        $Data->DatasetType(DATASET_TYPE_ARRAY);
        $Data->DatasetType(DATASET_TYPE_OBJECT);
        $this->Data = $Data;
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

    public function ToString() {
        if (!$this->Data) {
            $this->GetData();
        }

        $this->filterDepth($this->Data->Result(), $this->startDepth, $this->endDepth);

        return parent::ToString();
    }
}
