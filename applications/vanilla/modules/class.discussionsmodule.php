<?php
/**
 * Discussions module
 *
 * @copyright 2008-2015 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders recently active discussions
 */
class DiscussionsModule extends Gdn_Module {

    /** @var int Display limit. */
    public $Limit = 10;

    /** @var string  */
    public $Prefix = 'Discussion';

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $categoryIDs;

    /**
     *
     *
     * @throws Exception
     */
    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
        $this->FireEvent('Init');
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of discussions to display.
     */
    public function GetData($limit = FALSE) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $discussionModel = new DiscussionModel();

        $categoryIDs = $this->getCategoryIDs();
        $where = array('Announce' => 'all');

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = TRUE;
        }

        $this->SetData('Discussions', $discussionModel->Get(0, $limit, $where));
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (!$this->Data('Discussions')) {
            $this->GetData();
        }

        require_once Gdn::Controller()->FetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

        return parent::ToString();
    }

    /**
     * Get a list of category IDs to limit.
     *
     * @return array
     */
    public function getCategoryIDs() {
        return $this->categoryIDs;
    }

    /**
     * Set a list of category IDs to limit.
     *
     * @param array $categoryIDs
     */
    public function setCategoryIDs($categoryIDs) {
        $this->categoryIDs = $categoryIDs;
    }
}
