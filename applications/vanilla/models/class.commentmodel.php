<?php if (!defined('APPLICATION')) exit();

class Gdn_CommentModel extends Gdn_VanillaModel {
   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('Comment');
   }
   
   /**
    * Select the data for a single comment.
    *
    * @param boolean $FireEvent Whether or not to fire the event.
    * This is a bit of a kludge to fix an issue with the VanillaCommentReplies plugin.
    */
   public function CommentQuery($FireEvent = TRUE) {
      $this->SQL->Select('c.*')
         ->Select('iu.Name', '', 'InsertName')
         ->Select('iup.Name', '', 'InsertPhoto')
         ->Select('uu.Name', '', 'UpdateName')
         ->Select('du.Name', '', 'DeleteName')
         ->SelectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
         ->From('Comment c')
         ->Join('User iu', 'c.InsertUserID = iu.UserID', 'left')
         ->Join('Photo iup', 'iu.PhotoID = iup.PhotoID', 'left')
         ->Join('User uu', 'c.UpdateUserID = uu.UserID', 'left')
         ->Join('User du', 'c.DeleteUserID = du.UserID', 'left');
      if($FireEvent)
         $this->FireEvent('AfterCommentQuery');
   }
   
   public function Get($DiscussionID, $Limit, $Offset = 0) {
      $this->CommentQuery();
      $this->FireEvent('BeforeGet');
      return $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->OrderBy('c.DateInserted', 'asc')
         ->Limit($Limit, $Offset)
         ->Get();
   }

   public function SetWatch($Discussion, $Limit, $Offset, $TotalComments) {
      // Record the user's watch data
      $Session = Gdn::Session();
      if ($Session->UserID > 0) {
         $CountWatch = $Limit + $Offset;
         if ($CountWatch > $TotalComments)
            $CountWatch = $TotalComments;
            
         if (is_numeric($Discussion->CountCommentWatch)) {
            // Update the watch data
            $this->SQL->Put(
               'UserDiscussion',
               array(
                  'CountComments' => $CountWatch,
                  'DateLastViewed' => Format::ToDateTime()
               ),
               array(
                  'UserID' => $Session->UserID,
                  'DiscussionID' => $Discussion->DiscussionID,
                  'CountComments <' => $CountWatch
               )
            );
         } else {
            // Insert watch data
            $this->SQL->Insert(
               'UserDiscussion',
               array(
                  'UserID' => $Session->UserID,
                  'DiscussionID' => $Discussion->DiscussionID,
                  'CountComments' => $CountWatch,
                  'DateLastViewed' => Format::ToDateTime()
               )
            );
         }
      }
   }

   public function GetCount($DiscussionID) {
      $this->FireEvent('BeforeGetCount');
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow()
         ->CountComments;
   }

   public function GetCountWhere($Where = FALSE) {
      if (is_array($Where))
         $this->SQL->Where($Where);
         
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   public function GetID($CommentID) {
      $this->CommentQuery(FALSE);
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
   }
   
   public function GetNew($DiscussionID, $LastCommentID) {
      $this->CommentQuery();      
      $this->FireEvent('BeforeGetNew');
      return $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.CommentID >', $LastCommentID)
         ->OrderBy('c.DateInserted', 'asc')
         ->Get();
   }
   
   /**
    * Returns the offset of the specified comment in it's related discussion.
    *
    * @param int The comment id for which the offset is being defined.
    */
   public function GetOffset($CommentID) {
      $this->FireEvent('BeforeGetOffset');
      return $this->SQL
         ->Select('c2.CommentID', 'count', 'CountComments')
         ->From('Comment c')
         ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
         ->Join('Comment c2', 'd.DiscussionID = c2.DiscussionID')
         ->Where('c2.CommentID <=', $CommentID)
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   public function Save($FormPostValues) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $MaxCommentLength = Gdn::Config('Vanilla.Comment.MaxLength');
      if (is_numeric($MaxCommentLength) && $MaxCommentLength > 0) {
         $this->Validation->SetSchemaProperty('Body', 'Length', $MaxCommentLength);
         $this->Validation->ApplyRule('Body', 'Length');
      }
      
      $CommentID = ArrayValue('CommentID', $FormPostValues);
      $CommentID = is_numeric($CommentID) && $CommentID > 0 ? $CommentID : FALSE;
      $Insert = $CommentID === FALSE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);
      else
         $this->AddUpdateFields($FormPostValues);
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         // If the post is new and it validates, check for spam
         if (!$Insert || !$this->CheckForSpam('Comment')) {
            $Fields = $this->Validation->SchemaValidationFields();
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
            
            $DiscussionModel = new Gdn_DiscussionModel();
            $DiscussionID = ArrayValue('DiscussionID', $Fields);
            $Discussion = $DiscussionModel->GetID($DiscussionID);
            if ($Insert === FALSE) {
               $this->SQL->Put($this->Name, $Fields, array('CommentID' => $CommentID));
            } else {
               // Make sure that the comments get formatted in the method defined by Garden
               $Fields['Format'] = Gdn::Config('Garden.InputFormatter', '');
               $CommentID = $this->SQL->Insert($this->Name, $Fields);
               $this->EventArguments['CommentID'] = $CommentID;
               // IsNewDiscussion is passed when the first comment for new discussions are created.
               $this->EventArguments['IsNewDiscussion'] = ArrayValue('IsNewDiscussion', $FormPostValues);
               $this->FireEvent('AfterSaveComment');
               
               // Notify any users who were mentioned in the comment
               $Usernames = GetMentions($Fields['Body']);
               $UserModel = Gdn::UserModel();
               $Story = ArrayValue('Body', $Fields, '');
               $NotifiedUsers = array();
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetWhere(array('Name' => $Username))->FirstRow();
                  if ($User && $User->UserID != $Session->UserID) {
                     $NotifiedUsers[] = $User->UserID;   
                     $ActivityModel = new Gdn_ActivityModel();   
                     $ActivityID = $ActivityModel->Add(
                        $Session->UserID,
                        'CommentMention',
                        Anchor(Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
                        $User->UserID,
                        '',
                        'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                        FALSE
                     );
                     $ActivityModel->SendNotification($ActivityID, $Story);
                  }
               }
               
               // Notify users who have bookmarked the discussion
               $BookmarkData = $DiscussionModel->GetBookmarkUsers($DiscussionID);
               foreach ($BookmarkData->Result() as $Bookmark) {
                  if (!in_array($Bookmark->UserID, $NotifiedUsers) && $Bookmark->UserID != $Session->UserID) {
                     $NotifiedUsers[] = $Bookmark->UserID;
                     $ActivityModel = new Gdn_ActivityModel();   
                     $ActivityID = $ActivityModel->Add(
                        $Session->UserID,
                        'BookmarkComment',
                        Anchor(Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
                        $Bookmark->UserID,
                        '',
                        'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                        FALSE
                     );
                     $ActivityModel->SendNotification($ActivityID, $Story);
                  }
               }
            }
            
            // Record user-comment activity
            if ($Insert === TRUE && $Discussion !== FALSE && !in_array($Session->UserID, $NotifiedUsers))
               $this->RecordActivity($Discussion, $Session->UserID, $CommentID); 

            $this->UpdateCommentCount($DiscussionID);
            
            // Update the discussion author's CountUnreadDiscussions (ie.
            // the number of discussions created by the user that s/he has
            // unread messages in) if this comment was not added by the
            // discussion author.
            $Data = $this->SQL
               ->Select('d.InsertUserID')
               ->Select('d.DiscussionID', 'count', 'CountDiscussions')
               ->From('Discussion d')
               ->Join('Comment c', 'd.DiscussionID = c.DiscussionID')
               ->Join('UserDiscussion w', 'd.DiscussionID = w.DiscussionID and w.UserID = d.InsertUserID')
               ->Where('w.CountComments >', 0)
               ->Where('c.InsertUserID', $Session->UserID)
               ->Where('c.InsertUserID <>', 'd.InsertUserID', TRUE, FALSE)
               ->GroupBy('d.InsertUserID')
               ->Get();
            
            if ($Data->NumRows() > 0) {
               $UserData = $Data->FirstRow();
               $this->SQL
                  ->Update('User')
                  ->Set('CountUnreadDiscussions', $UserData->CountDiscussions)
                  ->Where('UserID', $UserData->InsertUserID)
                  ->Put();
            }
            
            $this->UpdateUser($Session->UserID);
         }
      }
      return $CommentID;
   }
      
   public function RecordActivity($Discussion, $ActivityUserID, $CommentID) {
      // Get the author of the discussion
      if ($Discussion->InsertUserID != $ActivityUserID) 
         AddActivity(
            $ActivityUserID,
            'DiscussionComment',
            Anchor(Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
            $Discussion->InsertUserID,
            'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID
         );
   }
   
   /**
    * Updates the CountComments value on the discussion based on the CommentID
    * being saved. 
    *
    * @param int The CommentID relating to the discussion we are updating.
    */
   public function UpdateCommentCount($DiscussionID) {
      $this->FireEvent('BeforeUpdateCommentCount');
      
      $Data = $this->SQL
         ->Select('c.CommentID', 'max', 'LastCommentID')
         ->Select('c.DateInserted', 'max', 'DateLastComment')
         ->Select('c.CommentID', 'count', 'CountComments')
         ->Select('d.Sink')
         ->From('Comment c')
         ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID')
         ->Where('c.DiscussionID', $DiscussionID)
         ->GroupBy('d.Sink')
         ->Get()->FirstRow();
      
      if (!is_null($Data)) {
         $this->SQL->Update('Discussion');
         if ($Data->Sink == '0')
            $this->SQL->Set('DateLastComment', $Data->DateLastComment);

         $this->SQL->Set('LastCommentID', $Data->LastCommentID)
            ->Set('CountComments', $Data->CountComments)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
      }
   }
   
   public function UpdateUser($UserID) {
      // Retrieve a comment count (don't include FirstCommentIDs)
      $CountComments = $this->SQL
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('Comment c')
         ->Join('Discussion d', 'c.DiscussionID = d.DiscussionID and c.CommentID <> d.FirstCommentID')
         ->Where('c.InsertUserID', $UserID)
         ->Get()
         ->FirstRow()
         ->CountComments;
      
      // Save to the attributes column of the user table for this user.
      $this->SQL
         ->Update('User')
         ->Set('CountComments', $CountComments)
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   public function Delete($CommentID) {
      $this->EventArguments['CommentID'] = $CommentID;

      // Check to see if this is the first or last comment in the discussion
      $Data = $this->SQL
         ->Select('d.DiscussionID, d.FirstCommentID, d.LastCommentID, c.InsertUserID')
         ->From('Discussion d')
         ->Join('Comment c', 'd.DiscussionID = c.DiscussionID')
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
         
      if ($Data) {
         if ($Data->FirstCommentID == $CommentID) {
            $DiscussionModel = new Gdn_DiscussionModel();
            $DiscussionModel->Delete($Data->DiscussionID);
         } else {
            // If this is the last comment, get the one before and update the LastCommentID field
            if ($Data->LastCommentID == $CommentID) {
               $OldData = $this->SQL
                  ->Select('c.CommentID')
                  ->From('Comment c')
                  ->Where('c.DiscussionID', $Data->DiscussionID)
                  ->OrderBy('c.DateInserted', 'desc')
                  ->Limit(1, 1)
                  ->Get()
                  ->FirstRow();
               if (is_object($OldData)) {
                  $this->SQL->Update('Discussion')
                     ->Set('LastCommentID', $OldData->CommentID)
                     ->Where('DiscussionID', $Data->DiscussionID)
                     ->Put();
               }
            }
            
            $this->FireEvent('DeleteComment');
            // Delete the comment
            $this->SQL->Delete('Comment', array('CommentID' => $CommentID));
         }
         // Update the user's comment count
         $this->UpdateUser($Data->InsertUserID);
      }
      return TRUE;
   }   
}