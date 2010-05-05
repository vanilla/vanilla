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
 * Renders a form that allows people to be added to conversations.
 */
class AddPeopleModule extends Gdn_Module {

   public $Conversation;
   public $Form;

   public function __construct(&$Sender = '') {
      $Session = Gdn::Session();
      if (property_exists($Sender, 'Conversation'))
         $this->Conversation = $Sender->Conversation;
         
      $this->Form = Gdn::Factory('Form', 'AddPeople');
      // $this->Form->Action = $Sender->SelfUrl;
      // If the form was posted back, check for people to add to the conversation
      if ($this->Form->AuthenticatedPostBack()) {
         $NewRecipientUserIDs = array();
         $NewRecipients = explode(',', $this->Form->GetFormValue('AddPeople', ''));
         $UserModel = Gdn::Factory("UserModel");
         foreach ($NewRecipients as $Name) {
            if (trim($Name) != '') {
               $User = $UserModel->GetByUsername(trim($Name));
               if (is_object($User))
                  $NewRecipientUserIDs[] = $User->UserID;
            }
         }
         $Sender->ConversationModel->AddUserToConversation($this->Conversation->ConversationID, $NewRecipientUserIDs);
         // if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
         //    Redirect('/messages/'.$this->Conversation->ConversationID);
            
         $Sender->StatusMessage = T('Your changes were saved.');
         $Sender->RedirectUrl = Url('/messages/'.$this->Conversation->ConversationID);
      }
      $this->_ApplicationFolder = $Sender->Application;
      $this->_ThemeFolder = $Sender->Theme;
   }   
   
   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (is_object($this->Conversation) && $this->Conversation->ConversationID > 0)
         return parent::ToString();

      return '';
   }
}