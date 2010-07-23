<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class CommentModel extends VanillaModel {
	
	public $OrderBy = 'c.DateInserted';
	public $OrderDirection = 'asc';
	
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
         ->Select('iu.Photo', '', 'InsertPhoto')
         ->Select('uu.Name', '', 'UpdateName')
         ->Select('du.Name', '', 'DeleteName')
         ->SelectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
         ->From('Comment c')
         ->Join('User iu', 'c.InsertUserID = iu.UserID', 'left')
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
         ->OrderBy($this->OrderBy, $this->OrderDirection)
         ->Limit($Limit, $Offset)
         ->Get();
   }
	
	// Sets the UserComment Score value. Returns the total score.
	public function SetUserScore($CommentID, $UserID, $Score) {
		// Insert or update the UserComment row
		$this->SQL->Replace(
			'UserComment',
			array('Score' => $Score),
			array('CommentID' => $CommentID, 'UserID' => $UserID)
		);
		
		// Get the total new score
		$TotalScore = $this->SQL->Select('Score', 'sum', 'TotalScore')
			->From('UserComment')
			->Where('CommentID', $CommentID)
			->Get()
			->FirstRow()
			->TotalScore;
		
		// Update the comment's cached version
		$this->SQL->Update('Comment')
			->Set('Score', $TotalScore)
			->Where('CommentID', $CommentID)
			->Put();
			
		return $TotalScore;
	}

	// Gets the UserComment Score value for the specified user
	public function GetUserScore($CommentID, $UserID) {
		$Data = $this->SQL->Select('Score')
			->From('UserComment')
			->Where('CommentID', $CommentID)
			->Where('UserID', $UserID)
			->Get()
			->FirstRow();
		
		return $Data ? $Data->Score : 0;
	}

   public function SetWatch($Discussion, $Limit, $Offset, $TotalComments) {
		// echo 'Setting Watch Records for Discussion '.$DiscussionID.' to Limit: '.$Limit.' Offset: '.$Offset.' CountWatch = ' . ($Limit + $Offset) . ' TotalComments ' . $TotalComments;
		// die();
		
      // Record the user's watch data
      $Session = Gdn::Session();
      if ($Session->UserID > 0) {
         $CountWatch = $Limit + $Offset;
         if ($CountWatch > $TotalComments)
            $CountWatch = $TotalComments;
            
         if (is_numeric($Discussion->CountCommentWatch)) {
            // Update the watch data
				if($CountWatch != $Discussion->CountCommentWatch && $CountWatch > $Discussion->CountCommentWatch) {
					// Only update the watch if there are new comments.
					$this->SQL->Put(
						'UserDiscussion',
						array(
							'CountComments' => $CountWatch,
                  'DateLastViewed' => Gdn_Format::ToDateTime()
						),
						array(
							'UserID' => $Session->UserID,
							'DiscussionID' => $Discussion->DiscussionID
						)
					);
				}
         } else {
				// Make sure the discussion isn't archived.
				$ArchiveDate = Gdn::Config('Vanilla.Archive.Date');
				if(!$ArchiveDate || (Gdn_Format::ToTimestamp($Discussion->DateLastComment) > Gdn_Format::ToTimestamp($ArchiveDate))) {
					// Insert watch data
					$this->SQL->Insert(
						'UserDiscussion',
						array(
							'UserID' => $Session->UserID,
							'DiscussionID' => $Discussion->DiscussionID,
							'CountComments' => $CountWatch,
                  'DateLastViewed' => Gdn_Format::ToDateTime()
						)
					);
				}
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
   
   public function GetID($CommentID, $ResultType = DATASET_TYPE_OBJECT) {
      $this->CommentQuery(FALSE);
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow($ResultType);
   }
   
   public function GetIDData($CommentID) {
      $this->CommentQuery(FALSE);
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get();
   }
   
   public function GetNew($DiscussionID, $LastCommentID) {
      $this->CommentQuery();      
		$Operator = $this->OrderDirection == 'asc' ? '>' : '<';
		$this->EventArguments['Operator'] = &$Operator;
      $this->FireEvent('BeforeGetNew');
      return $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.CommentID '.$Operator, $LastCommentID)
         ->OrderBy($this->OrderBy, $this->OrderDirection)
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

      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->EventArguments['CommentID'] = $CommentID;
      $this->FireEvent('BeforeSaveComment');
      
      // Validate the form posted values
      if ($this->Validate($FormPostValues, $Insert)) {
         // If the post is new and it validates, check for spam
         if (!$Insert || !$this->CheckForSpam('Comment')) {
            $Fields = $this->Validation->SchemaValidationFields();
            $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
            
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
            }
         }
      }
      return $CommentID;
   }

   // Save the stuff about a comment
   public function Save2($CommentID, $Insert, $CheckExisting = TRUE) {
      $Fields = $this->GetID($CommentID, DATASET_TYPE_ARRAY);
      $Session = Gdn::Session();

      // Make a quick check so that only the user making the comment can make the notification.
      // This check may be used in the future so should not be depended on later in the method.
      if ($Fields['InsertUserID'] != $Session->UserID)
         return;

      $DiscussionModel = new DiscussionModel();
      $DiscussionID = ArrayValue('DiscussionID', $Fields);
      $Discussion = $DiscussionModel->GetID($DiscussionID);

      if ($Insert) {
         // Notify any users who were mentioned in the comment
         $Usernames = GetMentions($Fields['Body']);
         $UserModel = Gdn::UserModel();
         $Story = ArrayValue('Body', $Fields, '');
         $NotifiedUsers = array();
         foreach ($Usernames as $Username) {
            $User = $UserModel->GetByUsername($Username);
            if ($User && $User->UserID != $Session->UserID) {
               $NotifiedUsers[] = $User->UserID;
               $ActivityModel = new ActivityModel();
               $ActivityID = $ActivityModel->Add(
                  $Session->UserID,
                  'CommentMention',
                  Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
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
               $ActivityModel = new ActivityModel();   
               $ActivityID = $ActivityModel->Add(
                  $Session->UserID,
                  'BookmarkComment',
                  Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
                  $Bookmark->UserID,
                  '',
                  'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                  FALSE
               );
               $ActivityModel->SendNotification($ActivityID, $Story);
            }
         }

         // Record user-comment activity
         if ($Discussion !== FALSE && !in_array($Session->UserID, $NotifiedUsers))
            $this->RecordActivity($Discussion, $Session->UserID, $CommentID, 'Only');
      }

      $this->UpdateCommentCount($DiscussionID);
      // Mark the comment read (note: add 1 to $Discussion->CountComments because this comment has been added since $Discussion was loaded)
      $this->SetWatch($Discussion, $Discussion->CountComments, $Discussion->CountComments+1, $Discussion->CountComments+1);


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
      
   public function RecordActivity($Discussion, $ActivityUserID, $CommentID, $SendEmail = '') {
      // Get the author of the discussion
      if ($Discussion->InsertUserID != $ActivityUserID) 
         AddActivity(
            $ActivityUserID,
            'DiscussionComment',
            Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
            $Discussion->InsertUserID,
            'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
            $SendEmail
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
            ->Set('CountComments', $Data->CountComments + 1)
            ->Where('DiscussionID', $DiscussionID)
            ->Put();
				
			// Update the last comment's user ID.
			$this->SQL
				->Update('Discussion d')
				->Update('Comment c')
				->Set('d.LastCommentUserID', 'c.InsertUserID', FALSE)
				->Where('d.DiscussionID', $DiscussionID)
				->Where('c.CommentID', 'd.LastCommentID', FALSE, FALSE);
			$this->SQL->Put();
      }
   }
   
   public function UpdateUser($UserID) {
      // Retrieve a comment count
      $CountComments = $this->SQL
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('Comment c')
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

      // Check to see if this is the last comment in the discussion
      $Data = $this->SQL
         ->Select('d.DiscussionID, d.LastCommentID, c.InsertUserID')
         ->From('Discussion d')
         ->Join('Comment c', 'd.DiscussionID = c.DiscussionID')
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
         
      if ($Data) {
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
			
			// Decrement the UserDiscussion comment count if the user has seen this comment
			$Offset = $this->GetOffset($CommentID);
			$this->SQL->Update('UserDiscussion')
				->Set('CountComments', 'CountComments - 1', FALSE)
				->Where('DiscussionID', $Data->DiscussionID)
				->Where('CountComments >', $Offset)
				->Put();
				
			// Decrement the Discussion's Comment Count
			$this->SQL->Update('Discussion')
				->Set('CountComments', 'CountComments - 1', FALSE)
				->Where('DiscussionID', $Data->DiscussionID)
				->Put();
			
			$this->FireEvent('DeleteComment');
			// Delete the comment
			$this->SQL->Delete('Comment', array('CommentID' => $CommentID));

         // Update the user's comment count
         $this->UpdateUser($Data->InsertUserID);
      }
      return TRUE;
   }
}