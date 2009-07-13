<?php if (!defined('APPLICATION')) exit();

class CommentModel extends VanillaModel {
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
         $this->FireEvent('CommentQueryAfter');
   }
   
   public function Get($DiscussionID, $Limit, $Offset = 0) {
      $this->CommentQuery();
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
      $this->FireEvent('GetCountBefore');
      return $this->SQL->Select('CommentID', 'count', 'CountComments')
         ->From('Comment')
         ->Where('DiscussionID', $DiscussionID)
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
      $this->FireEvent('GetOffsetBefore');
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
   
   /**
    * Reindex comments for the search.
    *
    * @param int $DiscussionID Optional. A discussion ID to index the comments for.
    */
   public function Reindex($DiscussionID = NULL, $Max = 250, $Echo = FALSE) {
      if(!is_null($DiscussionID))
         $Max = 0;
         
      $Search = Gdn::Factory('SearchModel');
      if(is_null($Search)) {
         return;
      }
      
      $StartTime = time(TRUE);
      
      if($Echo) {
         echo 'Start Time: ', date('j M Y g:ia'), "\n";
         
         // Get a count of all the comments that have to be reindexed.
         $Count = $this->SQL->GetCount('Comment', !$DiscussionID ? FALSE : array('DiscussionID' => $DiscussionID));
         if($Max > 0 && $Count > $Max) {
            $Count = $Max;
         } elseif($Count == 0) {
            $Count = 1;
         }
         echo 'Comments to reindex: ', number_format($Count), "\n";
         if($Count >= 1000)
            $Dec = 2;
         elseif($Count > 300)
            $Dec = 1;
         else
            $Dec = 0;
      }
      
      // Get all of the comments to reindex.
      $this->SQL
         ->Select('d.DiscussionID, d.Name, d.FirstCommentID, d.CategoryID')
         ->Select('c.CommentID, c.Body, c.InsertUserID, c.DateInserted')
         ->Select('sd.DocumentID')
         ->From('Discussion d')
         ->Join('Comment c', 'c.DiscussionID = d.DiscussionID')
         ->Join('SearchDocument sd', 'sd.PrimaryID = c.CommentID and sd.TableName = \'Comment\'', 'left');
         
      if(!is_null($DiscussionID)) {
         $this->SQL->Where('d.DiscussionID', $DiscussionID);
      }
      if($Max > 0) {
         $this->SQL->Where('c.Flag', '1')->Limit($Max);
      }
      
      $Data = $this->SQL->Get();
      if($Max > 0) {
         $Data = $Data->ResultObject();
         $CommentIDs = array();
         foreach($Data as $Row) {
            $CommentIDs[] = $Row->CommentID;
         }
         $this->SQL->Update('Comment', array('Flag' => 2))->WhereIn('CommentID', $CommentIDs)->Put();
      } else {
         $Data = $Data->PDOStatement();
         $Data->setFetchMode(PDO::FETCH_OBJ);
      }
      
      
      $CurrentIndex = 0;
      $StartIndexTime = time();
      foreach($Data as $Row) {
         // Only index the title with the first comment.
         if($Row->FirstCommentID == $Row->CommentID)
            $Keywords = $Row->Name . ' ' . $Row->Body;
         else
            $Keywords = $Row->Body;
         
         $Document = array(
            'Title' => $Row->Name,
            'Summary' => $Row->Body,
            'TableName' => 'Comment',
            'PrimaryID' => $Row->CommentID,
            'PermissionJunctionID' => $Row->CategoryID,
            'InsertUserID' => $Row->InsertUserID,
            'DateInserted' => $Row->DateInserted,
            'Url' => '/discussion/comment/'.$Row->CommentID.'/#Comment_'.$Row->CommentID,
         );
         
         if($Echo)
            echo $Document['Url'];
         
         if(!is_null($Row->DocumentID)) {
            $Document['DocumentID'] = $Row->DocumentID;
         }
         
         try {
            $Search->Index($Document, $Keywords);
            // Update the comment to show it's been indexed.
            $this->SQL->Update('Comment', array('Flag' => 0))->Where('CommentID', $Row->CommentID)->Put();
         } catch(Exception $Ex) {
            echo "Exception\n";
            $DocumentID = $this->SQL->GetWhere('SearchDocument', array('PrimaryID' => $Row->CommentID, 'TableName' => 'Comment'))->FirstRow()->DocumentID;
            $this->SQL->Delete('SearchKeywordDocument', array('DocumentID' => $DocumentID));
            $this->SQL->Delete('SearchDocument', array('DocumentID' => $DocumentID));
            $this->SQL->Update('Comment', array('Flag' => 3), array('CommentID' => $Row->CommentID));
            continue;
         }
         
         if($Echo) {
            // Calculate percent complete.
            $Percent = $CurrentIndex / $Count;
            // Calculate time left.
            $Elapsed = time() - $StartIndexTime;
            if($Percent != 0) {
               $TotalTime = $Elapsed / $Percent;
               $TimeLeft = $TotalTime - $Elapsed;
            }
            
            echo ' (', number_format(100 * $Percent, $Dec)  , '%';
            if(($CurrentIndex % 10) == 0 && isset($TimeLeft)) {
               printf(', Elapsed: %s, ~Time Left: %s, Memory: %sb', Format::Timespan(time() - $StartTime), Format::Timespan($TimeLeft), number_format(memory_get_usage()));
            }
            echo ")\n";
         }
         
         ++$CurrentIndex;
      }
      
      if($Echo) {
         echo 'Finish Time: ', date('j M Y g:ia'), "\n";
         echo 'Total Time: ', Format::Timespan(time() - $StartTime), "\n";
      }
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
            if ($Insert === FALSE) {
               $this->SQL->Put($this->Name, $Fields, array('CommentID' => $CommentID));
            } else {
               // Make sure that the comments get formatted in the method defined by Garden
               $Fields['Format'] = Gdn::Config('Garden.InputFormatter', '');
               $CommentID = $this->SQL->Insert($this->Name, $Fields);
               
               // Notify any users who were mentioned in the comment
               $Usernames = GetMentions($Fields['Body']);
               $UserModel = Gdn::UserModel();
               foreach ($Usernames as $Username) {
                  $User = $UserModel->GetWhere(array('Name' => $Username))->FirstRow();
                  if ($User && $User->UserID != $Session->UserID) {
                     AddActivity(
                        $Session->UserID,
                        'CommentMention',
                        '',
                        $User->UserID,
                        'discussion/comment/'.$CommentID.'/#Comment_'.$CommentID
                     );
                  }
               }
            }
            // Record user-comment activity
            $DiscussionID = ArrayValue('DiscussionID', $Fields);
            if ($DiscussionID !== FALSE)
               $this->RecordActivity($DiscussionID, $Session->UserID, $CommentID);

            $this->UpdateCommentCount($CommentID);
            
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
            
            // Index the post.
            $Search = Gdn::Factory('SearchModel');
            if(!is_null($Search)) {
               if(array_key_exists('Name', $FormPostValues) && array_key_exists('CategoryID', $FormPostValues)) {
                  $Title = $FormPostValues['Name'];
                  $CategoryID = $FormPostValues['CategoryID'];
               } else {
                  // Get the name from the discussion.
                  $Row = $this->SQL
                     ->GetWhere('Discussion', array('DiscussionID' => $DiscussionID))
                     ->FirstRow();
                  if(is_object($Row)) {
                     $Title = $Row->Name;
                     $CategoryID = $Row->CategoryID;
                  }
               }
               
               $Offset = $this->GetOffset($CommentID);
               
               // Index the discussion.
               $Document = array(
                  'TableName' => 'Comment',
                  'PrimaryID' => $CommentID,
                  'PermissionJunctionID' => $CategoryID,
                  'Title' => $Title,
                  'Summary' => $FormPostValues['Body'],
                  'Url' => '/discussion/comment/'.$CommentID.'/#Comment_'.$CommentID,
                  'InsertUserID' => $Session->UserID);
               $Search->Index($Document, $Offset == 1 ? $Document['Title'] . ' ' . $Document['Summary'] : NULL);
            }
            $this->UpdateUser($Session->UserID);
         }
      }
      return $CommentID;
   }
      
   public function RecordActivity($DiscussionID, $ActivityUserID, $CommentID) {
      // Get the author of the discussion
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetID($DiscussionID);
      if ($Discussion->InsertUserID != $ActivityUserID) 
         AddActivity(
            $ActivityUserID,
            'DiscussionComment',
            '',
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
   public function UpdateCommentCount($CommentID) {
      $this->FireEvent('UpdateCommentCountBefore');
      $Data = $this->SQL
         ->Select('c2.DiscussionID')
         ->Select('c2.DiscussionID', 'count', 'CountComments')
         ->From('Comment c')
         ->Join('Comment c2', 'c.DiscussionID = c2.DiscussionID')
         ->Join('Discussion d', 'c2.DiscussionID = d.DiscussionID')
         ->Where('c.CommentID', $CommentID)
         ->Where('c2.CommentID <>', 'd.FirstCommentID', TRUE, FALSE)
         ->GroupBy('c2.DiscussionID')
         ->Get()
         ->FirstRow();
      $Count = $Data ? $Data->CountComments : 0;
      
      if ($Count > 0) {
         $Data = $this->SQL
            ->Select('DateInserted, CommentID, DiscussionID')
            ->From('Comment')
            ->Where('DiscussionID', $Data->DiscussionID)
            ->OrderBy('DateInserted', 'desc')
            ->Limit(1, 0)
            ->Get()
            ->FirstRow();
      
         $this->SQL
            ->Update('Discussion')
            ->Set('DateLastComment', $Data->DateInserted)
            ->Set('LastCommentID', $Data->CommentID)
            ->Set('CountComments', $Count)
            ->Where('DiscussionID', $Data->DiscussionID)
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

      // Check to see if this is the first comment in the discussion
      $Data = $this->SQL
         ->Select('d.DiscussionID, d.FirstCommentID, c.InsertUserID')
         ->From('Discussion d')
         ->Join('Comment c', 'd.DiscussionID = c.DiscussionID')
         ->Where('c.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
         
      if ($Data) {
         if ($Data->FirstCommentID == $CommentID) {
            $DiscussionModel = new DiscussionModel();
            $DiscussionModel->Delete($Data->DiscussionID);
         } else {
            $this->FireEvent('DeleteComment');
            // Delete the comment
            $this->SQL->Delete('Comment', array('CommentID' => $CommentID));
            
            // Delete the search.
            $Search = Gdn::Factory('SearchModel');
            if(!is_null($Search)) {
               $Search->Delete(array('TableName' => 'Comment', 'PrimaryID' => $CommentID));
            }
         }
         // Update the user's comment count
         $this->UpdateUser($Data->InsertUserID);
      }
      return TRUE;
   }   
}