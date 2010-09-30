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
 * MessagesController handles displaying lists of conversations and conversation messages.
 */
class MessagesController extends ConversationsController {
   
   public $Uses = array('Form', 'ConversationModel', 'ConversationMessageModel');
   
   public function Initialize() {
      parent::Initialize();
      $this->Menu->HighlightRoute('/messages/all');
   }
   
   /**
    * Add a new conversations.
    */
   public function Add($Recipient = '') {
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
         $this->Form->SetFormValue('RecipientUserID', $RecipientUserIDs);
         $ConversationID = $this->Form->Save($this->ConversationMessageModel);
         if ($ConversationID !== FALSE)
            $this->RedirectUrl = Url('messages/'.$ConversationID);
      } else if ($Recipient != '') {
         $this->Form->SetFormValue('To', $Recipient);
      }
      $this->Render();      
   }
   
   /**
    * Add a message to a conversation.
    */
   public function AddMessage($ConversationID = '') {
      $this->Form->SetModel($this->ConversationMessageModel);
      if (is_numeric($ConversationID) && $ConversationID > 0)
         $this->Form->AddHidden('ConversationID', $ConversationID);
      
      if ($this->Form->AuthenticatedPostBack()) {
         $ConversationID = $this->Form->GetFormValue('ConversationID', '');
         $NewMessageID = $this->Form->Save();
         if ($NewMessageID) {
            if ($this->DeliveryType() == DELIVERY_TYPE_ALL)
               Redirect('messages/'.$ConversationID.'/#'.$NewMessageID);
               
            $this->SetJson('MessageID', $NewMessageID);
            // If this was not a full-page delivery type, return the partial response
            // Load all new messages that the user hasn't seen yet (including theirs)
            $LastMessageID = $this->Form->GetFormValue('LastMessageID');
            if (!is_numeric($LastMessageID))
               $LastMessageID = $NewMessageID - 1;
            
            $Session = Gdn::Session();
            $this->Conversation = $this->ConversationModel->GetID($ConversationID, $Session->UserID);   
            $this->MessageData = $this->ConversationMessageModel->GetNew($ConversationID, $LastMessageID);
            $this->View = 'messages';
         } else {
            // Handle ajax based errors...
            if ($this->DeliveryType() != DELIVERY_TYPE_ALL)
               $this->StatusMessage = $this->Form->Errors();
         }
      }
      $this->Render();      
   }
   
   /**
    * Show all conversations for the currently authenticated user.
    */
   public function All($Offset = 0, $Limit = '', $BookmarkedOnly = FALSE) {
      $this->Title(T('Conversations'));
      $this->Offset = $Offset;
      $Session = Gdn::Session();
      if (!is_numeric($this->Offset) || $this->Offset < 0)
         $this->Offset = 0;
      
      if ($Limit == '' || !is_numeric($Limit) || $Limit < 0)
         $Limit = Gdn::Config('Conversations.Conversations.PerPage', 50);
         
      $Wheres = array();
      if ($BookmarkedOnly !== FALSE)
         $Wheres['Bookmarked'] = '1';
         
      $this->ConversationData = $this->ConversationModel->Get(
         $Session->UserID,
         $this->Offset,
         $Limit,
         $Wheres
      );
      
      $CountConversations = $this->ConversationModel->GetCount($Session->UserID, $Wheres);
      
      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('MorePager', $this);
      $this->Pager->MoreCode = 'Older Conversations';
      $this->Pager->LessCode = 'Newer Conversations';
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $this->Offset,
         $Limit,
         $CountConversations,
         'messages/all/%1$s/%2$s/'
      );
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'conversations';
      }
      
      $this->AddModule('SignedInModule');
      $this->AddModule('NewConversationModule');
      $this->Render();
   }
   
   /**
    * Clear the message history for a specific conversation & user.
    */
   public function Clear($ConversationID = FALSE) {
      $Session = Gdn::Session();
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      if (is_numeric($ConversationID) && $ConversationID > 0 && $Session->IsValid())
         $this->ConversationModel->Clear($ConversationID, $Session->UserID);
         
      $this->StatusMessage = T('The conversation has been cleared.');
      $this->RedirectUrl = Url('/messages/all');
      $this->Render();
   }
   
   /**
    * A dataset of users taking part in this discussion. Used by $this->Index.
    */
   public $RecipientData;
   
   /**
    * The current offset of the paged data set. Defined and used by $this->Index and $this->All.
    */
   public $Offset;
   
   /**
    * Shows all uncleared messages within a conversation for the viewing user
    */
   public function Index($ConversationID = FALSE, $Offset = -1, $Limit = '') {
      $this->Offset = $Offset;
      $Session = Gdn::Session();
      if (!is_numeric($ConversationID) || $ConversationID < 0)
         $ConversationID = 0;

      $this->Form->SetModel($this->ConversationMessageModel);
      $this->Form->AddHidden('ConversationID', $ConversationID);
      
      $this->RecipientData = $this->ConversationModel->GetRecipients($ConversationID);
      $this->Conversation = $this->ConversationModel->GetID($ConversationID, $Session->UserID);
      
      if ($this->Conversation === FALSE)
         Redirect('dashboard/home/filenotfound');

      if ($Limit == '' || !is_numeric($Limit) || $Limit < 0)
         $Limit = Gdn::Config('Conversations.Messages.PerPage', 50);
      
      if (!is_numeric($this->Offset) || $this->Offset < 0) {
         // Round down to the appropriate offset based on the user's read messages & messages per page
         $CountReadMessages = $this->Conversation->CountMessages - $this->Conversation->CountNewMessages;
         if ($CountReadMessages < 0)
            $CountReadMessages = 0;
            
         if ($CountReadMessages > $this->Conversation->CountMessages)
            $CountReadMessages = $this->Conversation->CountMessages;
         
         // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
         $this->Offset = floor($CountReadMessages / $Limit) * $Limit;
      }
         
      $this->MessageData = $this->ConversationMessageModel->Get(
         $ConversationID,
         $Session->UserID,
         $this->Offset,
         $Limit
      );
      
      $this->Participants = '';
      $Count = 0;
      $Users = array();
      foreach($this->RecipientData->Result() as $User) {
         if($User->Deleted)
            continue;
         $Count++;
         if($User->UserID == $Session->UserID)
            continue;
         $Users[] = UserAnchor($User);
      }
      if(count($Users) == 0)
         $this->Participants = T('Just you!');
      else
         $this->Participants = sprintf(T('%s and you'), implode(', ', $Users));
      
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
      
      $this->AddModule('SignedInModule');
      $this->AddModule('NewConversationModule');

      $ClearHistoryModule = new ClearHistoryModule($this);
      $ClearHistoryModule->ConversationID($ConversationID);
      $this->AddModule($ClearHistoryModule);
      
      $InThisConversationModule = new InThisConversationModule($this);
      $InThisConversationModule->SetData($this->RecipientData);
      $this->AddModule($InThisConversationModule);
      
      $this->AddModule('AddPeopleModule');
      
      $this->Render();
   }
   
   /**
    * Allows users to bookmark conversations.
    */
   public function Bookmark($ConversationID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      $Success = FALSE;
      $Star = FALSE;
      if (
         is_numeric($ConversationID)
         && $ConversationID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Bookmark = $this->ConversationModel->Bookmark($ConversationID, $Session->UserID);
      }
      
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
    * @param int
    * @param string
    */
   public function Bookmarked($Offset = 0, $Limit = '') {
      $this->View = 'All';
      $this->All($Offset, $Limit, TRUE);
   }

   public function Inbox($Offset = 0, $Limit = '', $BookmarkedOnly = FALSE) {
      $this->View = 'All';
      $this->All($Offset, $Limit, $BookmarkedOnly);
   }
}