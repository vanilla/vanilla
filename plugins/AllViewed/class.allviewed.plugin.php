<?php
/**
 * 'All Viewed' plugin for Vanilla Forums.
 *
 * @author Lincoln Russell <lincoln@vanillaforums.com>
 * @author Oliver Chung <shoat@cs.washington.edu>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package AllViewed
 */

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
 * v2.2
 * - Added transient key validation
 * - Remove referrer redirects
 * - Cleaned up code
 */
class AllViewedPlugin extends Gdn_Plugin {

    /**
     * Adds "Mark All Viewed" to main menu.
     *
     * @since 1.0
     * @access public
     *
     * @param Gdn_Controller $sender
     */
    public function base_render_before($sender) {
        // Add "Mark All Viewed" to main menu
        if ($sender->Menu && Gdn::session()->isValid()) {
            if (c('Plugins.AllViewed.ShowInMenu', true)) {
                $sender->Menu->addLink('AllViewed', t('Mark All Viewed'), '/discussions/markallviewed', false, ['class' => 'MarkAllViewed Hijack']);
            }
        }
    }

    /**
     * Adds "Mark All Viewed" and (conditionally) "Mark Category Viewed" to MeModule menu.
     *
     * @since 2.0
     * @access public
     *
     * @param MeModule $sender
     * @param array $args
     */
    public function meModule_flyoutMenu_handler($sender, $args) {
        if (val('Dropdown', $args, false) && Gdn::session()->isValid()) {
            /** @var DropdownModule $dropdown */
            $dropdown = $args['Dropdown'];
            $dropdown->addGroup('', 'discussions', '', ['after' => 'profile']); // Add links after profile menu items
            $allModifiers = [
                'listItemCssClasses' => ['MarkAllViewed', 'link-mark-all-viewed']
            ];
            $dropdown->addLink(t('Mark All Viewed'), '/discussions/markallviewed', 'discussions.markallviewed', 'Hijack', [], $allModifiers);
            $categoryID = (int)(empty(Gdn::controller()->CategoryID) ? 0 : Gdn::controller()->CategoryID);
            $categoryModifiers['listItemCssClasses'] = ['MarkCategoryViewed', 'link-mark-category-viewed'];
            $dropdown->addLinkIf($categoryID > 0, t('Mark Category Viewed'), "/discussions/markcategoryviewed/{$categoryID}", 'discussions.markcategoryviewed', 'Hijack', [], $categoryModifiers);
        }
    }

    /**
     * Helper function that actually sets the DateMarkedRead column in UserCategory
     *
     * @since 2.0
     * @access private
     *
     * @param CategoryModel $categoryModel
     * @param int $categoryID
     */
    private function markCategoryRead($categoryModel, $categoryID) {
        $categoryModel->saveUserTree($categoryID, ['DateMarkedRead' => Gdn_Format::toDateTime()]);
    }

    /**
     * Allows user to mark all discussions in a specified category as viewed.
     *
     * @since 1.0
     * @access public
     *
     * @param DiscussionsController $sender
     * @param int $categoryID
     */
    public function discussionsController_markCategoryViewed_create($sender, $categoryID) {
        if (Gdn::request()->isAuthenticatedPostBack()) {
            if (strlen($categoryID) > 0 && (int)$categoryID > 0) {
                $categoryModel = new CategoryModel();
                $this->markCategoryRead($categoryModel, $categoryID);
                $this->recursiveMarkCategoryRead($categoryModel, CategoryModel::categories(), [$categoryID]);

                $sender->informMessage(t('Category marked as viewed.'));
                $sender->render('blank', 'utility', 'dashboard');
            }
        }

    }

