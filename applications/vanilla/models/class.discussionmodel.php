<?php if (!defined('APPLICATION')) exit();

class Gdn_DiscussionModel extends Gdn_VanillaModel {
   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('Discussion');
   }
   
   public function DiscussionQuery() {
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
      
      $this->SQL
         ->Select('d.InsertUserID', '', 'FirstUserID')
         ->Select('d.DateInserted', '', 'FirstDate')
         ->Select('iu.Name', '', 'FirstName')
         ->Select('iup.Name', '', 'FirstPhoto')
         // ->Select('fc.Body', '', 'FirstBody')
         ->Select('lc.DateInserted', '', 'LastDate')
         ->Select('lc.InsertUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         ->Select('lcup.Name', '', 'LastPhoto')
         ->Select('lc.Body', '', 'LastBody')
         ->Select("' &rarr; ', pc.Name, ca.Name", 'concat_ws', 'Category')
         ->From('Discussion d')
         ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // First comment author is also the discussion insertuserid
         ->Join('Photo iup', 'iu.PhotoID = iup.PhotoID', 'left') // First Photo
         ->Join('Comment fc', 'd.FirstCommentID = fc.CommentID') // First comment
         ->Join('Comment lc', 'd.LastCommentID = lc.CommentID') // Last comment
         ->Join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left') // Last comment user
         ->Join('Photo lcup', 'lcu.PhotoID = lcup.PhotoID', 'left') // Last Photo
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left') // Category
         ->Join('Category pc', 'ca.ParentCategoryID = pc.CategoryID', 'left'); // Parent category
         //->Permission('ca', 'CategoryID', 'Vanilla.Discussions.View');
   }
   
   public function DiscussionSummaryQuery() {
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('d.CategoryID', $Perms);
      }
      
      $this->SQL
         ->Select('d.InsertUserID', '', 'FirstUserID')
         ->Select('d.DateInserted', '', 'FirstDate')
         ->Select('iu.Name', '', 'FirstName') // <-- Need these for rss!
         ->Select('iup.Name', '', 'FirstPhoto')
         ->Select('fc.Body', '', 'FirstComment') // <-- Need these for rss!
         ->Select('fc.Format', '', 'FirstCommentFormat') // <-- Need these for rss!
         ->Select('lc.DateInserted', '', 'LastDate')
         ->Select('lc.InsertUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         //->Select('lcup.Name', '', 'LastPhoto')
         //->Select('lc.Body', '', 'LastBody')
         ->Select("' &rarr; ', pc.Name, ca.Name", 'concat_ws', 'Category')
         ->Select('ca.UrlCode', '', 'CategoryUrlCode')
         ->From('Discussion d')
         ->Join('User iu', 'd.InsertUserID = iu.UserID', 'left') // First comment author is also the discussion insertuserid
         ->Join('Photo iup', 'iu.PhotoID = iup.PhotoID', 'left') // First Photo
         ->Join('Comment fc', 'd.FirstCommentID = fc.CommentID', 'left') // First comment
         ->Join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left') // Last comment
         ->Join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left') // Last comment user
         //->Join('Photo lcup', 'lcu.PhotoID = lcup.PhotoID', 'left') // Last Photo
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left') // Category
         ->Join('Category pc', 'ca.ParentCategoryID = pc.CategoryID', 'left'); // Parent category
         //->Permission('ca', 'CategoryID', 'Vanilla.Discussions.View');
         
      $this->FireEvent('AfterDiscussionSummaryQuery');
   }
   
   public function Get($Offset = '0', $Limit = '', $Wheres = '') {
      if ($Limit == '') 
         $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery();
      $this->SQL
         ->Select('d.*');
         
      if ($UserID > 0) {
         $this->SQL
            ->Select('w.UserID', '', 'WatchUserID')
            ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
            ->Select('w.CountComments', '', 'CountCommentWatch')
            ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      } else {
            $this->SQL
               ->Select('0', '', 'WatchUserID')
               ->Select('now()', '', 'DateLastViewed')
               ->Select('0', '', 'Dismissed')
               ->Select('0', '', 'Bookmarked')
               ->Select('0', '', 'CountCommentWatch');
      }
      
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
      
      if (!isset($Wheres['w.Bookmarked']) && !isset($Wheres['d.InsertUserID'])) {
         $this->SQL
            ->BeginWhereGroup()
            ->Where('d.Announce', '0');
         
         // Removing this for speed.
         //if ($UserID > 0)
            //$this->SQL->OrWhere('w.Dismissed', '1');
            
         $this->SQL->EndWhereGroup();
      }
         
      return $this->SQL
         ->OrderBy('d.DateLastComment', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   public function GetAnnouncements($Wheres = '') {
      $Session = Gdn::Session();
      $Limit = Gdn::Config('Vanilla.Discussions.PerPage', 50);
      $Offset = 0;
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      $this->DiscussionSummaryQuery();
      $this->SQL
         ->Select('d.*')
         ->Select('w.UserID', '', 'WatchUserID')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left');
      
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      return $this->SQL
         ->Where('d.Announce', '1')
         ->Where('w.Dismissed is null')
         ->OrderBy('lc.DateInserted', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   // Returns all users who have bookmarked the specified discussion
   public function GetBookmarkUsers($DiscussionID) {
      return $this->SQL
         ->Select('UserID')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('Bookmarked', '1')
         ->Get();
   }
   
   protected $_CategoryPermissions = NULL;
   
   public function CategoryPermissions($Escape = FALSE) {
      if(is_null($this->_CategoryPermissions)) {
         $Session = Gdn::Session();
         
         if((is_object($Session->User) && $Session->User->Admin == '1')) {
            $this->_CategoryPermissions = TRUE;
         } else {
            $Data = $this->SQL
               ->Select('c.CategoryID')
               ->From('Category c')
               ->Permission('c', 'CategoryID', 'Vanilla.Discussions.View')
               ->Get();
            
            $Data = $Data->ResultArray();
            $this->_CategoryPermissions = array();
            foreach($Data as $Row) {
               $this->_CategoryPermissions[] = ($Escape ? '@' : '').$Row['CategoryID'];
            }
         }
      }
      
      return $this->_CategoryPermissions;
   }

   public function GetCount($Wheres = '', $ForceNoAnnouncements = FALSE) {
      $Session = Gdn::Session();
      $UserID = $Session->UserID > 0 ? $Session->UserID : 0;
      if (is_array($Wheres) && count($Wheres) == 0)
         $Wheres = '';
         
      $Perms = $this->CategoryPermissions();
      if($Perms !== TRUE) {
         $this->SQL->WhereIn('c.CategoryID', $Perms);
      }
         
      // Small optimization for basic queries
      if ($Wheres == '') {
         $this->SQL
            ->Select('c.CountDiscussions', 'sum', 'CountDiscussions')
            ->From('Category c');
            //->Permission('c', 'CategoryID', 'Vanilla.Discussions.View');
      } else {
         $this->SQL
	         ->Select('d.DiscussionID', 'count', 'CountDiscussions')
	         ->From('Discussion d')
            ->Join('Category c', 'd.CategoryID = c.CategoryID')
	         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$UserID, 'left')
            ->Where($Wheres);
      }
      return $this->SQL
         ->Get()
         ->FirstRow()
         ->CountDiscussions;
   }

   public function GetID($DiscussionID) {
      $Session = Gdn::Session();
      return $this->SQL
         ->Select('d.*')
         ->Select('c.Body')
         ->Select('ca.Name', '', 'Category')
         ->Select('w.DateLastViewed, w.Dismissed, w.Bookmarked')
         ->Select('w.CountComments', '', 'CountCommentWatch')
         ->Select('lc.DateInserted', '', 'LastDate')
         ->Select('lc.InsertUserID', '', 'LastUserID')
         ->Select('lcu.Name', '', 'LastName')
         ->From('Discussion d')
         ->Join('Comment c', 'd.FirstCommentID = c.CommentID', 'left')
         ->Join('Category ca', 'd.CategoryID = ca.CategoryID', 'left')
         ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = '.$Session->UserID, 'left')
         ->Join('Comment lc', 'd.LastCommentID = lc.CommentID', 'left') // Last comment
         ->Join('User lcu', 'lc.InsertUserID = lcu.UserID', 'left') // Last comment user
         ->Where('d.DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow();
   }
   
   /**
    * Marks the specified announcement as dismissed by the specified user.
    *
    * @param int The unique id of the discussion being affected.
    * @param int The unique id of the user being affected.
    */
   public function DismissAnnouncement($DiscussionID, $UserID) {
      $Count = $this->SQL
         ->Select('UserID')
         ->From('UserDiscussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Where('UserID', $UserID)
         ->Get()
         ->NumRows();
         
      $CountComments = $this->SQL
         ->Select('CountComments')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow()
         ->CountComments;
      
      if ($Count > 0) {
         $this->SQL
            ->Update('UserDiscussion')
            ->Set('CountComments', $CountComments)
            ->Set('DateLastViewed', Format::ToDateTime())
            ->Set('Dismissed', '1')
            ->Where('DiscussionID', $DiscussionID)
            ->Where('UserID', $UserID)
            ->Put();
      } else {
         $this->SQL->Insert(
            'UserDiscussion',
            array(
               'UserID' => $UserID,
               'DiscussionID' => $DiscussionID,
               'CountComments' => $CountComments,
               'DateLastViewed' => Format::ToDateTime(),
               'Dismissed' => '1'
            )
         );
      }
   }
   
   public function Save($FormPostValues, $CommentModel) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      $CommentModel->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $CommentModel->Validation->ApplyRule('Body', 'Required');
      $MaxCommentLength = Gdn::Config('Vanilla.Comment.MaxLength');
      if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
         $CommentModel->Validation->SetSchemaProperty('Body', 'Length', $MaxCommentLength);
         $CommentModel->Validation->ApplyRule('Body', 'Length');
      }      
      
      // Get the DiscussionID from the form so we know if we are inserting or updating.
      $DiscussionID = ArrayValue('DiscussionID', $FormPostValues, '');
      $Insert = $DiscussionID == '' ? TRUE : FALSE;
      
      if ($Insert) {
         unset($FormPostValues['DiscussionID']);
         // If no categoryid is defined, grab the first available.
         if (ArrayValue('CategoryID', $FormPostValues) === FALSE)
            $FormPostValues['CategoryID'] = $this->SQL->Get('Category', '', '', 1)->FirstRow()->CategoryID;
            
         $this->AddInsertFields($FormPostValues);
         // $FormPostValues['LastCommentUserID'] = $Session->UserID;
         $FormPostValues['DateLastComment'] = Format::ToDateTime();
      }
      // Add the update fields because this table's default sort is by DateUpdated (see $this->Get()).
      $this->AddUpdateFields($FormPostValues);
      
      // Remove checkboxes from the fields if they were unchecked
      if (ArrayValue('Announce', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Announce']);

      if (ArrayValue('Closed', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Closed']);

      if (ArrayValue('Sink', $FormPostValues, '') === FALSE)
         unset($FormPostValues['Sink']);
         
      // Validate the form posted values
      if (
         $this->Validate($FormPostValues, $Insert)
         && $CommentModel->Validate($FormPostValues)
      ) {
         // If the post is new and it validates, make sure the user isn't spamming
         if (!$Insert || !$this->CheckForSpam('Discussion')) {
            $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
            $DiscussionID = intval(ArrayValue('DiscussionID', $Fields, 0));
            $Fields = RemoveKeyFromArray($Fields, 'DiscussionID'); // Remove the primary key from the fields for saving
            $Discussion = FALSE;
            if ($DiscussionID > 0) {
               $this->SQL->Put($this->Name, $Fields, array($this->PrimaryKey => $DiscussionID));
            
               // Get the CommentID from the discussion table before saving
               $FormPostValues['CommentID'] = $this->SQL
                  ->Select('FirstCommentID')
                  ->From('Discussion')
                  ->Where('DiscussionID', $DiscussionID)
                  ->Get()
                  ->FirstRow()
                  ->FirstCommentID;
               $CommentModel->Save($FormPostValues);
            } else {
               $DiscussionID = $this->SQL->Insert($this->Name, $Fields);
               // Assign the new DiscussionID to the comment before saving
               $FormPostValues['IsNewDiscussion'] = TRUE;
               $FormPostValues['DiscussionID'] = $DiscussionID;
               $CommentID = $CommentModel->Save($FormPostValues);
               // Assign the FirstCommentID to the discussion table
               $this->SQL->Put($this->Name,
                  array('FirstCommentID' => $CommentID, 'LastCommentID' => $CommentID),
                  array($this->PrimaryKey => $DiscussionID)
               );
               
               $this->EventArguments['FormPostValues'] = $FormPostValues;
               $this->EventArguments['InsertFields'] = $Fields;
               $this->EventArguments['DiscussionID'] = $DiscussionID;
               $this->FireEvent('AfterSaveDiscussion');
               
               // Notify users of mentions
               $DiscussionName = ArrayValue('Name', $Fields, '');
               $Usernames = GetMentions($DiscussionName);
               $UserModel = Gdn::UserModel();
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetWhere(array('Name' => $Username))->FirstRow();
                  if ($User && $User->UserID != $Session->UserID) {
                     AddActivity(
                        $User->UserID,
                        'DiscussionMention',
                        '',
                        $Session->UserID,
                        '/discussion/'.$DiscussionID.'/'.Format::Url($DiscussionName)
                     );
                  }
               }
               $DiscussionName = ArrayValue('Name', $Fields, '');
               $this->RecordActivity($Session->UserID, $DiscussionID, $DiscussionName);
            }
            $Data = $this->SQL
               ->Select('CategoryID')
               ->From('Discussion')
               ->Where('DiscussionID', $DiscussionID)
               ->Get();
            
            $CategoryID = FALSE;
            if ($Data->NumRows() > 0)
               $CategoryID = $Data->FirstRow()->CategoryID;

            $this->UpdateDiscussionCount($CategoryID);
         }
      } else {
         // Make sure that all of the validation results from both validations are present for view by the form
         foreach ($CommentModel->ValidationResults() as $FieldName => $Results) {
            foreach ($Results as $Result) {
               $this->Validation->AddValidationResult($FieldName, $Result);
            }
         }
      }
      return $DiscussionID;
   }
   
   public function RecordActivity($UserID, $DiscussionID, $DiscussionName) {
      // Report that the discussion was created
      AddActivity(
         $UserID,
         'NewDiscussion',
         Anchor(Format::Text($DiscussionName), 'vanilla/discussion/'.$DiscussionID.'/'.Format::Url($DiscussionName))
      );
      
      // Get the user's discussion count
      $Data = $this->SQL
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion')
         ->Where('InsertUserID', $UserID)
         ->Get();
      
      // Save the count to the user table
      $this->SQL
         ->Update('User')
         ->Set('CountDiscussions', $Data->NumRows() > 0 ? $Data->FirstRow()->CountDiscussions : 0)
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   /**
    * Updates the CountDiscussions value on the category based on the CategoryID
    * being saved. 
    *
    * @param int The DiscussionID relating to the category we are updating.
    */
   public function UpdateDiscussionCount($CategoryID) {
      if (is_numeric($CategoryID) && $CategoryID > 0) {
         $Data = $this->SQL
            ->Select('DiscussionID', 'count', 'CountDiscussions')
            ->From('Discussion')
            ->Where('CategoryID', $CategoryID)
            ->Get()
            ->FirstRow();
         $Count = $Data ? $Data->CountDiscussions : 0;
         
         if ($Count >= 0) {
            $this->SQL
               ->Update('Category')
               ->Set('CountDiscussions', $Count)
               ->Where('CategoryID', $CategoryID)
               ->Put();
         }
      }
   }
   
   /**
    * Announces (or unannounces) a discussion. Returns the value that was set.
    */
   public function SetProperty($DiscussionID, $Property, $ForceValue = FALSE) {
      if ($ForceValue !== FALSE) {
         $Value = $ForceValue;
      } else {
         $Value = '1';
         $Discussion = $this->GetID($DiscussionID);
         $Value = ($Discussion->$Property == '1' ? '0' : '1');
      }
      $this->SQL
         ->Update('Discussion')
         ->Set($Property, $Value)
         ->Where('DiscussionID', $DiscussionID)
         ->Put();
      return $Value;
   }
      
   /**
    * Bookmarks (or unbookmarks) a discussion. Returns the current state of the
    * bookmark (ie. TRUE for bookmarked, FALSE for unbookmarked)
    */
   public function BookmarkDiscussion($DiscussionID, $UserID, &$Discussion = NULL) {
      $State = '1';
      $Discussion = $this->GetID($DiscussionID);
      if ($Discussion->CountCommentWatch == '') {
         $this->SQL
            ->Insert('UserDiscussion', array(
               'UserID' => $UserID,
               'DiscussionID' => $DiscussionID,
               'CountComments' => 0,
               'DateLastViewed' => Format::ToDateTime(),
               'Bookmarked' => '1'
            ));
      } else {
         $State = ($Discussion->Bookmarked == '1' ? '0' : '1');
         $this->SQL
            ->Update('UserDiscussion')
            ->Set('Bookmarked', $State)
            ->Where('UserID', $UserID)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
      }
      $this->EventArguments['Discussion'] = $Discussion;
      $this->EventArguments['State'] = $State;
      $this->FireEvent('AfterBookmarkDiscussion');
      return $State == '1' ? TRUE : FALSE;
   }
   
   /**
    * The number of bookmarks the specified $UserID has.
    */
   public function BookmarkCount($UserID) {
      $Data = $this->SQL
         ->Select('ud.DiscussionID', 'count', 'Count')
         ->From('UserDiscussion ud')
         ->Join('Discussion d', 'd.DiscussionID = ud.DiscussionID')
         ->Where('ud.UserID', $UserID)
         ->Where('ud.Bookmarked', '1')
         ->Get()
         ->FirstRow();
         
      if ($Data !== FALSE)
         return $Data->Count;
      
      return 0;
   }
   
   public function Delete($DiscussionID) {
      $Data = $this->SQL
         ->Select('CategoryID,InsertUserID')
         ->From('Discussion')
         ->Where('DiscussionID', $DiscussionID)
         ->Get();
      
      $UserID = FALSE;
      $CategoryID = FALSE;
      if ($Data->NumRows() > 0) {
         $UserID = $Data->FirstRow()->InsertUserID;
         $CategoryID = $Data->FirstRow()->CategoryID;
      }
      
      $this->SQL->Delete('Draft', array('DiscussionID' => $DiscussionID));
      $this->SQL->Delete('Comment', array('DiscussionID' => $DiscussionID));
      $this->SQL->Delete('Discussion', array('DiscussionID' => $DiscussionID));
      $this->UpdateDiscussionCount($CategoryID);
      
      // Get the user's discussion count
      $Data = $this->SQL
         ->Select('DiscussionID', 'count', 'CountDiscussions')
         ->From('Discussion')
         ->Where('InsertUserID', $UserID)
         ->Get();
      
      // Save the count to the user table
      $this->SQL
         ->Update('User')
         ->Set('CountDiscussions', $Data->NumRows() > 0 ? $Data->FirstRow()->CountDiscussions : 0)
         ->Where('UserID', $UserID)
         ->Put();      
      
      return TRUE;
   }
}
