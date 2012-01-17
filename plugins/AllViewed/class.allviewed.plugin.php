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
 *
 * @copyright 2009 Vanilla Forums Inc.
 * @author Matt Lincoln Russell <lincoln@vanillaforums.com>
 * @package AllViewed
 */
 
$PluginInfo['AllViewed'] = array(
   'Name' => 'All Viewed',
   'Description' => 'Allows users to mark all discussions as viewed.',
   'Version' => '1.3',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://lincolnwebs.com',
   'License' => 'GNU GPLv2',
   'MobileFriendly' => TRUE
);

/**
 * Allows users to mark all discussions as viewed.
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
      // Add "Mark All Viewed" to menu
      if ($Sender->Menu && Gdn::Session()->IsValid()) {
         if (C('Plugins.AllViewed.ShowInMenu', TRUE))
            $Sender->Menu->AddLink('AllViewed', T('Mark All Viewed'), '/discussions/markallviewed');
      }
   }
   
   /**
    * Allows user to mark all discussions as viewed.
    *
    * @since 1.0
    * @access public
    */
   public function DiscussionsController_MarkAllViewed_Create($Sender) {
      Gdn::UserModel()->UpdateAllViewed();
      Redirect('discussions');
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
    * Modify CountUnreadComments to account for DateAllViewed.
    *
    * Required in DiscussionModel->Get() just before the return:
    *    $this->EventArguments['Data'] = $Data;
    *    $this->FireEvent('AfterAddColumns');
    * @link http://vanillaforums.org/discussion/13227
    * @since 1.0
    * @access public
    */
   public function DiscussionModel_AfterAddColumns_Handler($Sender) {
      // Only for members
      if(!Gdn::Session()->IsValid()) return;
      
      // Get user's DateAllViewed (work around user caching by querying directly)
      $UserData = $Sender->SQL->Select('DateAllViewed')->From('User')->Where('UserID', Gdn::Session()->UserID)->Get()->FirstRow();
      $DateAllViewed = Gdn_Format::ToTimestamp(GetValue('DateAllViewed', $UserData));
      
      // Recalculate New count with user's DateAllViewed      
      foreach($Sender->EventArguments['Data']->Result() as $Discussion) {
		   if ($DateAllViewed != 0) { 
            // They've used AllViewed at least once
			   if (Gdn_Format::ToTimestamp($Discussion->DateInserted) > $DateAllViewed) {
			      // Discussion is newer than last 'AllViewed' click
			      continue;
			   }
			   
			   if (Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $DateAllViewed) {
				   // Covered by AllViewed
				   $Discussion->CountUnreadComments = 0; 
			   }
            elseif (Gdn_Format::ToTimestamp($Discussion->DateLastViewed) == $DateAllViewed || !$Discussion->DateLastViewed) {
               // User clicked AllViewed. Discussion is older than click. Last comment is newer than click.
			      // No UserDiscussion record found OR UserDiscussion was set by AllViewed
			      $Discussion->CountUnreadComments = $this->GetCommentCountSince($Discussion->DiscussionID, $DateAllViewed);
			   }
			}
		}
   }
   
   /**
    * Update user's AllViewed datetime.
    *
    * @since 1.0
    * @access public
    */
   public function UserModel_UpdateAllViewed_Create($Sender) {
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      // Can only activate on yourself
      $UserID = $Session->User->UserID; 
            
      // Validite UserID
      $UserID = (int) $UserID;
      if (!$UserID)
         throw new Exception('A valid UserId is required.');
      
      // Create timestamp first so all uses match.
      $AllViewed = Gdn_Format::ToDateTime();
      
      // Update User timestamp
      $Sender->SQL->Update('User')
         ->Set('DateAllViewed', $AllViewed)
         ->Where('UserID', $UserID)
         ->Put();
      
      // Update DateLastViewed = now
      $Sender->SQL->Update('UserDiscussion')
         ->Set('DateLastViewed', $AllViewed)
         ->Where('UserID', $UserID)
         ->Put();
      
      // Set in current session
      $Session->User->DateAllViewed = Gdn_Format::ToDateTime();
   }
   
   /**
    * 1-Time on Enable.
    */
   public function Setup() {
      $this->Structure();
   }
   
   /**
    * Database changes.
    *
    * @since 1.0
    * @access public
    */
   public function Structure() {
      Gdn::Structure()->Table('User')
         ->Column('DateAllViewed', 'datetime', NULL)
         ->Set();
   }
}