    /**
     * Helper function to recursively mark categories as read based on a Category's ParentID.
     *
     * @since 2.0
     * @access private
     *
     * @param CategoryModel $categoryModel
     * @param array $unprocessedCategories
     * @param array $parentIDs
     */
    private function recursiveMarkCategoryRead($categoryModel, $unprocessedCategories, $parentIDs) {
        $currentUnprocessedCategories = [];
        $currentParentIDs = $parentIDs;
        foreach ($unprocessedCategories as $category) {
            if (in_array($category["ParentCategoryID"], $parentIDs)) {
                $this->markCategoryRead($categoryModel, $category["CategoryID"]);
                // Don't add duplicate ParentIDs
                if (!in_array($category["CategoryID"], $currentParentIDs)) {
                    $currentParentIDs[] = $category["CategoryID"];
                }
            } else {
                // This keeps track of categories that we still need to check on recurse
                $currentUnprocessedCategories[] = $category;
            }
        }
        // Base case: if we have not found any new parent ids, we don't need to recurse
        if (count($parentIDs) != count($currentParentIDs)) {
            $this->recursiveMarkCategoryRead($categoryModel, $currentUnprocessedCategories, $currentParentIDs);
        }
    }

    /**
     * Allows user to mark all discussions as viewed.
     *
     * @since 1.0
     * @access public
     *
     * @param DiscussionsController $sender
     */
    public function discussionsController_markAllViewed_create($sender) {
        if (Gdn::request()->isAuthenticatedPostBack()) {
            $categoryModel = new CategoryModel();
            $this->markCategoryRead($categoryModel, -1);
            $this->recursiveMarkCategoryRead($categoryModel, CategoryModel::categories(), [-1]);

            $sender->informMessage(t('All discussions marked as viewed.'));

            // Didn't use the default async option and landed here directly.
            if ($sender->deliveryType() == DELIVERY_TYPE_ALL) {
                redirectTo('/');
            }

            $sender->render('blank', 'utility', 'dashboard');
        } else {
            throw new Exception('Requires POST', 405);
        }
    }

    /**
     * Get the number of comments inserted since the given timestamp.
     *
     * @since 1.0
     * @access public
     * @throws Exception
     *
     * @param int $discussionID
     * @param int|string $dateAllViewed
     * @return int
     */
    private function getCommentCountSince($discussionID, $dateAllViewed) {
        // Validate DiscussionID
        $discussionID = (int)$discussionID;
        if (!$discussionID) {
            throw new Exception('A valid DiscussionID is required in GetCommentCountSince.');
        }

        // Get new comment count
        return Gdn::database()->sql()
            ->from('Comment c')
            ->where('DiscussionID', $discussionID)
            ->where('DateInserted >', Gdn_Format::toDateTime($dateAllViewed))
            ->getCount();
    }

    /**
     * Helper function to actually override a discussion's unread status
     *
     * @since 2.0
     * @access private
     *
     * @param DiscussionModel $discussion
     * @param int|string $dateAllViewed
     */
    private function checkDiscussionDate($discussion, $dateAllViewed) {
        if (Gdn_Format::toTimestamp($discussion->DateInserted) > $dateAllViewed) {
            // Discussion is newer than DateAllViewed
            return;
        }

        if (Gdn_Format::toTimestamp($discussion->DateLastComment) <= $dateAllViewed) {
            // Covered by AllViewed
            $discussion->CountUnreadComments = 0;
        } elseif (Gdn_Format::toTimestamp($discussion->DateLastViewed) == $dateAllViewed || !$discussion->DateLastViewed) {
            // User clicked AllViewed. Discussion is older than click. Last comment is newer than click.
            // No UserDiscussion record found OR UserDiscussion was set by AllViewed
            $discussion->CountUnreadComments = $this->getCommentCountSince($discussion->DiscussionID, $dateAllViewed);
        }
    }

    /**
     * Modify CountUnreadComments to account for DateAllViewed.
     *
     * Required in DiscussionModel->get() just before the return:
     *    $this->EventArguments['Data'] = $Data;
     *    $this->fireEvent('AfterAddColumns');
     *
     * @link https://open.vanillaforums.com/discussion/13227
     * @since 1.0
     * @access public
     *
     * @param DiscussionModel $sender
     */
    public function discussionModel_setCalculatedFields_handler($sender) {
        // Only for members
        if (!Gdn::session()->isValid()) {
            return;
        }

        // Recalculate New count with each category's DateMarkedRead
        $discussion = &$sender->EventArguments['Discussion'];
        $category = CategoryModel::categories($discussion->CategoryID);
        $categoryLastDate = Gdn_Format::toTimestamp($category["DateMarkedRead"]);

        if ($categoryLastDate != 0) {
            $this->checkDiscussionDate($discussion, $categoryLastDate);
        }
    }
}
