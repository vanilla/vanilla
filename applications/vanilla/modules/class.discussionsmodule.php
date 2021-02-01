<?php
/**
 * Discussions module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
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

    /** @var bool */
    public $showTitle = true;

    /** @var bool Whether to show the discussion author avatar. */
    private $showPhotos = false;

    /** @var string */
    private $title = 'Recent Discussions';

    /**
     * Render the full discussion item instead of the minimal module version.
     *
     * @var bool
     */
    private $fullView = false;

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
     * @param bool $fullView
     */
    public function setFullView(bool $fullView): void {
        $this->fullView = $fullView;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        $this->title = $title;
    }

    /**
     * @return bool
     */
    public function isFullView(): bool {
        return $this->fullView;
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
            $visibleCategoryIDs = CategoryModel::instance()->getVisibleCategoryIDs(['filterHideDiscussions' => true]);
            if ($visibleCategoryIDs !== true) {
                $categoryIDs = array_intersect($visibleCategoryIDs, $categoryIDs);
            }
            $where['d.CategoryID'] = $categoryIDs;
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
     *
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
