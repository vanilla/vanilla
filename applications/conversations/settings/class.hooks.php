<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/
class ConversationsHooks implements Gdn_IPlugin {
   
   public function UserModel_SessionQuery_Handler($Sender) {
      // Add some extra fields to the session query
      //$Sender->SQL->Select('u.CountUnreadConversations');
   }
   
   // Remove data when deleting a user
   public function UserModel_BeforeDeleteUser_Handler($Sender) {
      $UserID = GetValue('UserID', $Sender->EventArguments);
      $Options = GetValue('Options', $Sender->EventArguments, array());
      $Options = is_array($Options) ? $Options : array();
      
      $DeleteMethod = GetValue('DeleteMethod', $Options, 'delete');
      if ($DeleteMethod == 'delete') {
         $Sender->SQL->Delete('Conversation', array('InsertUserID' => $UserID));
         $Sender->SQL->Delete('Conversation', array('UpdateUserID' => $UserID));
         $Sender->SQL->Delete('UserConversation', array('UserID' => $UserID));
         $Sender->SQL->Delete('ConversationMessage', array('InsertUserID' => $UserID));
      } else if ($DeleteMethod == 'wipe') {
         $Sender->SQL->Update('ConversationMessage')
            ->Set('Body', T('The user and all related content has been deleted.'))
            ->Set('Format', 'Deleted')
            ->Where('InsertUserID', $UserID)
            ->Put();
      } else {
         // Leave conversation messages
      }
      // Remove the user's profile information related to this application
      $Sender->SQL->Update('User')
         ->Set('CountUnreadConversations', 0)
         ->Where('UserID', $UserID)
         ->Put();
   }
   
   public function ProfileController_AfterAddSideMenu_Handler(&$Sender) {
      // Add a "send X a message" link to the side menu on the profile page
      $Session = Gdn::Session();
      if ($Session->IsValid() && $Session->UserID != $Sender->User->UserID) {
         $SideMenu = $Sender->EventArguments['SideMenu'];
         $SideMenu->AddLink('Options', sprintf(T('Send %s a Message'), $Sender->User->Name), '/messages/add/'.$Sender->User->Name);
         $Sender->EventArguments['SideMenu'] = $SideMenu;
      }
   }
   
   public function ProfileController_AfterPreferencesDefined_Handler(&$Sender) {
      $Sender->Preferences['Email Notifications']['Email.ConversationMessage'] = T('Notify me of private messages.');
      $Sender->Preferences['Email Notifications']['Email.AddedToConversation'] = T('Notify me when I am added to private conversations.');
   }
   
   public function Base_Render_Before(&$Sender) {
      // Add the menu options for conversations
      $Session = Gdn::Session();
      if ($Sender->Menu && $Session->IsValid()) {
         $Inbox = T('Inbox');
         $CountUnreadConversations = $Session->User->CountUnreadConversations;
         if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
            $Inbox .= ' <span>'.$CountUnreadConversations.'</span>';
            
         $Sender->Menu->AddLink('Conversations', $Inbox, '/messages/all', FALSE, array('Standard' => TRUE));
         // $Sender->Menu->AddLink('Conversations', T('New Conversation'), '/messages/add', FALSE);
      }
   }
   
   // Load some information into the BuzzData collection
   public function SettingsController_DashboardData_Handler(&$Sender) {
      $ConversationModel = new ConversationModel();
      // Number of Conversations
      $CountConversations = $ConversationModel->GetCountWhere();
      $Sender->AddDefinition('CountConversations', $CountConversations);
      $Sender->BuzzData[T('Conversations')] = number_format($CountConversations);
      // Number of New Conversations in the last day
      $Sender->BuzzData[T('New conversations in the last day')] = number_format($ConversationModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Conversations in the last week
      $Sender->BuzzData[T('New conversations in the last week')] = number_format($ConversationModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 week')))));

      $ConversationMessageModel = new ConversationMessageModel();
      // Number of Messages
      $CountMessages = $ConversationMessageModel->GetCountWhere();
      $Sender->AddDefinition('CountConversationMessages', $CountMessages);
      $Sender->BuzzData[T('Conversation Messages')] = number_format($CountMessages);
      // Number of New Messages in the last day
      $Sender->BuzzData[T('New messages in the last day')] = number_format($ConversationMessageModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 day')))));
      // Number of New Messages in the last week
      $Sender->BuzzData[T('New messages in the last week')] = number_format($ConversationMessageModel->GetCountWhere(array('DateInserted >=' => Gdn_Format::ToDateTime(strtotime('-1 week')))));
   }   
   
   public function Setup() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = C('Conversations.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Validation = new Gdn_Validation(); // This is going to be needed by structure.php to validate permission names
      include(PATH_APPLICATIONS . DS . 'conversations' . DS . 'settings' . DS . 'structure.php');

      $ApplicationInfo = array();
      include(CombinePaths(array(PATH_APPLICATIONS . DS . 'conversations' . DS . 'settings' . DS . 'about.php')));
      $Version = ArrayValue('Version', ArrayValue('Conversations', $ApplicationInfo, array()), 'Undefined');
      SaveToConfig('Conversations.Version', $Version);
   }
}