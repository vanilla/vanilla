<?php
/**
 * Bookmarked module
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
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
            $BookmarkIDs = Gdn::sql()
                ->select('DiscussionID')
                ->from('UserDiscussion')
                ->where('UserID', Gdn::session()->UserID)
                ->where('Bookmarked', 1)
                ->get()->resultArray();
            $BookmarkIDs = consolidateArrayValuesByKey($BookmarkIDs, 'DiscussionID');

            if (count($BookmarkIDs)) {
                $DiscussionModel = new DiscussionModel();
                DiscussionModel::CategoryPermissions();

                $DiscussionModel->SQL->whereIn('d.DiscussionID', $BookmarkIDs);

                $Bookmarks = $DiscussionModel->get(
                    0,
                    $this->Limit,
                    array('w.Bookmarked' => '1')
                );
                $this->setData('Bookmarks', $Bookmarks);
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
            $this->GetData();
        }

        $Bookmarks = $this->data('Bookmarks');

        if (is_object($Bookmarks) && ($Bookmarks->numRows() > 0 || $this->Help)) {
            return parent::ToString();
        }

        return '';
    }
}
