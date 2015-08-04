<?php
/**
 * Discussions module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
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
        $this->fireEvent('Init');
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of discussions to display.
     */
    public function getData($limit = false) {
        if (!$limit) {
            $limit = $this->Limit;
        }

        $discussionModel = new DiscussionModel();

        $categoryIDs = $this->getCategoryIDs();
        $where = array('Announce' => 'all');

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $discussionModel->Watching = true;
        }

        $this->setData('Discussions', $discussionModel->get(0, $limit, $where));
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!$this->data('Discussions')) {
            $this->GetData();
        }

        require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

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
