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
   
   public function Setup() {
      return TRUE;
   }
}