<?php
/**
 * 'All Viewed' plugin for Vanilla Forums.
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @author Oliver Chung <shoat@cs.washington.edu>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package AllViewed
 */

$PluginInfo['AllViewed'] = array(
    'Name' => 'All Viewed',
    'Description' => 'Allows users to mark all discussions as viewed and mark category viewed.',
    'Version' => '2.1',
    'Author' => "Lincoln Russell, Oliver Chung",
    'AuthorEmail' => 'lincoln@vanillaforums.com, shoat@cs.washington.edu',
    'AuthorUrl' => 'http://lincolnwebs.com',
    'License' => 'GNU GPLv2',
    'MobileFriendly' => true
);

/**
 * Allows users to mark all discussions as viewed and mark category viewed.
 *
 * AllViewed allows members to mark all discussions as viewed by clicking "Mark All Viewed"
 * in the main nav (path: /discussions/markallviewed)
 *
 * This resets their counters for how many comments were previously in the discussion.
 * Therefore, if there are 3 subsequent comments, the discussion will simply say "New",
 * not "3 New" because it no longer knows how many comments there were.
 *
 * Normally viewing the discussion will put it back on a "X New"-style counter.
 * This behavior is to circumvent potential problems with massive updates to the
 * UserDiscussion table when "Mark All Viewed" is clicked.
 *
 * v1.2
 * - Fixed "New" count jumping back to "Total" (rather than 1) after new comment if user hadn't actually viewed a discussion.
 * - Removed spurious config checks and &s
 * v1.3
 * - Made it possible to forgo the 'Mark All Viewed' option being added to menu automatically
 * - Cleanup spurious local variable assignments
 * - Documentation cleanup
 * v2.0
 * - Reimplemented using UserCategory's DateMarkedRead column
 * - Added Mark Category Read
 * - Redirects to previous page instead of /discussions
 * v2.1
 * - Added SpMarkAllViewed and SpMarkCategoryViewed sprites to links
 * - Cleaned up formatting to match majority
 */
class AllViewedPlugin extends Gdn_Plugin {

    /**
     * Adds "Mark All Viewed" to main menu.
     *
     * @since 1.0
     * @access public
     */
    public function base_render_before($Sender) {
        // Add "Mark All Viewed" to main menu
        if ($Sender->Menu && Gdn::session()->isValid()) {
            if (c('Plugins.AllViewed.ShowInMenu', true)) {
                $Sender->Menu->addLink('AllViewed', t('Mark All Viewed'), '/discussions/markallviewed');
            }
        }
    }

    /**
     * Adds "Mark All Viewed" and (conditionally) "Mark Category Viewed" to MeModule menu.
     *
     * @since 2.0
     * @access public
     */
    public function meModule_flyoutMenu_handler($Sender) {
        // Add "Mark All Viewed" to menu
        if (Gdn::session()->isValid()) {
            echo wrap(Anchor(sprite('SpMarkAllViewed').' '.t('Mark All Viewed'), '/discussions/markallviewed'), 'li', array('class' => 'MarkAllViewed'));

            $CategoryID = (int)(empty(Gdn::controller()->CategoryID) ? 0 : Gdn::controller()->CategoryID);
            if ($CategoryID > 0) {
                echo wrap(Anchor(sprite('SpMarkCategoryViewed').' '.t('Mark Category Viewed'), "/discussions/markcategoryviewed/{$CategoryID}"), 'li', array('class' => 'MarkCategoryViewed'));
            }
        }
    }

