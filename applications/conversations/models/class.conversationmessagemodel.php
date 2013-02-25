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
 * Message Model
 *
 * @package Conversations
 */
 
/**
 * Manages messages in a conversation.
 *
 * @since 2.0.0
 * @package Conversations
 */
class ConversationMessageModel extends Gdn_Model {
   /**
    * Class constructor. Defines the related database table name.
    * 
    * @since 2.0.0
    * @access public
    */
   public function __construct() {
      parent::__construct('ConversationMessage');
      $this->PrimaryKey = 'MessageID';
   }
   
   /**
    * Get messages by conversation.
    * 
    * Events: BeforeGet.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation being viewed.
    * @param int $ViewingUserID Unique ID of current user.
    * @param int $Offset Number to skip.
    * @param int $Limit Maximum to return.
    * @param array $Wheres SQL conditions.
    * @return Gdn_DataSet SQL results.
    */
   public function Get($ConversationID, $ViewingUserID, $Offset = '0', $Limit = '', $Wheres = '') {
      if ($Limit == '') 
         $Limit = Gdn::Config('Conversations.Messages.PerPage', 50);

      $Offset = !is_numeric($Offset) || $Offset < 0 ? 0 : $Offset;
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      $this->FireEvent('BeforeGet');
      return $this->SQL
         ->Select('cm.*')
         ->Select('iu.Name', '', 'InsertName')
         ->Select('iu.Email', '', 'InsertEmail')
         ->Select('iu.Photo', '', 'InsertPhoto')
         ->From('ConversationMessage cm')
         ->Join('Conversation c', 'cm.ConversationID = c.ConversationID')
         ->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID, 'left')
         ->Join('User iu', 'cm.InsertUserID = iu.UserID', 'left')
         ->BeginWhereGroup()
         ->Where('uc.DateCleared is null') 
         ->OrWhere('uc.DateCleared <', 'cm.DateInserted', TRUE, FALSE) // Make sure that cleared conversations do not show up unless they have new messages added.
         ->EndWhereGroup()
         ->Where('cm.ConversationID', $ConversationID)
         ->OrderBy('cm.DateInserted', 'asc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   /**
    * Get the data from the model based on its primary key.
    *
    * @param mixed $ID The value of the primary key in the database.
    * @param string $DatasetType The format of the result dataset.
    * @return Gdn_DataSet
    */
   public function GetID($ID, $DatasetType = FALSE) {
      $Result = $this->GetWhere(array("MessageID" => $ID))->FirstRow($DatasetType);
      return $Result;
   }
   
   /**
    * Get only new messages from conversation.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation being viewed.
    * @param int $LastMessageID Unique ID of last message to be viewed.
    * @return Gdn_DataSet SQL results.
    */
   public function GetNew($ConversationID, $LastMessageID) {
      $Session = Gdn::Session();
      $this->SQL->Where('MessageID > ', $LastMessageID);
      return $this->Get($ConversationID, $Session->UserID);
   }
   
   /**
    * Get number of messages in a conversation.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation being viewed.
    * @param int $ViewingUserID Unique ID of current user.
    * @param array $Wheres SQL conditions.
    * @return int Number of messages.
    */
   public function GetCount($ConversationID, $ViewingUserID, $Wheres = '') {
      if (is_array($Wheres))
         $this->SQL->Where($Wheres);
         
      $Data = $this->SQL
         ->Select('cm.MessageID', 'count', 'Count')
         ->From('ConversationMessage cm')
         ->Join('Conversation c', 'cm.ConversationID = c.ConversationID')
         ->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID)
         ->BeginWhereGroup()
         ->Where('uc.DateCleared is null') 
         ->OrWhere('uc.DateCleared >', 'c.DateUpdated', TRUE, FALSE) // Make sure that cleared conversations do not show up unless they have new messages added.
         ->EndWhereGroup()
         ->GroupBy('cm.ConversationID')
         ->Where('cm.ConversationID', $ConversationID)
         ->Get();
         
      if ($Data->NumRows() > 0)
         return $Data->FirstRow()->Count;
      
      return 0;
   }

   /**
    * Get number of messages that meet criteria.
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
         ->Select('MessageID', 'count', 'Count')
         ->From('ConversationMessage')
         ->Get();
         
      if ($Data->NumRows() > 0)
         return $Data->FirstRow()->Count;
      
      return 0;
   }
   
   /**
    * Save message from form submission.
    * 
    * @since 2.0.0
    * @access public
    *
    * @param array $FormPostValues Values submitted via form.
    * @return int Unique ID of message created or updated.
    */
   public function Save($FormPostValues, $Conversation = NULL) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $this->AddInsertFields($FormPostValues);
      
      $this->EventArguments['FormPostValues'] = $FormPostValues;
      $this->FireEvent('BeforeSaveValidation');
      
