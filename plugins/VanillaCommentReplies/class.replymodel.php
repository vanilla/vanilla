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
 * A "reply" is essentially a comment that has a value in
 * Comment.ReplyCommentID other than NULL.
 */
class ReplyModel extends CommentModel {
   
   public function ReplyQuery() {
      return $this->SQL
         ->Select('c.*')
         ->Select('iu.Name', '', 'InsertName')
         ->Select('iup.Name', '', 'InsertPhoto')
         ->Select('uu.Name', '', 'UpdateName')
         ->Select('du.Name', '', 'DeleteName')
         ->SelectCase('c.DeleteUserID', array('null' => '0', '' => '1'), 'Deleted')
         ->From('Comment c')
         ->Join('User iu', 'c.InsertUserID = iu.UserID', 'left')
         ->Join('Photo iup', 'iu.PhotoID = iup.PhotoID', 'left')
         ->Join('User uu', 'c.UpdateUserID = uu.UserID', 'left')
         ->Join('User du', 'c.DeleteUserID = du.UserID', 'left')
         ->Where('c.ReplyCommentID is not null');
   }
   
   public function Get($DiscussionID, $FirstCommentID, $LastCommentID) {
      $this->ReplyQuery();
      return $this->SQL
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.ReplyCommentID >=', $FirstCommentID)
         ->Where('c.ReplyCommentID <=', $LastCommentID)
         ->OrderBy('c.ReplyCommentID', 'asc')
         ->OrderBy('c.DateInserted', 'asc')
         ->Get();
   }
   
   public function GetID($CommentID) {
      $this->ReplyQuery();
      return $this->SQL
         ->Where('CommentID', $CommentID)
         ->Get()
         ->FirstRow();
   }
   
   public function GetNew($ReplyCommentID, $LastCommentID) {
      $this->ReplyQuery();
      return $this->SQL
         ->Where('c.ReplyCommentID', $ReplyCommentID)
         ->Where('c.CommentID >', $LastCommentID)
         ->OrderBy('c.DateInserted', 'asc')
         ->Get();
   }
   
   public function GetAllNew($DiscussionID, $LastCommentID) {
      return $this
         ->ReplyQuery()
         ->Where('c.DiscussionID', $DiscussionID)
         ->Where('c.CommentID >', $LastCommentID)
         ->OrderBy('c.ReplyCommentID, c.DateInserted')
         ->Get();
   }
   
   public function Save($FormPostValues) {
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $this->Validation->ApplyRule('ReplyCommentID', 'Required');
      
      // Add/define extra fields for saving
      $ReplyCommentID = intval(ArrayValue('ReplyCommentID', $FormPostValues, 0));
      $Discussion = $this->SQL
         ->Select('c.DiscussionID, d.Name, d.CategoryID')
         ->From('Comment c')
         ->Join('Discussion d', 'd.DiscussionID = c.DiscussionID')
         ->Where('c.CommentID', $ReplyCommentID)
         ->Get()
         ->FirstRow();
         
      if (is_object($Discussion))
         $FormPostValues['DiscussionID'] = $Discussion->DiscussionID;
         
      $CommentID = ArrayValue('CommentID', $FormPostValues);
      $Insert = $CommentID === FALSE ? TRUE : FALSE;  
      if ($Insert) {
         $this->AddInsertFields($FormPostValues);
         // Check for spam
         $this->CheckForSpam('Comment'); // Comments and replies use the same spam check rules
      } else {
         $this->AddUpdateFields($FormPostValues);
      }
      
      // Validate the form posted values
      if ($this->Validation->Validate($FormPostValues, $Insert)) {
         $Fields = $this->Validation->SchemaValidationFields();
         $Fields = RemoveKeyFromArray($Fields, $this->PrimaryKey);
         $Session = Gdn::Session();
         // Make sure there are no reply drafts.
         if ($Insert === FALSE) {
            $this->SQL->Put($this->Name, $Fields, array('CommentID' => $CommentID));
         } else {
            $CommentID = $this->SQL->Insert($this->Name, $Fields);
            $this->UpdateReplyCount($ReplyCommentID);
            
            // Report user-comment activity
            $this->RecordActivity($ReplyCommentID, $Session->UserID, $CommentID);
         }
      }
      return $CommentID;
   }
   
   /**
    * Retrieves an object with the discussion id and name based on the
    * CommentID of the reply.
    *
    * @param int The CommentID of the reply for which we are fetching discussion info.
    */
   public function GetDiscussion($CommentID) {
      return $this->SQL
         ->Select('d.DiscussionID, d.Name')
         ->From('Discussion d')
         ->Join('Comment r', 'd.DiscussionID = r.DiscussionID')
         ->Where('r.CommentID', $CommentID)
         ->Get()
         ->FirstRow();
   }
   
   /**
    * Updates the CountReplies value on the comment & discussion based on the
    * CommentID of the comment being replied to.
    *
    * @param int The CommentID of the comment being replied to.
    */
   public function UpdateReplyCount($ReplyCommentID) {
      // Update CountReplies in the comment table
      $Info = $this->SQL
         ->Select('DiscussionID')
         ->Select('DiscussionID', 'count', 'CountReplies')
         ->From('Comment')
         ->Where('ReplyCommentID', $ReplyCommentID)
         ->GroupBy('DiscussionID')
         ->Get()
         ->FirstRow();
         
      $this->SQL
         ->Update('Comment')
         ->Set('CountReplies', $Info->CountReplies)
         ->Where('CommentID', $ReplyCommentID)
         ->Put();
      
      // Update CountReplies in the discussion table
      $Info = $this->SQL
         ->Select('DiscussionID')
         ->Select('DiscussionID', 'count', 'CountReplies')
         ->From('Comment')
         ->Where('DiscussionID', $Info->DiscussionID)
         ->GroupBy('DiscussionID')
         ->Get()
         ->FirstRow();
         
      $this->SQL
         ->Update('Discussion')
         ->Set('CountReplies', $Info->CountReplies)
         ->Where('DiscussionID', $Info->DiscussionID)
         ->Put();
   }
   
   public function RecordActivity($ReplyCommentID, $ActivityUserID, $CommentID) {
      // Get the author of the discussion
      $CommentModel = new CommentModel();
      $Comment = $CommentModel->GetID($ReplyCommentID);
      if ($ActivityUserID != $Comment->InsertUserID)
         AddActivity(
            $ActivityUserID,
            'CommentReply',
            '',
            $Comment->InsertUserID,
            'discussion/reply/'.$CommentID.'/#Comment_'.$ReplyCommentID
         );
   }   

}