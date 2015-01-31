<?php if (!defined('APPLICATION')) exit();

/**
 * 'All Viewed' plugin for Vanilla Forums.
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
 * - Rename to "Mark All Viewed" (from "All Viewed")
 * - Bug fix: children categories being marked as read when parent is
 * - Bug fix: fix performance issues from over-querying
 * 
 * @copyright 2009-2014 Vanilla Forums Inc.
 * @author Matt Lincoln Russell <lincoln@vanillaforums.com>
 * @author Oliver Chung <shoat@cs.washington.edu>
 * @package AllViewed
 */

$PluginInfo['AllViewed'] = array(
   'Name' => 'Mark All Viewed',
   'Description' => 'Allows users to mark all discussions as viewed and mark category viewed.',
   'Version' => '2.2',
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com',
   'License' => 'GNU GPLv2',
   'MobileFriendly' => TRUE
);

/**
 * Allows users to mark all discussions as viewed and mark category viewed.
 *
 * @package AllViewed
 */
class AllViewedPlugin extends Gdn_Plugin {
   /**
   * Adds "Mark All Viewed" to main menu.
   *
   * @since 1.0
   * @param Gdn_Controller $Sender
   */
   public function Base_Render_Before($Sender) {
      // Add "Mark All Viewed" to main menu
      if ($Sender->Menu && Gdn::Session()->IsValid()) {
         if (C('Plugins.AllViewed.ShowInMenu', TRUE)) {
            $Sender->Menu->AddLink('AllViewed', T('Mark All Viewed'), '/discussions/markallviewed');
         }
      }
   }

   /**
    * Adds "Mark All Viewed" and (conditionally) "Mark Category Viewed" to MeModule menu.
    *
    * @since 2.0
    * @param MeModule $Sender
    */
   public function MeModule_FlyoutMenu_Handler($Sender) {
      // Only for members
      if(!Gdn::Session()->IsValid()) {
         return;
      }

      // Add "Mark All Viewed" to menu
      echo Wrap(
         Anchor(Sprite('SpMarkAllViewed').' '.T('Mark All Viewed'), '/discussions/markallviewed'),
         'li', array('class' => 'MarkAllViewed'));

      $CategoryID = (int)(empty(Gdn::Controller()->CategoryID) ? 0 : Gdn::Controller()->CategoryID);
      if ($CategoryID > 0) {
         $Anchor = "/discussions/markcategoryviewed/{$CategoryID}";
         echo Wrap(
            Anchor(Sprite('SpMarkCategoryViewed').' '.T('Mark Category Viewed'), $Anchor),
            'li', array('class' => 'MarkCategoryViewed'));
      }
   }

