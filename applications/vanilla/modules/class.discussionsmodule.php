<?php
/**
 * Discussions module
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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

    /** @var bool Whether to show the discussion author avatar. */
    private $showPhotos = false;

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
     * @param $showPhotos Whether to show the discussion author avatar.
     * @return DiscussionsModule
     */
    public function setShowPhotos($showPhotos) {
        $this->showPhotos = $showPhotos;
        return $this;
    }

    /**
     * @return bool Whether to show the discussion author avatar.
     */
    public function getShowPhotos() {
        return $this->showPhotos;
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
        $where = ['Announce' => 'all'];

        if ($categoryIDs) {
            $where['d.CategoryID'] = CategoryModel::filterCategoryPermissions($categoryIDs);
        } else {
            $visibleCategoriesResult = CategoryModel::instance()->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
            if ($visibleCategoriesResult !== true) {
                $where['d.CategoryID'] = $visibleCategoriesResult;
            }
        }

        $this->setData('Discussions', $discussionModel->get(0, $limit, $where));
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!$this->data('Discussions')) {
            $this->getData();
        }

        require_once Gdn::controller()->fetchViewLocation('helper_functions', 'Discussions', 'Vanilla');

        return parent::toString();
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
