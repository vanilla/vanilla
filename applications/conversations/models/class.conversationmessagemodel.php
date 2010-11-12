<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ConversationMessageModel extends Gdn_Model {
   /**
    * Class constructor.
    */
   public function __construct() {
      parent::__construct('ConversationMessage');
   }
   
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
         ->Select('iu.Photo', '', 'InsertPhoto')
         ->From('ConversationMessage cm')
         ->Join('Conversation c', 'cm.ConversationID = c.ConversationID')
         ->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID)
         ->Join('User iu', 'cm.InsertUserID = iu.UserID')
         ->BeginWhereGroup()
         ->Where('uc.DateCleared is null') 
         ->OrWhere('uc.DateCleared <', 'cm.DateInserted', TRUE, FALSE) // Make sure that cleared conversations do not show up unless they have new messages added.
         ->EndWhereGroup()
         ->Where('cm.ConversationID', $ConversationID)
         ->OrderBy('cm.DateInserted', 'asc')
         ->Limit($Limit, $Offset)
         ->Get();
   }
   
   public function GetNew($ConversationID, $LastMessageID) {
      $Session = Gdn::Session();
      $this->SQL->Where('MessageID > ', $LastMessageID);
      return $this->Get($ConversationID, $Session->UserID);
   }
   
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
   
   public function Save($FormPostValues) {
      $Session = Gdn::Session();
      
      // Define the primary key in this model's table.
      $this->DefineSchema();
      
      // Add & apply any extra validation rules:      
      $this->Validation->ApplyRule('Body', 'Required');
      $this->AddInsertFields($FormPostValues);
      
      // Validate the form posted values
      $MessageID = FALSE;
      if($this->Validate($FormPostValues)) {
         $Fields = $this->Validation->SchemaValidationFields(); // All fields on the form that relate to the schema
         $Fields['Format'] = C('Conversations.Message.Format','Ham');
         
         $MessageID = $this->SQL->Insert($this->Name, $Fields);
         $ConversationID = ArrayValue('ConversationID', $Fields, 0);
         $Px = $this->SQL->Database->DatabasePrefix;

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
         
         // Update the conversation's DateUpdated field
         $this->SQL
            ->Update('Conversation c')
            ->History()
            ->Set('CountMessages', $CountMessages)
            ->Set('LastMessageID', $LastMessageID)
            ->Where('ConversationID', $ConversationID)
            ->Put();

         // Update the last message of the users that were previously up-to-date on their read messages.
         $this->SQL
            ->Update('UserConversation uc')
            ->Set('uc.LastMessageID', $MessageID)
            ->Set('uc.CountReadMessages', "case uc.UserID when {$Session->UserID} then $CountMessages else uc.CountReadMessages end", FALSE)
            ->Where('uc.ConversationID', $ConversationID)
            ->Where('uc.Deleted', '0')
            ->Where('uc.CountReadMessages', $CountMessages - 1)
            ->Put();

         // Incrememnt the users' inbox counts.
         $this->SQL
            ->Update('User u')
            ->Join('UserConversation uc', 'u.UserID = uc.UserID')
            ->Set('u.CountUnreadConversations', 'coalesce(u.CountUnreadConversations, 0) + 1', FALSE)
            ->Where('uc.ConversationID', $ConversationID)
            ->Where('uc.LastMessageID', $MessageID)
            ->Where('uc.UserID <>', $Session->UserID)
            ->Put();

         // Grab the users that need to be notified.
         $UnreadData = $this->SQL
            ->Select('uc.UserID')
            ->From('UserConversation uc')
            ->Where('uc.ConversationID', $ConversationID) // hopefully coax this index.
            // ->Where('uc.LastMessageID', $MessageID)
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
            $Story = ArrayValue('Body', $Fields, '');
            $ActivityModel->SendNotification($ActivityID, $Story);
         }
      }
      return $MessageID;
   }
}