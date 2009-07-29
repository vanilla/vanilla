<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/
class ConversationsHooks implements Gdn_IPlugin {
   
   public function Gdn_UserModel_SessionQuery_Handler(&$Sender) {
      // Add some extra fields to the session query
      $Sender->SQL->Select('u.CountUnreadConversations');
   }   
   
   public function Base_Render_Before(&$Sender) {
      // Add the menu options for conversations
      $Session = Gdn::Session();
      if ($Sender->Menu && $Session->IsValid()) {
         $Inbox = Gdn::Translate('Inbox');
         $CountUnreadConversations = $Session->User->CountUnreadConversations;
         if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
            $Inbox .= '<span>'.$CountUnreadConversations.'</span>';
            
         $Sender->Menu->AddLink('Conversations', $Inbox, '/messages/all', FALSE);
         $Sender->Menu->AddLink('Conversations', 'New Conversation', '/messages/add', FALSE);
      }
   }
   
   // Load some information into the BuzzData collection
   public function SettingsController_DashboardData_Handler(&$Sender) {
      $ConversationModel = new Gdn_ConversationModel();
      // Number of Conversations
      $CountConversations = $ConversationModel->GetCountWhere();
      $Sender->AddDefinition('CountConversations', $CountConversations);
      $Sender->BuzzData[Gdn::Translate('Conversations')] = number_format($CountConversations);
      // Number of New Conversations in the last day
      $Sender->BuzzData[Translate('New conversations in the last day')] = number_format($ConversationModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Conversations in the last week
      $Sender->BuzzData[Translate('New conversations in the last week')] = number_format($ConversationModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 week')))));

      $ConversationMessageModel = new Gdn_ConversationMessageModel();
      // Number of Messages
      $CountMessages = $ConversationMessageModel->GetCountWhere();
      $Sender->AddDefinition('CountConversationMessages', $CountMessages);
      $Sender->BuzzData[Gdn::Translate('Conversation Messages')] = number_format($CountMessages);
      // Number of New Messages in the last day
      $Sender->BuzzData[Translate('New messages in the last day')] = number_format($ConversationMessageModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Messages in the last week
      $Sender->BuzzData[Translate('New messages in the last week')] = number_format($ConversationMessageModel->GetCountWhere(array('DateInserted >=' => Format::ToDateTime(strtotime('-1 week')))));
   }   
   
   public function Setup() {
      return TRUE;
   }
}