      // Validate the form posted values
      $MessageID = FALSE;
      if($this->Validate($FormPostValues)) {
         $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
         TouchValue('Format', $Fields, C('Garden.InputFormatter', 'Html'));
         
         $this->EventArguments['Fields'] = $Fields;
         $this->FireEvent('BeforeSave');
         
         $MessageID = $this->SQL->Insert($this->Name, $Fields);
         $this->LastMessageID = $MessageID;
         $ConversationID = ArrayValue('ConversationID', $Fields, 0);

         if (!$Conversation)
            $Conversation = $this->SQL->GetWhere('Conversation', array('ConversationID' => $ConversationID))->FirstRow(DATASET_TYPE_ARRAY);

         $Message = $this->GetID($MessageID);
         $this->EventArguments['Conversation'] = $Conversation;
         $this->EventArguments['Message'] = $Message;
         $this->FireEvent('AfterSave');
         
         // Get the new message count for the conversation.
         $SQLR = $this->SQL
            ->Select('MessageID', 'count', 'CountMessages')
            ->Select('MessageID', 'max', 'LastMessageID')
            ->From('ConversationMessage')
            ->Where('ConversationID', $ConversationID)
            ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         if (sizeof($SQLR)) {
            list($CountMessages, $LastMessageID) = array_values($SQLR);
         } else { return; }
         
         // Update the conversation's DateUpdated field.
         $DateUpdated = Gdn_Format::ToDateTime();
         
         $this->SQL
            ->Update('Conversation c')
            ->Set('CountMessages', $CountMessages)
            ->Set('LastMessageID', $LastMessageID)
            ->Set('UpdateUserID', Gdn::Session()->UserID)
            ->Set('DateUpdated', $DateUpdated)
            ->Where('ConversationID', $ConversationID)
            ->Put();

         // Update the last message of the users that were previously up-to-date on their read messages.
         $this->SQL
            ->Update('UserConversation uc')
            ->Set('uc.LastMessageID', $MessageID)
            ->Set('uc.DateConversationUpdated', $DateUpdated)
            ->Where('uc.ConversationID', $ConversationID)
            ->Where('uc.Deleted', '0')
            ->Where('uc.CountReadMessages', $CountMessages - 1)
            ->Where('uc.UserID <>', $Session->UserID)
            ->Put();
         
         // Update the date updated of the users that were not up-to-date.
         $this->SQL
            ->Update('UserConversation uc')
            ->Set('uc.DateConversationUpdated', $DateUpdated)
            ->Where('uc.ConversationID', $ConversationID)
            ->Where('uc.Deleted', '0')
            ->Where('uc.CountReadMessages <>', $CountMessages - 1)
            ->Where('uc.UserID <>', $Session->UserID)
            ->Put();

         // Update the sending user.
         $this->SQL
            ->Update('UserConversation uc')
            ->Set('uc.CountReadMessages', $CountMessages)
            ->Set('Deleted', 0)
            ->Set('uc.DateConversationUpdated', $DateUpdated)
            ->Where('ConversationID', $ConversationID)
            ->Where('UserID', $Session->UserID)
            ->Put();

         // Find users involved in this conversation
         $UserData = $this->SQL
            ->Select('UserID')
            ->Select('LastMessageID')
            ->Select('Deleted')
            ->From('UserConversation')
            ->Where('ConversationID', $ConversationID)
            ->Get()->Result(DATASET_TYPE_ARRAY);
         
         $UpdateCountUserIDs = array();
         $NotifyUserIDs = array();
         
         // Collapse for call to UpdateUserCache and ActivityModel.
         $InsertUserFound = FALSE;
         foreach ($UserData as $UpdateUser) {
            $LastMessageID = GetValue('LastMessageID', $UpdateUser);
            $UserID = GetValue('UserID', $UpdateUser);
            $Deleted = GetValue('Deleted', $UpdateUser);
            
            if ($UserID == GetValue('InsertUserID', $Fields)) {
               $InsertUserFound = TRUE;
               if ($Deleted) {
                  $this->SQL->Put('UserConversation', array('Deleted' => 0, 'DateConversationUpdated' => $DateUpdated), array('ConversationID' => $ConversationID, 'UserID' => $UserID));
               }
            }
            
            // Update unread for users that were up to date
            if ($LastMessageID == $MessageID)
               $UpdateCountUserIDs[] = $UserID;
            
            // Send activities to users that have not deleted the conversation
            if (!$Deleted)
               $NotifyUserIDs[] = $UserID;
         }
         
         if (!$InsertUserFound) {
            $UserConversation = array(
               'UserID' => GetValue('InsertUserID', $Fields),
               'ConversationID' => $ConversationID,
               'LastMessageID' => $LastMessageID,
               'CountReadMessages' => $CountMessages,
               'DateConversationUpdated' => $DateUpdated);
            $this->SQL->Insert('UserConversation', $UserConversation);
         }
         
         if (sizeof($UpdateCountUserIDs)) {
            $ConversationModel = new ConversationModel();
            $ConversationModel->UpdateUserUnreadCount($UpdateCountUserIDs, TRUE);
         }
         
         $this->FireEvent('AfterAdd');

         $ActivityModel = new ActivityModel();
         foreach ($NotifyUserIDs as $NotifyUserID) {
            if ($Session->UserID == $NotifyUserID)
               continue; // don't notify self.

            // Notify the users of the new message.
            $ActivityID = $ActivityModel->Add(
               $Session->UserID,
               'ConversationMessage',
               '',
               $NotifyUserID,
               '',
               "/messages/{$ConversationID}#{$MessageID}",
               FALSE
            );
            $Story = GetValue('Body', $Fields, '');
            
            if (C('Conversations.Subjects.Visible')) {
               $Story = ConcatSep("\n\n", GetValue('Subject', $Conversation, ''), $Story);
            }
            $ActivityModel->SendNotification($ActivityID, $Story);
         }
      }
      return $MessageID;
   }
}