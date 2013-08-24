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
 * Messages Controller
 *
 * @package Conversations
 */
 
/**
 * MessagesController handles displaying lists of conversations and conversation messages.
 *
 * @since 2.0.0
 * @package Conversations
 */
class MessagesController extends ConversationsController {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Form', 'ConversationModel', 'ConversationMessageModel');
   
   /**
    * A dataset of users taking part in this discussion. Used by $this->Index.
    * 
    * @since 2.0.0
    * @access public
    * @var object
    */
   public $RecipientData;
   
   /**
    * The current offset of the paged data set. Defined and used by $this->Index and $this->All.
    * 
    * @since 2.0.0
    * @access public
    * @var int
    */
   public $Offset;
   
   /**
    * Highlight route and include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      $this->Menu->HighlightRoute('/messages/inbox');
      $this->SetData('Breadcrumbs', array(array('Name' => T('Inbox'), 'Url' => '/messages/inbox')));
//      $this->AddModule('MeModule');
      $this->AddModule('SignedInModule');

      if (CheckPermission('Conversations.Conversations.Add'))
         $this->AddModule('NewConversationModule');
   }
   
   /**
    * Start a new conversation.
    *
    * @since 2.0.0
    * @access public
    *
    * @param string $Recipient Username of the recipient.
    */
   public function Add($Recipient = '') {
      $this->Permission('Conversations.Conversations.Add');
      $this->Form->SetModel($this->ConversationModel);
      
      if ($this->Form->AuthenticatedPostBack()) {
         $RecipientUserIDs = array();
         $To = explode(',', $this->Form->GetFormValue('To', ''));
         $UserModel = new UserModel();
         foreach ($To as $Name) {
            if (trim($Name) != '') {
               $User = $UserModel->GetByUsername(trim($Name));
               if (is_object($User))
                  $RecipientUserIDs[] = $User->UserID;
            }
         }
         
         $this->EventArguments['Recipients'] = $RecipientUserIDs;
         $this->FireEvent('BeforeAddConversation');
         
         $this->Form->SetFormValue('RecipientUserID', $RecipientUserIDs);
         $ConversationID = $this->Form->Save($this->ConversationMessageModel);
         if ($ConversationID !== FALSE) {
            $Target = $this->Form->GetFormValue('Target', 'messages/'.$ConversationID);
            
            $this->RedirectUrl = Url($Target);
         }
      } else {
         if ($Recipient != '')
            $this->Form->SetValue('To', $Recipient);
      }
      if ($Target = Gdn::Request()->Get('Target'))
            $this->Form->AddHidden('Target', $Target);

      Gdn_Theme::Section('PostConversation');
      $this->Title(T('New Conversation'));
      $this->SetData('Breadcrumbs', array(array('Name' => T('Inbox'), 'Url' => '/messages/inbox'), array('Name' => $this->Data('Title'), 'Url' => 'messages/add')));
      $this->Render();      
   }
   
   /**
    * Add a message to a conversation.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $ConversationID Unique ID of the conversation.
    */
   public function AddMessage($ConversationID = '') {
      $this->Form->SetModel($this->ConversationMessageModel);
      if (is_numeric($ConversationID) && $ConversationID > 0)
         $this->Form->AddHidden('ConversationID', $ConversationID);
      
      if ($this->Form->AuthenticatedPostBack()) {
         $ConversationID = $this->Form->GetFormValue('ConversationID', '');
         $Conversation = $this->ConversationModel->GetID($ConversationID, Gdn::Session()->UserID);   
         
         $this->EventArguments['Conversation'] = $Conversation;
         $this->EventArguments['ConversationID'] = $ConversationID;
         $this->FireEvent('BeforeAddMessage');
         
         $NewMessageID = $this->Form->Save();
         
         if ($NewMessageID) {
            if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
               Redirect('messages/'.$ConversationID.'/#'.$NewMessageID, 302);
               
            $this->SetJson('MessageID', $NewMessageID);
            // If this was not a full-page delivery type, return the partial response
            // Load all new messages that the user hasn't seen yet (including theirs)
            $LastMessageID = $this->Form->GetFormValue('LastMessageID');
            if (!is_numeric($LastMessageID))
               $LastMessageID = $NewMessageID - 1;
            
            $Session = Gdn::Session();
            $MessageData = $this->ConversationMessageModel->GetNew($ConversationID, $LastMessageID);
            $this->Conversation = $Conversation;
            $this->MessageData = $MessageData;

            $this->View = 'messages';
         } else {
            // Handle ajax based errors...
            if ($this->DeliveryType() != DELIVERY_TYPE_ALL)
               $this->ErrorMessage($this->Form->Errors());
         }
      }
      $this->Render();      
   }
   
