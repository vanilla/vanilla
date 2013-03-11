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
 * Conversations Hooks
 *
 * @package Conversations
 */
 
/**
 * Handles hooks into Dashboard and Vanilla.
 *
 * @since 2.0.0
 * @package Conversations
 */
class ConversationsHooks implements Gdn_IPlugin {
   
   /**
    *
    * @param DbaController $Sender 
    */
   public function DbaController_CountJobs_Handler($Sender) {
      $Counts = array(
          'Conversation' => array('CountMessages', 'FirstMessageID', 'LastMessageID', 'DateUpdated', 'UpdateUserID')
//          'Category' => array('CountDiscussions', 'CountComments', 'LastDiscussionID', 'LastCommentID')
      );
      
      foreach ($Counts as $Table => $Columns) {
         foreach ($Columns as $Column) {
            $Name = "Recalculate $Table.$Column";
            $Url = "/dba/counts.json?".http_build_query(array('table' => $Table, 'column' => $Column));
            
            $Sender->Data['Jobs'][$Name] = $Url;
         }
      }
   }
   
   public function UserModel_SessionQuery_Handler($Sender) {
      // Add some extra fields to the session query
      //$Sender->SQL->Select('u.CountUnreadConversations');
   }
   
   /**
    * Remove data when deleting a user.
    *
    * @since 2.0.0
    * @access public
    */
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
   
   /**
    * Add 'Inbox' to profile menu.
    *
    * @since 2.0.0
    * @access public
    */
   public function ProfileController_AddProfileTabs_Handler($Sender) {
      if (Gdn::Session()->IsValid()) {
         $Inbox = T('Inbox');
         $InboxHtml = Sprite('SpInbox').' '.$Inbox;
         $InboxLink = '/messages/all';
         
         if (Gdn::Session()->UserID != $Sender->User->UserID) {
            // Accomodate admin access
            if (C('Conversations.Moderation.Allow', FALSE) && Gdn::Session()->CheckPermission('Conversations.Moderation.Manage')) {
               $CountUnread = $Sender->User->CountUnreadConversations;
               $InboxLink .= "?userid={$Sender->User->UserID}";
            } else {
               return;
            }
         } else {
            // Current user
            $CountUnread = Gdn::Session()->User->CountUnreadConversations;
         }
         
         if (is_numeric($CountUnread) && $CountUnread > 0)
            $InboxHtml .= ' <span class="Aside"><span class="Count">'.$CountUnread.'</span></span>';
         $Sender->AddProfileTab($Inbox, $InboxLink, 'Inbox', $InboxHtml);
      }
   }
   
   /**
    * Add "Message" option to profile options.
    */
   public function ProfileController_BeforeProfileOptions_Handler($Sender, $Args) {
      if (!$Sender->EditMode && Gdn::Session()->IsValid() && Gdn::Session()->UserID != $Sender->User->UserID)
         $Sender->EventArguments['MemberOptions'][] = array(
            'Text' => Sprite('SpMessage').' '.T('Message'),
            'Url' => '/messages/add/'.$Sender->User->Name,
            'CssClass' => 'MessageUser'
         );
   }   
   
   
   /**
    * Additional options for the Preferences screen.
    *
    * @since 2.0.0
    * @access public
    */
   public function ProfileController_AfterPreferencesDefined_Handler($Sender) {
      $Sender->Preferences['Notifications']['Email.ConversationMessage'] = T('Notify me of private messages.');
      $Sender->Preferences['Notifications']['Popup.ConversationMessage'] = T('Notify me of private messages.');
   }
   
   /**
    * Add 'Inbox' to global menu.
    *
    * @since 2.0.0
    * @access public
    */
   public function Base_Render_Before($Sender) {
      // Add the menu options for conversations
      if ($Sender->Menu && Gdn::Session()->IsValid()) {
         $Inbox = T('Inbox');
         $CountUnreadConversations = GetValue('CountUnreadConversations', Gdn::Session()->User);
         if (is_numeric($CountUnreadConversations) && $CountUnreadConversations > 0)
            $Inbox .= ' <span class="Alert">'.$CountUnreadConversations.'</span>';
            
         $Sender->Menu->AddLink('Conversations', $Inbox, '/messages/all', FALSE, array('Standard' => TRUE));
      }
   }
   
   /**
    * Let us add Messages to the Inbox page.
    */
   public function Base_AfterGetLocationData_Handler($Sender, $Args) {
      $Args['ControllerData']['Conversations/messages/inbox'] = T('Inbox Page');
   }
   
   /**
    * Load some information into the BuzzData collection (for Dashboard report).
    *
    * @since 2.0.?
    * @access public
    */
   public function SettingsController_DashboardData_Handler($Sender) {
      /*
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
      */
   }   
   
   /**
    * Database & config changes to be done upon enable.
    *
    * @since 2.0.0
    * @access public
    */
   public function Setup() {
      $Database = Gdn::Database();
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Drop = C('Conversations.Version') === FALSE ? TRUE : FALSE;
      $Explicit = TRUE;
      $Validation = new Gdn_Validation(); // This is going to be needed by structure.php to validate permission names
      include(PATH_APPLICATIONS . DS . 'conversations' . DS . 'settings' . DS . 'structure.php');
      include(PATH_APPLICATIONS . DS . 'conversations' . DS . 'settings' . DS . 'stub.php');

      $ApplicationInfo = array();
      include(CombinePaths(array(PATH_APPLICATIONS . DS . 'conversations' . DS . 'settings' . DS . 'about.php')));
      $Version = ArrayValue('Version', ArrayValue('Conversations', $ApplicationInfo, array()), 'Undefined');
      SaveToConfig('Conversations.Version', $Version);
   }
}