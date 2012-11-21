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
 *
 * @copyright 2009 Vanilla Forums Inc.
 * @author Matt Lincoln Russell <lincoln@vanillaforums.com>
 * @author Oliver Chung <shoat@cs.washington.edu>
 * @package AllViewed
 */
 
$PluginInfo['AllViewed'] = array(
	'Name' => 'All Viewed',
	'Description' => 'Allows users to mark all discussions as viewed and mark category viewed.',
	'Version' => '2.0',
	'Author' => "Matt Lincoln Russell, Oliver Chung",
	'AuthorEmail' => 'lincoln@vanillaforums.com, shoat@cs.washington.edu',
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
    * @access public
    */
   public function Base_Render_Before($Sender) {
      // Add "Mark All Viewed" to main menu
      if ($Sender->Menu && Gdn::Session()->IsValid()) {
         if (C('Plugins.AllViewed.ShowInMenu', TRUE))
            $Sender->Menu->AddLink('AllViewed', T('Mark All Viewed'), '/discussions/markallviewed');
      }
   }
   
	/**
	 * Adds "Mark All Viewed" and (conditionally) "Mark Category Viewed" to MeModule menu.
	 *
	 * @since 2.0
	 * @access public
	 */
	public function MeModule_FlyoutMenu_Handler($Sender) {
		// Add "Mark All Viewed" to menu
		if (Gdn::Session()->IsValid()) {
         echo Wrap(Anchor(T('Mark All Viewed'), '/discussions/markallviewed'), 'li', array('class' => 'MarkAllViewed'));
         
			$CategoryID = (int)(empty(Gdn::Controller()->CategoryID) ? 0 : Gdn::Controller()->CategoryID);
			if ($CategoryID > 0)
            echo Wrap(Anchor(T('Mark Category Viewed'), "/discussions/markcategoryviewed/{$CategoryID}"), 'li', array('class' => 'MarkCategoryViewed'));
		}
	}
   
	/**
	 * Helper function that actually sets the DateMarkedRead column in UserCategory 
	 *
	 * @since 2.0
	 * @access private
	 */
	private function MarkCategoryRead($CategoryModel, $CategoryID) {
		$CategoryModel->SaveUserTree($CategoryID, array('DateMarkedRead' => Gdn_Format::ToDateTime()));
	}

	/**
	 * Allows user to mark all discussions in a specified category as viewed.
	 *
    * @param DiscussionsController $Sender
	 * @since 1.0
	 * @access public
	 */
	public function DiscussionsController_MarkCategoryViewed_Create($Sender, $CategoryID) {
		if (strlen($CategoryID) > 0 && (int)$CategoryID > 0) {
         $CategoryModel = new CategoryModel();
			$this->MarkCategoryRead($CategoryModel, $CategoryID);
			$this->RecursiveMarkCategoryRead($CategoryModel, CategoryModel::Categories(), array($CategoryID));
		}
      
		Redirect(Gdn::Request()->GetValueFrom(Gdn_Request::INPUT_SERVER, 'HTTP_REFERER'));
	}

	/**
	 * Helper function to recursively mark categories as read based on a Category's ParentID.
	 *
	 * @since 2.0
	 * @access private
	 */
	private function RecursiveMarkCategoryRead($CategoryModel, $UnprocessedCategories, $ParentIDs) {
		$CurrentUnprocessedCategories = array();
		$CurrentParentIDs = $ParentIDs;
		foreach ($UnprocessedCategories as $Category) {
			if (in_array($Category["ParentCategoryID"], $ParentIDs)) {
				$this->MarkCategoryRead($CategoryModel, $Category["CategoryID"]);
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
			$this->RecursiveMarkCategoryRead($CategoryModel, $CurrentUnprocessedCategories, $CurrentParentIDs);
		}
	}

	/**
	 * Allows user to mark all discussions as viewed.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function DiscussionsController_MarkAllViewed_Create($Sender) {
		$CategoryModel = new CategoryModel();
		$this->MarkCategoryRead($CategoryModel, -1);
		$this->RecursiveMarkCategoryRead($CategoryModel, CategoryModel::Categories(), array(-1));
		Redirect($_SERVER["HTTP_REFERER"]);
	}

	/**
	 * Get the number of comments inserted since the given timestamp.
	 *
	 * @since 1.0
	 * @access public
	 */
	public function GetCommentCountSince($DiscussionID, $DateAllViewed) {
		// Only for members
		if(!Gdn::Session()->IsValid()) return;
		
		// Validate DiscussionID
		$DiscussionID = (int) $DiscussionID;
		if (!$DiscussionID)
			throw new Exception('A valid DiscussionID is required in GetCommentCountSince.');
		
		// Get new comment count
		return Gdn::Database()->SQL()
			->From('Comment c')
			->Where('DiscussionID', $DiscussionID)
			->Where('DateInserted >', Gdn_Format::ToDateTime($DateAllViewed))
			->GetCount();
	}

	/**
	 * Helper function to actually override a discussion's unread status
	 *
	 * @since 2.0
	 * @access private
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
	 * Required in DiscussionModel->Get() just before the return:
	 *    $this->EventArguments['Data'] = $Data;
	 *    $this->FireEvent('AfterAddColumns');
	 * @link http://vanillaforums.org/discussion/13227
	 * @since 1.0
	 * @access public
	 */
	public function DiscussionModel_SetCalculatedFields_Handler($Sender) {
		// Only for members
		if (!Gdn::Session()->IsValid()) return;
		
		// Recalculate New count with each category's DateMarkedRead
		$Discussion = &$Sender->EventArguments['Discussion'];
      $Category = CategoryModel::Categories($Discussion->CategoryID);
      $CategoryLastDate = Gdn_Format::ToTimestamp($Category["DateMarkedRead"]);
      if ($CategoryLastDate != 0)
         $this->CheckDiscussionDate($Discussion, $CategoryLastDate);
      
	}
}
