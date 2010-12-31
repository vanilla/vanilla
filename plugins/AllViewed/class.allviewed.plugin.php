<?php if (!defined('APPLICATION')) exit();
// Copyright Trademark Productions 2010

$PluginInfo['AllViewed'] = array(
   'Name' => 'Mark All Viewed',
   'Description' => 'Allows users to mark all discussions as viewed.',
   'Version' => '1.1',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincolnwebs@gmail.com',
   'AuthorUrl' => 'http://www.tmprod.com/web-development/vanilla.php',
   'License' => 'GNU GPLv2'
);

class AllViewedPlugin extends Gdn_Plugin {
   /**
    * Adds "Mark All Viewed" to main menu.
    */
   public function Base_Render_Before(&$Sender) {
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
   function DiscussionsController_MarkAllViewed_Create(&$Sender) {
      $UserModel = Gdn::UserModel();
      $UserModel->UpdateAllViewed();
      Redirect('discussions');
   }
   
   /**
    * Get the number of comments inserted since the given timestamp.
    */
   function GetCommentCountSince($DiscussionID, $DateSince) {
      if (!C('Plugins.AllViewed.Enabled')) return;
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
         ->Where('DiscussionID = '.$DiscussionID)
         ->Where("DateInserted > '".$DateSince."'")
         ->Get()
         ->Count;
   }
   
   /**
    * Modify CountUnreadComments to account for DateAllViewed
    *
    * Required in DiscussionModel->Get() just before the return:
    *    $this->EventArguments['Data'] = $Data;
    *    $this->FireEvent('AfterAddColumns');
    * @link http://vanillaforums.org/discussion/13227
    */
   function DiscussionModel_AfterAddColumns_Handler(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled')) return;
      // Only for members
      $Session = Gdn::Session();
      if(!$Session->IsValid()) return;
      
      // Recalculate New count with user's DateAllViewed   
      $Sender->Data = GetValue('Data', $Sender->EventArguments, '');
      $Result = &$Sender->Data->Result();
      $DateAllViewed = Gdn_Format::ToTimestamp($Session->User->DateAllViewed);
		foreach($Result as &$Discussion) {
		   if ($DateAllViewed != 0) { // Only if they've used AllViewed
			   if (Gdn_Format::ToTimestamp($Discussion->DateLastComment) <= $DateAllViewed)
				   $Discussion->CountUnreadComments = 0; // Covered by AllViewed
            elseif ($Discussion->DateLastViewed == $DateAllViewed) // AllViewed used since last "real" view, but new comments since then
			      $Discussion->CountUnreadComments = $this->GetCommentCountSince($Discussion->DiscussionID, $DateAllViewed);
			}
		}
   }
   
   /**
    * Update user's AllViewed datetime.
    */
   function UserModel_UpdateAllViewed_Create(&$Sender) {
      if (!C('Plugins.AllViewed.Enabled')) return;
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
      SaveToConfig('Plugins.AllViewed.Enabled', TRUE);
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
   
   /**
    * 1-Time on Disable
    */
   public function OnDisable() {
      RemoveFromConfig('Plugins.AllViewed.Enabled');
   }
}