   /**
    * Show all conversations for the currently authenticated user.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param string $Page
    */
   public function All($Page = '') {
      $Session = Gdn::Session();
      $this->Title(T('Inbox'));
      Gdn_Theme::Section('ConversationList');

      list($Offset, $Limit) = OffsetLimit($Page, C('Conversations.Conversations.PerPage', 50));
      
      // Calculate offset
      $this->Offset = $Offset;
      
      $UserID = $this->Request->Get('userid', Gdn::Session()->UserID);
      if ($UserID != Gdn::Session()->UserID) {
         if (!C('Conversations.Moderation.Allow', FALSE)) {
            throw PermissionException();
         }
         $this->Permission('Conversations.Moderation.Manage');
      }
      
      $Conversations = $this->ConversationModel->Get2($UserID, $Offset, $Limit);
      $this->SetData('Conversations', $Conversations->ResultArray());
      
      // Get Conversations Count
      //$CountConversations = $this->ConversationModel->GetCount($UserID);
      //$this->SetData('CountConversations', $CountConversations);
      
      // Build the pager
      if (!$this->Data('_PagerUrl'))
         $this->SetData('_PagerUrl', 'messages/all/{Page}');
      $this->SetData('_Page', $Page);
      $this->SetData('_Limit', $Limit);
      $this->SetData('_CurrentRecords', count($Conversations->ResultArray()));
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL && $this->_DeliveryMethod == DELIVERY_METHOD_XHTML) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'conversations';
      }
      
      // Build and display page.
      $this->Render();
   }
   
   /**
    * Clear the message history for a specific conversation & user.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $ConversationID Unique ID of conversation to clear.
    */
   public function Clear($ConversationID = FALSE, $TransientKey = '') {
      $Session = Gdn::Session();
      
      // Yes/No response
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      
      $ValidID = (is_numeric($ConversationID) && $ConversationID > 0);
      $ValidSession = ($Session->UserID > 0 && $Session->ValidateTransientKey($TransientKey));
      
      if ($ValidID && $ValidSession) {
         // Clear it
         $this->ConversationModel->Clear($ConversationID, $Session->UserID);
         $this->InformMessage(T('The conversation has been cleared.'));
         $this->RedirectUrl = Url('/messages/all');
      }
      
      $this->Render();
   }
   
   /**
    * Shows all uncleared messages within a conversation for the viewing user
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation to view.
    * @param int $Offset Number to skip.
    * @param int $Limit Number to show.
    */
   public function Index($ConversationID = FALSE, $Offset = -1, $Limit = '') {
      $this->Offset = $Offset;
      $Session = Gdn::Session();
      Gdn_Theme::Section('Conversation');
      
      // Figure out Conversation ID
      if (!is_numeric($ConversationID) || $ConversationID < 0)
         $ConversationID = 0;

      // Form setup for adding comments
      $this->Form->SetModel($this->ConversationMessageModel);
      $this->Form->AddHidden('ConversationID', $ConversationID);
      
      // Get conversation data
      $this->RecipientData = $this->ConversationModel->GetRecipients($ConversationID);
      $this->SetData('Recipients', $this->RecipientData);

      // Check permissions on the recipients.
      $InConversation = FALSE;
      foreach($this->RecipientData->Result() as $Recipient) {
         if ($Recipient->UserID == Gdn::Session()->UserID) {
            $InConversation = TRUE;
            break;
         }
      }
      if (!$InConversation) {
         // Conversation moderation must be enabled and they must have permission
         if (!C('Conversations.Moderation.Allow', FALSE)) {
            throw PermissionException();
         }
         $this->Permission('Conversations.Moderation.Manage');
      }
      
      $this->Conversation = $this->ConversationModel->GetID($ConversationID);
      $this->SetData('Conversation', $this->Conversation);
      
      // Bad conversation? Redirect
      if ($this->Conversation === FALSE)
         throw NotFoundException('Conversation');
      
      // Get limit
      if ($Limit == '' || !is_numeric($Limit) || $Limit < 0)
         $Limit = Gdn::Config('Conversations.Messages.PerPage', 50);
      
      // Calculate counts
      if (!is_numeric($this->Offset) || $this->Offset < 0) {
         // Round down to the appropriate offset based on the user's read messages & messages per page
         $CountReadMessages = $this->Conversation->CountMessages - $this->Conversation->CountNewMessages;
         if ($CountReadMessages < 0)
            $CountReadMessages = 0;
            
         if ($CountReadMessages > $this->Conversation->CountMessages)
            $CountReadMessages = $this->Conversation->CountMessages;
         
         // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
         $this->Offset = floor($CountReadMessages / $Limit) * $Limit;

         // Send the hash link in.
         if ($CountReadMessages > 1)
            $this->AddDefinition('LocationHash', '#Item_'.$CountReadMessages);
      }
      
      // Fetch message data
      $this->MessageData = $this->ConversationMessageModel->Get(
         $ConversationID,
         $Session->UserID,
         $this->Offset,
         $Limit
      );
      
      // Figure out who's participating.
      $this->Participants = '';
      $Count = 0;
      $Users = array();
      $InConversation = FALSE;
      foreach($this->RecipientData->Result() as $User) {
         $Count++;
         if($User->UserID == $Session->UserID) {
            $InConversation = TRUE;
            continue;
         }
         if($User->Deleted) {
            $Users[] = Wrap(UserAnchor($User), 'del', array('title' => sprintf(T('%s has left this conversation.'), htmlspecialchars($User->Name))));
            $this->SetData('_HasDeletedUsers', TRUE);
         } else
            $Users[] = UserAnchor($User);

         
      }
      if ($InConversation) {
         if(count($Users) == 0)
            $this->Participants = T('Just you!');
         else
            $this->Participants = sprintf(T('%s and you'), implode(', ', $Users));
      } else {
         $this->Participants = implode(', ', $Users);
      }
      
      $this->Title(strip_tags($this->Participants));

      // $CountMessages = $this->ConversationMessageModel->GetCount($ConversationID, $Session->UserID);
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'Newer Messages';
      $this->Pager->LessCode = 'Older Messages';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $this->Offset,
         $Limit,
         $this->Conversation->CountMessages,
         'messages/'.$ConversationID.'/%1$s/%2$s/'
      );
      
      // Mark the conversation as ready by this user.
      $this->ConversationModel->MarkRead($ConversationID, $Session->UserID);
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'messages';
      }
      
      // Add modules.
      $ClearHistoryModule = new ClearHistoryModule($this);
      $ClearHistoryModule->ConversationID($ConversationID);
      $this->AddModule($ClearHistoryModule);
      
      $InThisConversationModule = new InThisConversationModule($this);
      $InThisConversationModule->SetData($this->RecipientData);
      $this->AddModule($InThisConversationModule);

      // Doesn't make sense for people who can't even start conversations to be adding people
      if (CheckPermission('Conversations.Conversations.Add'))
         $this->AddModule('AddPeopleModule');
      
      $Subject = $this->Data('Conversation.Subject');
      if (!$Subject)
         $Subject = T('Message');
      
      $this->Data['Breadcrumbs'][] = array(
          'Name' => $Subject,
          Url('', '//'));
      
      // Render view
      $this->Render();
   }
   
   public function Popin() {
      $this->Permission('Garden.SignIn.Allow');
      
      // Fetch from model  
      $Conversations = $this->ConversationModel->Get2(
         Gdn::Session()->UserID,
         0,
         5
      )->ResultArray();
      
      // Last message user data
      Gdn::UserModel()->JoinUsers($Conversations, array('LastInsertUserID'));
      
      // Join in the participants.
      $this->SetData('Conversations', $Conversations);
      $this->Render();
   }
   
   /**
    * Allows users to bookmark conversations.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $ConversationID Unique ID of conversation to view.
    * @param string $TransientKey Single-use hash to prove intent.
    */
   public function Bookmark($ConversationID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      $Success = FALSE;
      $Star = FALSE;
      
      // Validate & do bookmarking
      if (
         is_numeric($ConversationID)
         && $ConversationID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Bookmark = $this->ConversationModel->Bookmark($ConversationID, $Session->UserID);
      }
      
      // Report success or error
      if ($Bookmark === FALSE)
         $this->Form->AddError('ErrorBool');
      else
         $this->SetJson('Bookmark', $Bookmark);
      
      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType == DELIVERY_TYPE_ALL)
         Redirect($_SERVER['HTTP_REFERER']);
      else
         $this->Render();
   }
   
   /**
    * Show bookmarked conversations for the current user.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number to skip.
    * @param string $Limit Number to show.
    */
//   public function Bookmarked($Offset = 0, $Limit = '') {
//      $this->View = 'All';
//      $this->All($Offset, $Limit, TRUE);
//   }

   /**
    * Show bookmarked conversations for the current user.
    *
    * @since 2.0.0
    * @access public
    *
    * @param int $Offset Number to skip.
    * @param string $Limit Number to show.
    */
   public function Inbox($Page = '') {
      $this->View = 'All';
      $this->All($Page);
   }
}