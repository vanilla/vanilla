<?php
/**
 * FollowedCategoriesModule module
 *
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders the followed categories.
 */
class FollowedCategoriesModule extends Gdn_Module {

    /** @var int Count of categories to display. */
    public $limit;

    /**
     * @var bool
     */
    public $hasMoreResult = false;

    /**
     * @var CategoryModel
     */
    private $categoryModel;

    /**
     * @var string
     */
    public $transientKey;

    /**
     * FollowedCategoriesModule constructor.
     *
     * @param int $limit Count of categories to display.
     */
    public function __construct($limit = 5) {
        parent::__construct('');
        $this->_ApplicationFolder = 'vanilla';
        $this->limit = $limit;
        $this->categoryModel = CategoryModel::instance();
    }

    /**
     * The target asset to be rendered to if not called in a template.
     *
     * @return string
     */
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
        $session = Gdn::session();
        $userId = $session->isValid() ? $session->UserID : null;
        if (!$userId) {
            return;
        }
        $userCategories = $this->categoryModel->getFollowed($userId);
        $followedCategories = [];
        foreach ($userCategories as $userCategory) {
            $followedCategories[] = CategoryModel::categories($userCategory['CategoryID']);
        }
        $this->Data = new Gdn_DataSet($followedCategories, DATASET_TYPE_ARRAY);
        $this->hasMoreResult = count($userCategories) > $this->limit;
        $this->transientKey = $session->transientKey();
    }

    /**
     * Returns the followed categories component as a string to be rendered to the screen.
     *
     * @return string
     */
    public function toString() {
        if (!$this->Data) {
            $this->getData();
        }
        $this->Visible = Gdn::config('Vanilla.EnableCategoryFollowing') && Gdn::session()->isValid();

        return parent::toString();
    }
}
