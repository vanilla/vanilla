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
        $this->Visible = C('Vanilla.Modules.ShowBookmarkedModule', true);
    }

    public function GetData() {
        if (Gdn::Session()->IsValid()) {
            $BookmarkIDs = Gdn::SQL()
                ->Select('DiscussionID')
                ->From('UserDiscussion')
                ->Where('UserID', Gdn::Session()->UserID)
                ->Where('Bookmarked', 1)
                ->Get()->ResultArray();
            $BookmarkIDs = ConsolidateArrayValuesByKey($BookmarkIDs, 'DiscussionID');

            if (count($BookmarkIDs)) {
                $DiscussionModel = new DiscussionModel();
                DiscussionModel::CategoryPermissions();

                $DiscussionModel->SQL->WhereIn('d.DiscussionID', $BookmarkIDs);

                $Bookmarks = $DiscussionModel->Get(
                    0,
                    $this->Limit,
                    array('w.Bookmarked' => '1')
                );
                $this->SetData('Bookmarks', $Bookmarks);
            } else {
                $this->SetData('Bookmarks', new Gdn_DataSet());
            }
        }
    }

    public function AssetTarget() {
        return 'Panel';
    }

    public function ToString() {
        if (!$this->Data('Bookmarks')) {
            $this->GetData();
        }

        $Bookmarks = $this->Data('Bookmarks');

        if (is_object($Bookmarks) && ($Bookmarks->NumRows() > 0 || $this->Help)) {
            return parent::ToString();
        }

        return '';
    }
}
