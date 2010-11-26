<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
/**
 * Comment Model
 *
 * @package Vanilla
 */
 
/**
 * Manages discussion comments.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class CommentModel extends VanillaModel {
   /**
    * List of fields to order results by.
    * 
    * @var array
    * @access protected
    * @since 2.0.0
    */
   protected $_OrderBy = array(array('c.DateInserted', ''));
   
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Comment');
      $this->FireEvent('AfterConstruct');
   }
   
   /**
    * Select the data for a single comment.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param bool $FireEvent Kludge to fix VanillaCommentReplies plugin.
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
   
   /**
    * Get comments for a discussion.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Which discussion to get comment from.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @return object SQL results.
    */
   public function Get($DiscussionID, $Limit, $Offset = 0) {
      $this->CommentQuery();
      $this->FireEvent('BeforeGet');
      $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Limit($Limit, $Offset);
      
      $this->OrderBy($this->SQL);

      return $this->SQL->Get();
   }
  
   /** 
    * Set the order of the comments or return current order. 
    * 
    * Getter/setter for $this->_OrderBy.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param mixed Field name(s) to order results by. May be a string or array of strings.
    * @return array $this->_OrderBy (optionally).
    */
   public function OrderBy($Value = NULL) {
      if ($Value === NULL)
         return $this->_OrderBy;

      if (is_string($Value))
         $Value = array($Value);

      if (is_array($Value)) {
         // Set the order of this object.
         $OrderBy = array();

         foreach($Value as $Part) {
            if (StringEndsWith($Part, ' desc', TRUE))
               $OrderBy[] = array(substr($Part, 0, -5), 'desc');
            elseif (StringEndsWith($Part, ' asc', TRUE))
               $OrderBy[] = array(substr($Part, 0, -4), 'asc');
            else
               $OrderBy[] = array($Part, 'asc');
         }
         $this->_OrderBy = $OrderBy;
      } elseif (is_a($Value, 'Gdn_SQLDriver')) {
         // Set the order of the given sql.
         foreach ($this->_OrderBy as $Parts) {
            $Value->OrderBy($Parts[0], $Parts[1]);
         }
      }
   }
	
	/**
	 * Sets the UserComment Score value. 
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of comment we're setting the score for.
    * @param int $UserID Unique ID of user scoring the comment.
    * @param int $Score Score being assigned to the comment.
	 * @return int New total score for the comment.
	 */
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
   
   /**
	 * Gets the UserComment Score value for the specified user.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of comment we're getting the score for.
    * @param int $UserID Unique ID of user who scored the comment.
	 * @return int Current score for the comment.
	 */
	public function GetUserScore($CommentID, $UserID) {
		$Data = $this->SQL->Select('Score')
			->From('UserComment')
			->Where('CommentID', $CommentID)
			->Where('UserID', $UserID)
			->Get()
			->FirstRow();
		
		return $Data ? $Data->Score : 0;
	}
   
   /**
	 * Record the user's watch data.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param object $Discussion Discussion being watched.
    * @param int $Limit Max number to get.
    * @param int $Offset Number to skip.
    * @param int $TotalComments Total in entire discussion (hard limit).
	 */
   public function SetWatch($Discussion, $Limit, $Offset, $TotalComments) {
      $Session = Gdn::Session();
      if ($Session->UserID > 0) {
         $CountWatch = $Limit + $Offset + 1; // Include the first comment (in the discussion table) in the count.
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
			// decho('Limit: '.$Limit.'; Offset: '.$Offset.'; CountComments: '.$TotalComments);
			// decho('Setting Watch Records for Discussion '.$Discussion->DiscussionID.' to CountWatch: '.$CountWatch.' Limit: '.$Limit.' Offset: '.$Offset.' TotalComments ' . $TotalComments);
			// die();
		}
   }

   /**
	 * Count total comments in a discussion specified by ID.
	 *
	 * Events: BeforeGetCount
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of discussion we're counting comments from.
    * @return object SQL result.
	 */
   public function GetCount($DiscussionID) {
      $this->FireEvent('BeforeGetCount');
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
         ->Get()
         ->FirstRow()
         ->CountComments + 1; // Add 1 so the comment in the discussion table is counted
   }
   
   /**
	 * Count total comments in a discussion specified by $Where conditions.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param array $Where Conditions
    * @return object SQL result.
	 */
   public function GetCountWhere($Where = FALSE) {
      if (is_array($Where))
         $this->SQL->Where($Where);
         
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   /**
	 * Get single comment by ID. Allows you to pick data format of return value.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment.
    * @param string $ResultType Format to return comment in.
    * @return mixed SQL result in format specified by $ResultType.
	 */
   public function GetID($CommentID, $ResultType = DATASET_TYPE_OBJECT) {
      $this->CommentQuery(FALSE); // FALSE supresses FireEvent
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow($ResultType);
   }
   
   /**
	 * Get single comment by ID as SQL result data.
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment.
    * @return object SQL result.
	 */
   public function GetIDData($CommentID) {
      $this->CommentQuery(FALSE); // FALSE supresses FireEvent
      return $this->SQL
         ->Where('c.CommentID', $CommentID)
         ->Get();
   }
   
   /**
	 * Get comments in a discussion since the specified one.
	 *
	 * Events: BeforeGetNew
	 * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of the discusion.
    * @param int $LastCommentID Unique ID of the comment.
    * @return object SQL result.
	 */
   public function GetNew($DiscussionID, $LastCommentID) {
      $this->CommentQuery();
      $this->FireEvent('BeforeGetNew');
      $this->OrderBy($this->SQL);
      return $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.CommentID >', $LastCommentID)
         ->Get();
   }
   
   /**
    * Gets the offset of the specified comment in its related discussion.
    * 
    * Events: BeforeGetOffset
    * 
    * @since 2.0.0
    * @access public
    *
    * @param mixed $Comment Unique ID or or a comment object for which the offset is being defined.
    * @return object SQL result.
    */
   public function GetOffset($Comment) {
      $this->FireEvent('BeforeGetOffset');
      
      if (is_numeric($Comment)) {
         $Comment = $this->GetID($Comment);
      }

      $this->SQL
         ->Select('c.CommentID', 'count', 'CountComments')
         ->From('Comment c')
         ->Where('c.DiscussionID', GetValue('DiscussionID', $Comment));

      $this->SQL->BeginWhereGroup();

      // Figure out the where clause based on the sort.
      foreach ($this->_OrderBy as $Part) {
         //$Op = count($this->_OrderBy) == 1 || isset($PrevWhere) ? '=' : '';
         list($Expr, $Value) = $this->_WhereFromOrderBy($Part, $Comment, '');

         if (!isset($PrevWhere)) {
            $this->SQL->Where($Expr, $Value);
         } else {
            $this->SQL->BeginWhereGroup();
            $this->SQL->OrWhere($PrevWhere[0], $PrevWhere[1]);
            $this->SQL->Where($Expr, $Value);
            $this->SQL->EndWhereGroup();
         }

         $PrevWhere = $this->_WhereFromOrderBy($Part, $Comment, '==');
      }

      $this->SQL->EndWhereGroup();

      return $this->SQL
         ->Get()
         ->FirstRow()
         ->CountComments;
   }
   
   /**
    * Builds Where statements for GetOffset method.
    * 
    * @since 2.0.0
    * @access protected
    * @see CommentModel::GetOffset()
    *
    * @param array $Part Value from $this->_OrderBy.
    * @param object $Comment
    * @param string $Op Comparison operator.
    * @return array Expression and value.
    */
   protected function _WhereFromOrderBy($Part, $Comment, $Op = '') {
      if (!$Op || $Op == '=')
         $Op = ($Part[1] == 'desc' ? '>' : '<').$Op;
      elseif ($Op == '==')
         $Op = '=';
      
      $Expr = $Part[0].' '.$Op;
      if (preg_match('/c\.(\w*\b)/', $Part[0], $Matches))
         $Field = $Matches[1];
      else
         $Field = $Part[0];
      $Value = GetValue($Field, $Comment);
      if (!$Value)
         $Value = 0;

      return array($Expr, $Value);
   }
   
   /**
    * Insert or update core data about the comment.
    * 
    * Events: BeforeSaveComment, AfterSaveComment.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $FormPostValues Data from the form model.
    * @return int $CommentID
    */
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
      
      // Validate $CommentID and whether this is an insert
      $CommentID = ArrayValue('CommentID', $FormPostValues);
      $CommentID = is_numeric($CommentID) && $CommentID > 0 ? $CommentID : FALSE;
      $Insert = $CommentID === FALSE;
      if ($Insert)
         $this->AddInsertFields($FormPostValues);
      else
         $this->AddUpdateFields($FormPostValues);
      
      // Prep and fire event
      $this->EventArguments['FormPostValues'] = &$FormPostValues;
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
      
      // Update discussion's comment count
      $DiscussionID = GetValue('DiscussionID', $FormPostValues);
      $this->UpdateCommentCount($DiscussionID);

      return $CommentID;
   }

   /**
    * Insert or update meta data about the comment.
    * 
    * Updates unread comment totals, bookmarks, and activity. Sends notifications.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $CommentID Unique ID for this comment.
    * @param int $Insert Used as a boolean for whether this is a new comment.
    * @param bool $CheckExisting Not used.
    */
   public function Save2($CommentID, $Insert, $CheckExisting = TRUE) {
      $Session = Gdn::Session();
      
      // Load comment data
      $Fields = $this->GetID($CommentID, DATASET_TYPE_ARRAY);

      // Make a quick check so that only the user making the comment can make the notification.
      // This check may be used in the future so should not be depended on later in the method.
      if ($Fields['InsertUserID'] != $Session->UserID)
         return;

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

      if ($Insert) {
			$DiscussionModel = new DiscussionModel();
			$DiscussionID = GetValue('DiscussionID', $Fields);
			$Discussion = $DiscussionModel->GetID($DiscussionID);
			// Prepare the notification queue
         $ActivityModel = new ActivityModel();
			$ActivityModel->ClearNotificationQueue();

         // Notify any users who were mentioned in the comment
         $Usernames = GetMentions($Fields['Body']);
         $UserModel = Gdn::UserModel();
         $Story = '['.$Discussion->Name."]\n".ArrayValue('Body', $Fields, '');
         $NotifiedUsers = array();
         foreach ($Usernames as $Username) {
            $User = $UserModel->GetByUsername($Username);
            if ($User && $User->UserID != $Session->UserID) {
               $NotifiedUsers[] = $User->UserID;
               $ActivityID = $ActivityModel->Add(
                  $Session->UserID,
                  'CommentMention',
                  Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
                  $User->UserID,
                  '',
                  'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                  FALSE
               );
               $ActivityModel->QueueNotification($ActivityID, $Story);
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
               $ActivityModel->QueueNotification($ActivityID, $Story);
            }
         }

         // Record user-comment activity
         if ($Discussion !== FALSE && !in_array($Session->UserID, $NotifiedUsers)) {
            $this->RecordActivity($ActivityModel, $Discussion, $Session->UserID, $CommentID, FALSE);
				$ActivityModel->QueueNotification($ActivityID, $Story);
			}
				
			// Send all notifications
			$ActivityModel->SendNotificationQueue();
      }
   }
   
   /**
    * Helper function for recording comment-related activity.
    * 
    * @since 2.0.0
    * @access public
    * @see CategoryModel::Save2()
    *
    * @param object $ActivityModel Passed along so we can use its Add method.
    * @param object $Discussion Discussion data used to compose link for activity stream.
    * @param int $ActivityUserID User taking the action; passed to ActivityModel::Add().
    * @param int $CommentID Unique ID of comment inserted or updated. Used to compose link for activity stream.
    * @param int $SendEmail Passed directly to ActivityModel::Add().
    */  
   public function RecordActivity(&$ActivityModel, $Discussion, $ActivityUserID, $CommentID, $SendEmail = '') {
      if ($Discussion->InsertUserID != $ActivityUserID)
			$ActivityModel->Add(
				$ActivityUserID,
				'DiscussionComment',
				Anchor(Gdn_Format::Text($Discussion->Name), 'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID),
				$Discussion->InsertUserID,
				'',
				'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
				$SendEmail
			);
	}
   
   /**
    * Updates the CountComments value on the discussion based on the CommentID being saved. 
    *
    * Events: BeforeUpdateCommentCount.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $DiscussionID Unique ID of the discussion we are updating.
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
         if ($Data->Sink == '0' && $Data->DateLastComment)
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
   
   /**
    * Update user's total comment count.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $UserID Unique ID of the user to be updated.
    */
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
   
   /**
    * Delete a comment.
    *
    * This is a hard delete that completely removes it from the database.
    * Events: DeleteComment.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $CommentID Unique ID of the comment to be deleted.
    * @param bool Always returns TRUE.
    */
   public function Delete($CommentID) {
      $this->EventArguments['CommentID'] = $CommentID;

      // Check to see if this is the last comment in the discussion
      $Data = $this->SQL
         ->Select('d.DiscussionID, d.LastCommentID, c.InsertUserID, d.DateInserted')
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
                  ->Set('LastCommentUserID', $OldData->InsertUserID)
                  ->Set('DateLastComment', $OldData->DateInserted)
						->Where('DiscussionID', $Data->DiscussionID)
						->Put();
				}
				else { // It was the ONLY comment
               $this->SQL->Update('Discussion')
                  ->Set('LastCommentID', NULL)
                  ->Set('LastCommentUserID', NULL)
                  ->Set('DateLastComment', $Data->DateInserted)
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