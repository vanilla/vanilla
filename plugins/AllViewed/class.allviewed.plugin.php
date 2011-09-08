<?php if (!defined('APPLICATION')) exit();
/**
 * 'Mark All Viewed' plugin for Vanilla Forums.
 *
 * v1.2 
 * - Fixed "New" count jumping back to "Total" (rather than 1) after new comment if user hadn't actually viewed a discussion.
 * - Removed spurious config checks and &s
 *
 * @package AllViewed
 */
 
$PluginInfo['AllViewed'] = array(
   'Name' => 'Mark All Viewed',
   'Description' => 'Allows users to mark all discussions as viewed.',
   'Version' => '1.2',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => '',
   'License' => 'GNU GPLv2'
);

/**
 * Allows users to mark all discussions as viewed.
 *
 * @package AllViewed
 */
class AllViewedPlugin extends Gdn_Plugin {
   /**
    * Adds "Mark All Viewed" to main menu.
    */
   public function Base_Render_Before($Sender) {
      // Add "Mark All Viewed" to menu
      $Session = Gdn::Session();
      if ($Sender->Menu && $Session->IsValid()) {
         // Comment out this next line if you want to put the link somewhere else manually
         $Sender->Menu->AddLink('AllViewed', T('Mark All Viewed'), '/discussions/markallviewed');
      }
   }
   
   /**
    * Allows user to mark all discussions as viewed.
    */
   function DiscussionsController_MarkAllViewed_Create($Sender) {
      $UserModel = Gdn::UserModel();
      $UserModel->UpdateAllViewed();
      Redirect('discussions');
   }
   
   /**
    * Get the number of comments inserted since the given timestamp.
    */
   function GetCommentCountSince($DiscussionID, $DateSince) {
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      // Validate DiscussionID
      $DiscussionID = (int) $DiscussionID;
      if (!$DiscussionID)
         throw new Exception('A valid DiscussionID is required in GetCommentCountSince.');
      
      // Prep DB
      $Database = Gdn::Database();
      $SQL = $Database->SQL();
      
      // Get new comment count
      return $SQL
         ->Select('c.CommentID')
         ->From('Comment c')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('DateInserted >', Gdn_Format::ToDateTime($DateSince))
         ->GetCount();
   }
   
   /**
    * Modify CountUnreadComments to account for DateAllViewed
    *
    * Required in DiscussionModel->Get() just before the return:
    *    $this->EventArguments['Data'] = $Data;
    *    $this->FireEvent('AfterAddColumns');
    * @link http://vanillaforums.org/discussion/13227
    */
   function DiscussionModel_AfterAddColumns_Handler($Sender) {
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      // Recalculate New count with user's DateAllViewed
      $DateAllViewed = Gdn_Format::ToTimestamp($Session->User->DateAllViewed);
      foreach($Sender->EventArguments['Data']->Result() as $Discussion) {
		   if ($DateAllViewed != 0) { // Only if they've used AllViewed
			   if (Gdn_Format::ToTimestamp($Discussion->DateInserted) > $DateAllViewed) {
			      // Discussion is newer than last 'AllViewed' click
			      continue;
			   }
			   
			   if (Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $DateAllViewed) {
				   // Covered by AllViewed
				   $Discussion->CountUnreadComments = 0; 
			   }
            elseif (Gdn_Format::ToTimestamp($Discussion->DateLastViewed) == $DateAllViewed || !$Discussion->DateLastViewed) {
               // AllViewed used since last "real" view, but new comments since then
			      $Discussion->CountUnreadComments = $this->GetCommentCountSince($Discussion->DiscussionID, $DateAllViewed);
			   }
			}
		}
   }
   
   /**
    * Update user's AllViewed datetime.
    */
   function UserModel_UpdateAllViewed_Create($Sender) {
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      $UserID = $Session->User->UserID; // Can only activate on yourself
            
      // Validite UserID
      $UserID = (int) $UserID;
      if (!$UserID)
         throw new Exception('A valid UserId is required.');
      
      // Create timestamp first so all uses match.
      $AllViewed = Gdn_Format::ToDateTime();
      
      // Update User timestamp
      $Sender->SQL->Update('User')
         ->Set('DateAllViewed', $AllViewed);
      $Sender->SQL->Where('UserID', $UserID)->Put();
      
      // Update DateLastViewed = now
      $Sender->SQL->Update('UserDiscussion')
         ->Set('DateLastViewed', $AllViewed) 
         ->Where('UserID', $UserID)->Put();
      
      // Set in current session
      $Session->User->DateAllViewed = Gdn_Format::ToDateTime();
   }
   
   /**
    * 1-Time on Enable
    */
   public function Setup() {
      $this->Structure();
   }
   
   /**
    * Database changes
    */
   public function Structure() {
      $Structure = Gdn::Structure();
      $Structure->Table('User')
         ->Column('DateAllViewed', 'datetime', NULL)
         ->Set();
   }
}