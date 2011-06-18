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
 * Conversation Model
 *
 * @package Conversations
 */
 
/**
 * Manages conversations.
 *
 * @since 2.0.0
 * @package Conversations
 */
class ConversationModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('Conversation');
   }
   
   /**
    * Build generic part of conversation query.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ViewingUserID Unique ID of current user.
    */
   public function ConversationQuery($ViewingUserID, $Join = '') {
      $this->SQL
         ->Select('c.*')
         ->Select('lm.InsertUserID', '', 'LastMessageUserID')
         ->Select('lm.DateInserted', '', 'DateLastMessage')
         ->Select('lm.Body', '', 'LastMessage')
         ->Select('lm.Format')
         ->Select('lmu.Name', '', 'LastMessageName')
         ->Select('lmu.Photo', '', 'LastMessagePhoto')
         ->From('Conversation c');


      if ($ViewingUserID) {
         $this->SQL
            ->Select('c.CountMessages - uc.CountReadMessages', '', 'CountNewMessages')
            ->Select('uc.LastMessageID, uc.CountReadMessages, uc.DateLastViewed, uc.Bookmarked')
            ->Join('UserConversation uc', "c.ConversationID = uc.ConversationID and uc.UserID = $ViewingUserID")
            ->Join('ConversationMessage lm', 'uc.LastMessageID = lm.MessageID')
            ->Join('User lmu', 'lm.InsertUserID = lmu.UserID')
            ->Where('uc.Deleted', 0);
      } else {
         $this->SQL
            ->Select('0', '', 'CountNewMessages')
            ->Select('c.CountMessages', '', 'CountReadMessages')
            ->Select('lm.DateInserted', '', 'DateLastViewed')
            ->Select('0', '', 'Bookmarked')
            ->Join('ConversationMessage lm', 'c.LastMessageID = lm.MessageID')
            ->Join('User lmu', 'lm.InsertUserID = lmu.UserID');
      }
   }
   
   /**
    * Get list of conversations.
    * 
    * Events: BeforeGet.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ViewingUserID Unique ID of current user.
    * @param int $Offset Number to skip.
    * @param int $Limit Maximum to return.
    * @param array $Wheres SQL conditions.
    * @return Gdn_DataSet SQL results.
    */
   public function Get($ViewingUserID, $Offset = '0', $Limit = '', $Wheres = '') {
      if ($Limit == '') 
         $Limit = C('Conversations.Conversations.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      
      $this->ConversationQuery($ViewingUserID);
      
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
      
      $this->FireEvent('BeforeGet');
      
      return $this->SQL
         ->OrderBy('c.DateUpdated', 'desc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   /**
    * Get number of conversations involving current user.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ViewingUserID Unique ID of current user.
    * @param array $Wheres SQL conditions.
    * @return int Number of messages.
    */
   public function GetCount($ViewingUserID, $Wheres = '') {
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      return $this->SQL
         ->Select('uc.UserID', 'count', 'Count')
         ->From('UserConversation uc')
         ->Where('uc.UserID', $ViewingUserID)
         ->Get()
         ->Value('Count', 0);
   }
   
   /**
    * Get number of conversations that meet criteria.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $Wheres SQL conditions.
    * @return int Number of messages.
    */
   public function GetCountWhere($Wheres = '') {
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      $Data = $this->SQL
         ->Select('ConversationID', 'count', 'Count')
         ->From('Conversation')
         ->Get();

      if ($Data->NumRows() > 0)
         return $Data->FirstRow()->Count;
      
      return 0;
   }   
   
   /**
    * Get meta data of a single conversation.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation.
    * @param int $ViewingUserID Unique ID of current user.
    * @return Gdn_DataSet SQL result (single row).
    */
   public function GetID($ConversationID, $ViewingUserID = FALSE) {
      // Get the conversation.
      $Conversation = $this->GetWhere(array('ConversationID' => $ConversationID))->FirstRow(DATASET_TYPE_ARRAY);

      if ($ViewingUserID) {
         $Data = $this->SQL->GetWhere(
            'UserConversation',
            array('ConversationID' => $ConversationID, 'UserID' => $ViewingUserID))
            ->FirstRow(DATASET_TYPE_ARRAY);

         // Convert the array.
         $UserConversation = ArrayTranslate($Data, array('LastMessageID', 'CountReadMessages', 'DateLastViewed', 'Bookmarked'));
         $UserConversation['CountNewMessages'] = $Conversation['CountMessages'] - $Data['CountReadMessages'];
      } else {
         $UserConversation = array('CountNewMessages' => 0, 'CountReadMessages' => $Conversation['CountMessages'], 'DateLastViewed' => $Conversation['DateUpdated']);
      }
      $Conversation = array_merge($Conversation, $UserConversation);
      return (object)$Conversation;
   }
   
   /**
    * Get all users involved in conversation.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation.
    * @param int $IgnoreUserID Ignored.
    * @return Gdn_DataSet SQL results.
    */
   public function GetRecipients($ConversationID, $IgnoreUserID = '0') {
      return $this->SQL
         ->Select('uc.UserID, u.Name, uc.Deleted')
         ->Select('cm.DateInserted', 'max', 'DateLastActive')
         ->From('UserConversation uc')
         ->Join('User u', 'uc.UserID = u.UserID')
         ->Join('ConversationMessage cm', 'uc.ConversationID = cm.ConversationID and uc.UserID = cm.InsertUserID', 'left')
         ->Where('uc.ConversationID', $ConversationID)
         // ->Where('uc.UserID <>', $IgnoreUserID)
         ->GroupBy('uc.UserID, u.Name, uc.Deleted')
         ->Get();
   }
   
   public function JoinParticipants(&$Data) {
      $this->SQL
         ->From('UserConversation uc')
         ->Join('User u', 'u.UserID = uc.UserID');
      
      Gdn_DataSet::Join($Data, array('alias' => 'uc', 'parent' => 'ConversationID', 'column' => 'Participants', 'UserID', 'u.Name', 'u.Photo'), array('sql' => $this->SQL));
   }
   
   /**
    * Save conversation from form submission.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $FormPostValues Values submitted via form.
    * @param object $MessageModel Message starting the conversation.
    * @return int Unique ID of conversation created or updated.
    */
   public function Save($FormPostValues, $MessageModel) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      $MessageModel->DefineSchema();

      if (!GetValue('RecipientUserIDs', $FormPostValues) && isset($FormPostValues['To'])) {
            $To = explode(',', $FormPostValues['To']);
            $To = array_map('trim', $To);

            $RecipientUserIDs = $this->SQL
               ->Select('UserID')
               ->From('User')
               ->WhereIn('Name', $To)
               ->Get();
            $RecipientUserIDs = ConsolidateArrayValuesByKey($RecipientUserIDs, 'UserID');
            $FormPostValues['RecipientUserID'] = $RecipientUserIDs;
         }
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $MessageModel->Validation->ApplyRule('Body', 'Required');     
      // Make sure that there is at least one recipient
      $this->Validation->AddRule('OneOrMoreArrayItemRequired', 'function:ValidateOneOrMoreArrayItemRequired');
      $this->Validation->ApplyRule('RecipientUserID', 'OneOrMoreArrayItemRequired');
      
      // Add insert/update fields
      $this->AddInsertFields($FormPostValues);
      $this->AddUpdateFields($FormPostValues);
      
      // Validate the form posted values
      $ConversationID = FALSE;
      if (
         $this->Validate($FormPostValues)
         && $MessageModel->Validate($FormPostValues)
      ) {
         $Fields = $this->Validation->ValidationFields(); // All fields on the form that relate to the schema
         

         // Define the recipients, and make sure that the sender is in the list
         $RecipientUserIDs = GetValue('RecipientUserID', $Fields, 0);

         if (!in_array($Session->UserID, $RecipientUserIDs))
            $RecipientUserIDs[] = $Session->UserID;
            
         // Also make sure there are no duplicates in the recipient list
         $RecipientUserIDs = array_unique($RecipientUserIDs);
         sort($RecipientUserIDs);
         $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
         $Fields['Contributors'] = Gdn_Format::Serialize($RecipientUserIDs);
         $ConversationID = $this->SQL->Insert($this->Name, $Fields);
         $FormPostValues['ConversationID'] = $ConversationID;
         $MessageID = $MessageModel->Save($FormPostValues);

         $this->SQL
            ->Update('Conversation')
            ->Set('FirstMessageID', $MessageID)
            ->Where('ConversationID', $ConversationID)
            ->Put();
            
         // Now that the message & conversation have been inserted, insert all of the recipients
         foreach ($RecipientUserIDs as $UserID) {
            $CountReadMessages = $UserID == $Session->UserID ? 1 : 0;
            $this->SQL->Insert('UserConversation', array(
               'UserID' => $UserID,
               'ConversationID' => $ConversationID,
               'LastMessageID' => $MessageID,
               'CountReadMessages' => $CountReadMessages
            ));
         }
         
         // And update the CountUnreadConversations count on each user related to the discussion.
         $this->SQL
            ->Update('User')
            ->Set('CountUnreadConversations', 'coalesce(CountUnreadConversations, 0) + 1', FALSE)
            ->WhereIn('UserID', $RecipientUserIDs)
            ->Where('UserID <>', $Session->UserID)
            ->Put();

         // Add notifications (this isn't done by the conversationmessagemodule
         // because the conversation has not yet been created at the time they are
         // inserted)
         $UnreadData = $this->SQL
            ->Select('uc.UserID')
            ->From('UserConversation uc')
            ->Where('uc.ConversationID', $ConversationID) // hopefully coax this index.
            ->Where('uc.UserID <>', $Session->UserID)
            ->Get();
   
         $ActivityModel = new ActivityModel();
         foreach ($UnreadData->Result() as $User) {
            // Notify the users of the new message.
            $ActivityID = $ActivityModel->Add(
               $Session->UserID,
               'ConversationMessage',
               '',
               $User->UserID,
               '',
               "/messages/$ConversationID#$MessageID",
               FALSE
            );
            $Story = ArrayValue('Body', $FormPostValues, '');
            $ActivityModel->SendNotification($ActivityID, $Story);
         }

      } else {
         // Make sure that all of the validation results from both validations are present for view by the form
         foreach ($MessageModel->ValidationResults() as $FieldName => $Results) {
            foreach ($Results as $Result) {
               $this->Validation->AddValidationResult($FieldName, $Result);
            }
         }
      }
      
      return $ConversationID;
   }
   
   /**
    * Clear a conversation for a specific user id.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation effected.
    * @param int $ClearingUserID Unique ID of current user.
    */
   public function Clear($ConversationID, $ClearingUserID) {
      $this->SQL->Update('UserConversation')
         ->Set('Deleted', 1)
         ->Set('DateLastViewed', Gdn_Format::ToDateTime())
         ->Where('UserID', $ClearingUserID)
         ->Where('ConversationID', $ConversationID)
         ->Put();

      $this->CountUnread($ClearingUserID);
   }

   public function CountUnread($UserID, $Save = TRUE) {
      // Also update the unread conversation count for this user
      $CountUnread = $this->SQL
         ->Select('c.ConversationID', 'count', 'CountUnread')
         ->From('UserConversation uc')
         ->Join('Conversation c', 'c.ConversationID = uc.ConversationID and uc.CountReadMessages < c.CountMessages')
         ->Where('uc.UserID', $UserID)
         ->Where('uc.Deleted', 0)
         ->Get()->Value('CountUnread', 0);

      if ($Save) {
         $this->SQL
            ->Update('User')
            ->Set('CountUnreadConversations', $CountUnread)
            ->Where('UserID', $UserID)
            ->Put();
      }

      return $CountUnread;
   }

   /**
    * Update a conversation as read for a specific user id.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation effected.
    * @param int $ReadingUserID Unique ID of current user.
    */
   public function MarkRead($ConversationID, $ReadingUserID) {
      // Update the the read conversation count for the user.
      $this->SQL->Update('UserConversation uc')
         ->Join('Conversation c', 'c.ConversationID = uc.ConversationID')
         ->Set('uc.CountReadMessages', 'c.CountMessages', FALSE)
         ->Set('uc.DateLastViewed', Gdn_Format::ToDateTime())
         ->Set('uc.LastMessageID', 'c.LastMessageID', FALSE)
         ->Where('c.ConversationID', $ConversationID)
         ->Where('uc.ConversationID', $ConversationID)
         ->Where('uc.UserID', $ReadingUserID)
         ->Put();
         
      // Also update the unread conversation count for this user
      $CountUnread = $this->CountUnread($ReadingUserID);
         
      // Also write through to the current session user.
      if($ReadingUserID > 0 && $ReadingUserID == Gdn::Session()->UserID)
         Gdn::Session()->User->CountUnreadConversations = $CountUnread;
   }
   
   /**
    * Bookmark (or unbookmark) a conversation for a specific user id.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation effected.
    * @param int $UserID Unique ID of current user.
    * @return bool Whether it is currently bookmarked.
    */
   public function Bookmark($ConversationID, $UserID) {
      $Bookmark = FALSE;
      $Discussion = $this->GetID($ConversationID, $UserID);
      if (is_object($Discussion)) {
         $Bookmark = $Discussion->Bookmark == '0' ? '1' : '0';
         $this->SQL->Update('UserConversation')
            ->Set('Bookmark', $Bookmark)
            ->Where('ConversationID', $ConversationID)
            ->Where('UserID', $UserID)
            ->Put();
         $Bookmark == '1' ? TRUE : FALSE;
      }
      return $Bookmark;
   }
   
   /**
    * Add another user to the conversation.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation effected.
    * @param int $UserID Unique ID of current user.
    */
   public function AddUserToConversation($ConversationID, $UserID) {
      if (!is_array($UserID))
         $UserID = array($UserID);
         
      // First define the current users in the conversation
      $OldContributorData = $this->GetRecipients($ConversationID);
      $OldContributorData = Gdn_DataSet::Index($OldContributorData, 'UserID');
      $AddedUserIDs = array();
      
      // Get some information about this conversation
      $ConversationData = $this->SQL
         ->Select('LastMessageID')
         ->Select('CountMessages')
         ->From('Conversation')
         ->Where('ConversationID', $ConversationID)
         ->Get()
         ->FirstRow();
      
      // Add the user(s) if they are not already in the conversation
      foreach ($UserID as $NewUserID) {
         if (!array_key_exists($NewUserID, $OldContributorData)) {
            $AddedUserIDs[] = $NewUserID;
            $this->SQL->Insert('UserConversation', array(
               'UserID' => $NewUserID,
               'ConversationID' => $ConversationID,
               'LastMessageID' => $ConversationData->LastMessageID,
               'CountReadMessages' => 0
            ));
         } elseif ($OldContributorData[$NewUserID]->Deleted) {
            $AddedUserIDs[] = $NewUserID;
            
            $this->SQL->Put('UserConversation',
               array('Deleted' => 0),
               array('ConversationID' => $ConversationID, 'UserID' => $NewUserID));
         }
      }
      if (count($AddedUserIDs) > 0) {
         $Session = Gdn::Session();
         
         // Update the Contributors field on the conversation
         $Contributors = array_unique(array_merge($AddedUserIDs, array_keys($OldContributorData)));
         sort($Contributors);
         $this->SQL
            ->Update('Conversation')
            ->Set('Contributors', Gdn_Format::Serialize($Contributors))
            ->Where('ConversationID', $ConversationID)
            ->Put();
         
         // NOTIFY ALL NEWLY ADDED USERS THAT THEY WERE ADDED TO THE CONVERSATION
         foreach ($AddedUserIDs as $AddedUserID) {
            AddActivity(
               $Session->UserID,
               'AddedToConversation',
               '',
               $AddedUserID,
               '/messages/'.$ConversationID
            );
         }
         
         // Update the unread conversation count for each affected user
         $this->SQL
            ->Update('User')
            ->Set('CountUnreadConversations', 'coalesce(CountUnreadConversations, 0) + 1', FALSE)
            ->WhereIn('UserID', $AddedUserIDs)
            ->Put();
      }
   }
}