   /**
    * Helper function that actually sets the DateMarkedRead column in UserCategory.
    *
    * @since 2.0
    * @param object $CategoryModel
    * @param int $CategoryID
    * @return null
    */
   private function MarkCategoryRead($CategoryModel, $CategoryID) {
      $CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::ToDateTime()));
   }

   /**
    * Allows user to mark all discussions in a specified category as viewed.

    * @since 1.0
    * @param DiscussionsController $Sender
    * @param int $CategoryID
    */
   public function DiscussionsController_MarkCategoryViewed_Create($Sender, $CategoryID) {
      // Only for members
      if(!Gdn::Session()->IsValid()) {
         return;
      }

      // If we sent a category, mark it as viewed.
      if (strlen($CategoryID) > 0 && (int)$CategoryID > 0) {
         $CategoryModel = new CategoryModel();
         $this->MarkCategoryRead($CategoryModel, $CategoryID);
         $this->RecursiveMarkCategoryRead($CategoryModel, CategoryModel::Categories(), array($CategoryID));
      }

      // Back from whence thy came
      Redirect(Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER'));
   }

   /**
    * Helper function to recursively mark categories as read based on a Category's ParentID.
    *
    * @since 2.0
    * @param object $CategoryModel
    * @param array $CategoriesToMark Contains category arrays.
    * @param array $ParentIDs Numeric array of CategoryIDs.
    * @return null
    */
   private function RecursiveMarkCategoryRead($CategoryModel, $CategoriesToMark, $ParentIDs) {
      // Reset categories we are dealing with
      $CurrentCategoriesToMark = array();
      $CurrentParentIDs = $ParentIDs;

      // Find the categories we are operating on
      foreach ($CategoriesToMark as $Category) {
         if (in_array($Category["ParentCategoryID"], $ParentIDs)) {
            // Mark this one
            $this->MarkCategoryRead($CategoryModel, $Category["CategoryID"]);

            // Don't add duplicate ParentIDs
            if (!in_array($Category["CategoryID"], $CurrentParentIDs)) {
               $CurrentParentIDs[] = $Category["CategoryID"];
            }
         } else {
            // This keeps track of categories that we still need to check on recursively
            $CurrentCategoriesToMark[] = $Category;
         }
      }

      // If we have not found any new ParentIDs, we don't need to descend another level
      if (count($ParentIDs) != count($CurrentParentIDs)) {
         $this->RecursiveMarkCategoryRead($CategoryModel, $CurrentCategoriesToMark, $CurrentParentIDs);
      }
   }

   /**
    * User action: mark all discussions as viewed.
    *
    * @since 1.0
    * @param DiscussionController $Sender
    */
   public function DiscussionsController_MarkAllViewed_Create($Sender) {
      // Only for members
      if(!Gdn::Session()->IsValid()) {
         return;
      }

      // Mark all viewed from root category
      $CategoryModel = new CategoryModel();
      $this->MarkCategoryRead($CategoryModel, -1);
      $this->RecursiveMarkCategoryRead($CategoryModel, CategoryModel::Categories(), array(-1));

      // Back from whence thy came
      Redirect(Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER'));
   }

   /**
    * Get the number of comments inserted since the given timestamp.
    *
    * @since 1.0
    * @param int $DiscussionID
    * @param int $DateAllViewed Unix timestamp.
    * @return int Number of comments.
    */
   public function GetCommentCountSince($DiscussionID, $DateAllViewed) {
      // Only for members
      if(!Gdn::Session()->IsValid()) {
         return;
      }

      // Validate DiscussionID
      $DiscussionID = (int) $DiscussionID;
      if (!$DiscussionID) {
         throw new Exception('A valid DiscussionID is required in GetCommentCountSince.');
      }

      // Get new comment count
      return Gdn::Database()->SQL()
         ->From('Comment c')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('DateInserted >', Gdn_Format::ToDateTime($DateAllViewed))
         ->GetCount();
   }

   /**
    * Override a discussion's unread status.
    *
    * @since 2.0
    * @param object $Discussion
    * @param int $DateAllViewed Unix timestamp.
    * @return null
    */
   private function CheckDiscussionDate($Discussion, $DateAllViewed) {
      if (Gdn_Format::ToTimestamp($Discussion->DateInserted) > $DateAllViewed) {
         // Discussion is newer than DateAllViewed
         return;
      }

      if (Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $DateAllViewed) {
         // Covered by AllViewed
         $Discussion->CountUnreadComments = 0;
      } elseif (Gdn_Format::ToTimestamp($Discussion->DateLastViewed) == $DateAllViewed || !$Discussion->DateLastViewed) {
         // User clicked AllViewed. Discussion is older than click. Last comment is newer than click.
         // No UserDiscussion record found OR UserDiscussion was set by AllViewed
         $Discussion->CountUnreadComments = $this->GetCommentCountSince($Discussion->DiscussionID, $DateAllViewed);
      }
   }

   /**
    * Modify CountUnreadComments to account for DateAllViewed.
    *
    * @since 1.0
    * @param DiscussionModel $Sender
    * @return null
    */
   public function DiscussionModel_SetCalculatedFields_Handler($Sender) {
      // Only for members
      if (!Gdn::Session()->IsValid()) {
         return;
      }

      // Recalculate New count with each category's DateMarkedRead
      $Discussion = &$Sender->EventArguments['Discussion'];
      $Category = CategoryModel::Categories($Discussion->CategoryID);
      $CategoryLastDate = Gdn_Format::ToTimestamp($Category["DateMarkedRead"]);
      if ($CategoryLastDate != 0) {
         $this->CheckDiscussionDate($Discussion, $CategoryLastDate);
      }
   }
}
