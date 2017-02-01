<?php
/**
 * User discussions module
 *
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.3
 */

/**
 * Renders recent discussions started by a specified user.
 */
class UserDiscussionsModule extends Gdn_Module {

    /** @var int Display limit. */
    public $limit = 10;

    /** @var bool Whether to show the discussion author avatar. */
    private $showPhotos = false;

    /** @var array Limit the discussions to just this list of categories, checked for view permission. */
    protected $categoryIDs;

    /** @var  int The ID of the user for whom you are showing the comments. */
    public $userID = null;

    /**
     *
     *
     * @throws Exception
     */
    public function __construct($sender) {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
        $this->fireEvent('Init');
        //If you are being executed from the profile controller, get the UserID.
        if (strtolower(val('ControllerName', $sender)) === 'profilecontroller') {
            $this->userID = $sender->User->UserID;
        }
    }

    /**
     * @param $showPhotos Whether to show the comment author avatar.
     * @return CommentsModule
     */
    public function setShowPhotos($showPhotos) {
        $this->showPhotos = $showPhotos;
        return $this;
    }

    /**
     * @return bool Whether to show the comment author avatar.
     */
    public function getShowPhotos() {
        return $this->showPhotos;
    }

    /**
     * Get the data for the module.
     *
     * @param int|bool $limit Override the number of comments to display.
     */
    public function getData($limit = false) {
        if (!$limit) {
            $limit = $this->limit;
        }
        $discussionModel = new DiscussionModel();
        $discussions  = $discussionModel->getByUser($this->userID, $limit, 0, false);
        $this->setData('Discussions', $discussions);
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        // If there is no userID show nothing.
        if (!$this->userID) {
            return;
        }

        if (!$this->data('Discussions')) {
            $this->getData();
        }
//        $this->view = 'discussions';

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
