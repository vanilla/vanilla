<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class Gdn_ConversationMessageModel extends Gdn_Model {
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
         ->Select('iup.Name', '', 'InsertPhoto')
         ->From('ConversationMessage cm')
         ->Join('Conversation c', 'cm.ConversationID = c.ConversationID')
         ->Join('UserConversation uc', 'c.ConversationID = uc.ConversationID and uc.UserID = '.$ViewingUserID)
         ->Join('User iu', 'cm.InsertUserID = iu.UserID')
         ->Join('Photo iup', 'iu.PhotoID = iup.PhotoID', 'left')
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
         $MessageID = $this->SQL->Insert($this->Name, $Fields);
         $ConversationID = ArrayValue('ConversationID', $Fields, 0);
         
         // Update the conversation's DateUpdated field
         $this->SQL
            ->Update('Conversation')
            ->Set('DateUpdated', Format::ToDateTime())
            ->Set('UpdateUserID', $Session->UserID)
            ->Where('ConversationID', $ConversationID)
            ->Put();
         
         // NOTE: INCREMENTING COUNTS INSTEAD OF GETTING ACTUAL COUNTS COULD
         // BECOME A PROBLEM. WATCH FOR IT.
         // Update the message counts for all users in the conversation
         $this->SQL
            ->Update('UserConversation')
            ->Set('CountMessages', 'CountMessages + 1', FALSE)
            ->Where('ConversationID', $ConversationID)
            ->Put();
            
         $this->SQL
            ->Update('UserConversation')
            ->Set('CountNewMessages', 'CountNewMessages + 1', FALSE)
            ->Where('ConversationID', $ConversationID)
            ->Where('UserID <>', $Session->UserID)
            ->Put();

         // Update the userconversation records to reflect the most recently
         // added message for all users other than the one that added the
         // message (otherwise they would see their name/message on the
         // conversation list instead of the person they are conversing with).
         $this->SQL
            ->Update('UserConversation')
            ->Set('LastMessageID', $MessageID)
            ->Where('ConversationID', $ConversationID)
            ->Where('UserID <>', $Session->UserID)
            ->Put();
            
         // Update the CountUnreadConversations count on each user related to the discussion.
         // And notify the users of the new message
         $UnreadData = $this->SQL
            ->Select('c.UserID')
            ->Select('c2.CountNewMessages', 'count', 'CountUnreadConversations')
            ->From('UserConversation c')
            ->Join('UserConversation c2', 'c.UserID = c2.UserID')
            ->Where('c2.CountNewMessages >', 0)
            ->Where('c.ConversationID', $ConversationID)
            ->Where('c.UserID <>', $Session->UserID)
            ->GroupBy('c.UserID')
            ->Get();
      
         $ActivityModel = new Gdn_ActivityModel();
         foreach ($UnreadData->Result() as $User) {
            // Update the CountUnreadConversations count on each user related to the discussion.
            $this->SQL
               ->Update('User')
               ->Set('CountUnreadConversations', $User->CountUnreadConversations)
               ->Where('UserID', $User->UserID)
               ->Put();
            
            // And notify the users of the new message
            $ActivityID = $ActivityModel->Add(
               $Session->UserID,
               'ConversationMessage',
               '',
               $User->UserID,
               '',
               '/messages/'.$ConversationID.'#'.$MessageID,
               FALSE
            );
            $Story = ArrayValue('Body', $Fields, '');
            $ActivityModel->SendNotification($ActivityID, $Story);
         }      
            
      }
      return $MessageID;
   }
}