    /**
     * Helper function that actually sets the DateMarkedRead column in UserCategory
     *
     * @since 2.0
     * @access private
     */
    private function markCategoryRead($CategoryModel, $CategoryID) {
        $CategoryModel->saveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::toDateTime()));
    }

    /**
     * Allows user to mark all discussions in a specified category as viewed.
     *
     * @param DiscussionsController $Sender
     * @since 1.0
     * @access public
     */
    public function discussionsController_markCategoryViewed_create($Sender, $CategoryID) {
        if (strlen($CategoryID) > 0 && (int)$CategoryID > 0) {
            $CategoryModel = new CategoryModel();
            $this->markCategoryRead($CategoryModel, $CategoryID);
            $this->recursiveMarkCategoryRead($CategoryModel, CategoryModel::categories(), array($CategoryID));
        }

        redirect(Gdn::request()->getValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER'));
    }

    /**
     * Helper function to recursively mark categories as read based on a Category's ParentID.
     *
     * @since 2.0
     * @access private
     */
    private function recursiveMarkCategoryRead($CategoryModel, $UnprocessedCategories, $ParentIDs) {
        $CurrentUnprocessedCategories = array();
        $CurrentParentIDs = $ParentIDs;
        foreach ($UnprocessedCategories as $Category) {
            if (in_array($Category["ParentCategoryID"], $ParentIDs)) {
                $this->markCategoryRead($CategoryModel, $Category["CategoryID"]);
                // Don't add duplicate ParentIDs
                if (!in_array($Category["CategoryID"], $CurrentParentIDs)) {
                    $CurrentParentIDs[] = $Category["CategoryID"];
                }
            } else {
                // This keeps track of categories that we still need to check on recurse
                $CurrentUnprocessedCategories[] = $Category;
            }
        }
        // Base case: if we have not found any new parent ids, we don't need to recurse
        if (count($ParentIDs) != count($CurrentParentIDs)) {
            $this->recursiveMarkCategoryRead($CategoryModel, $CurrentUnprocessedCategories, $CurrentParentIDs);
        }
    }

    /**
     * Allows user to mark all discussions as viewed.
     *
     * @since 1.0
     * @access public
     */
    public function discussionsController_markAllViewed_create($Sender) {
        $CategoryModel = new CategoryModel();
        $this->markCategoryRead($CategoryModel, -1);
        $this->recursiveMarkCategoryRead($CategoryModel, CategoryModel::categories(), array(-1));
        redirect($_SERVER["HTTP_REFERER"]); // TODO nope
    }

    /**
     * Get the number of comments inserted since the given timestamp.
     *
     * @since 1.0
     * @access public
     */
    public function getCommentCountSince($DiscussionID, $DateAllViewed) {
        // Only for members
        if (!Gdn::session()->isValid()) {
            return;
        }

        // Validate DiscussionID
        $DiscussionID = (int)$DiscussionID;
        if (!$DiscussionID) {
            throw new Exception('A valid DiscussionID is required in GetCommentCountSince.');
        }

        // Get new comment count
        return Gdn::database()->sql()
            ->from('Comment c')
            ->where('DiscussionID', $DiscussionID)
            ->where('DateInserted >', Gdn_Format::toDateTime($DateAllViewed))
            ->getCount();
    }

    /**
     * Helper function to actually override a discussion's unread status
     *
     * @since 2.0
     * @access private
     */
    private function checkDiscussionDate($Discussion, $DateAllViewed) {
        if (Gdn_Format::toTimestamp($Discussion->DateInserted) > $DateAllViewed) {
            // Discussion is newer than DateAllViewed
            return;
        }

        if (Gdn_Format::toTimestamp($Discussion->DateLastComment) <= $DateAllViewed) {
            // Covered by AllViewed
            $Discussion->CountUnreadComments = 0;
        } elseif (Gdn_Format::toTimestamp($Discussion->DateLastViewed) == $DateAllViewed || !$Discussion->DateLastViewed) {
            // User clicked AllViewed. Discussion is older than click. Last comment is newer than click.
            // No UserDiscussion record found OR UserDiscussion was set by AllViewed
            $Discussion->CountUnreadComments = $this->getCommentCountSince($Discussion->DiscussionID, $DateAllViewed);
        }
    }

    /**
     * Modify CountUnreadComments to account for DateAllViewed.
     *
     * Required in DiscussionModel->get() just before the return:
     *    $this->EventArguments['Data'] = $Data;
     *    $this->fireEvent('AfterAddColumns');
     * @link http://vanillaforums.org/discussion/13227
     * @since 1.0
     * @access public
     */
    public function discussionModel_setCalculatedFields_handler($Sender) {
        // Only for members
        if (!Gdn::session()->isValid()) {
            return;
        }

        // Recalculate New count with each category's DateMarkedRead
        $Discussion = &$Sender->EventArguments['Discussion'];
        $Category = CategoryModel::categories($Discussion->CategoryID);
        $CategoryLastDate = Gdn_Format::toTimestamp($Category["DateMarkedRead"]);
        if ($CategoryLastDate != 0) {
            $this->checkDiscussionDate($Discussion, $CategoryLastDate);
        }
    }
}
