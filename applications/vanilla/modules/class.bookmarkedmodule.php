<?php
/**
 * Bookmarked module
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

/**
 * Renders recently active bookmarked discussions.
 */
class BookmarkedModule extends Gdn_Module {

    /** @var int Display limit. */
    public $Limit = 10;

    /** @var bool */
    public $Help = false;

    /** @var string */
    public $ListID = 'Bookmark_List';

    public function __construct() {
        parent::__construct();
        $this->_ApplicationFolder = 'vanilla';
        $this->Visible = c('Vanilla.Modules.ShowBookmarkedModule', true);
    }

    public function getData() {
        if (Gdn::session()->isValid()) {
            $bookmarkIDs = Gdn::sql()
                ->select('DiscussionID')
                ->from('UserDiscussion')
                ->where('UserID', Gdn::session()->UserID)
                ->where('Bookmarked', 1)
                ->get()->resultArray();
            $bookmarkIDs = array_column($bookmarkIDs, 'DiscussionID');

            if (count($bookmarkIDs)) {
                $discussionModel = new DiscussionModel();
                DiscussionModel::categoryPermissions();

                $bookmarks = $discussionModel->get(
                    0,
                    $this->Limit,
                    ['d.DiscussionID' => $bookmarkIDs]
                );
                $this->setData('Bookmarks', $bookmarks);
            } else {
                $this->setData('Bookmarks', new Gdn_DataSet());
            }
        }
    }

    public function assetTarget() {
        return 'Panel';
    }

    public function toString() {
        if (!$this->data('Bookmarks')) {
            $this->getData();
        }

        $bookmarks = $this->data('Bookmarks');

        if (is_object($bookmarks) && ($bookmarks->numRows() > 0 || $this->Help)) {
            return parent::toString();
        }

        return '';
    }
}
