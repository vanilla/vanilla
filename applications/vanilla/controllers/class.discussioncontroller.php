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
 * Discussion Controller - handles accessing & displaying a single discussion
 * and all of it's parts.
 */
class DiscussionController extends VanillaController {
   
   public $Uses = array('DiscussionModel', 'CommentModel', 'Form');
   public $CategoryID;
   
   public function Index($DiscussionID = '', $DiscussionStub = '', $Offset = '', $Limit = '') {
      $this->AddCssFile('vanilla.css');
      $Session = Gdn::Session();
      $this->AddJsFile('jquery.resizable.js');
      $this->AddJsFile('jquery.ui.packed.js');
      $this->AddJsFile('jquery.autogrow.js');
//      $this->AddJsFile('jquery.gardenmorepager.js');
      $this->AddJsFile('options.js');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussion.js');
      $this->AddJsFile('autosave.js');
      
      // Load the discussion record
      $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
      $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);
      if(!is_object($this->Discussion)) {
         return Gdn::Dispatcher()->Dispatch('Default404');
      }
      
      // Check Permissions
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->Discussion->CategoryID);
      $this->SetData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, TRUE);
      
      // Setup
      $this->Title($this->Discussion->Name);

      // Actual number of comments, excluding the discussion itself
      $ActualResponses = $this->Discussion->CountComments - 1;
      // Define the query offset & limit
      if (!is_numeric($Limit) || $Limit < 0)
         $Limit = C('Vanilla.Comments.PerPage', 50);

      $OffsetProvided = $Offset != '';
      list($Offset, $Limit) = OffsetLimit($Offset, $Limit);

      // If $Offset isn't defined, assume that the user has not clicked to
      // view a next or previous page, and this is a "view" to be counted.
      if ($Offset == '')
         $this->DiscussionModel->AddView($DiscussionID);

      $this->Offset = $Offset;
      if (C('Vanilla.Comments.AutoOffset')) {
         if (!is_numeric($this->Offset) || $this->Offset < 0 || !$OffsetProvided) {
            // Round down to the appropriate offset based on the user's read comments & comments per page
            $CountCommentWatch = $this->Discussion->CountCommentWatch > 0 ? $this->Discussion->CountCommentWatch : 0;
            if ($CountCommentWatch > $ActualResponses)
               $CountCommentWatch = $ActualResponses;

            // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
            $this->Offset = floor($CountCommentWatch / $Limit) * $Limit;
         }
         if ($ActualResponses <= $Limit)
            $this->Offset = 0;

         if ($this->Offset == $ActualResponses)
            $this->Offset -= $Limit;
      } else {
         if ($this->Offset == '')
            $this->Offset = 0;
      }

      if ($this->Offset < 0)
         $this->Offset = 0;

      // Set the canonical url to have the proper page title.
      $this->CanonicalUrl(Url(ConcatSep('/', 'discussion/'.$this->Discussion->DiscussionID.'/'. Gdn_Format::Url($this->Discussion->Name), PageNumber($this->Offset, $Limit, TRUE)), TRUE));

      // Make sure to set the user's discussion watch records
      $this->CommentModel->SetWatch($this->Discussion, $Limit, $this->Offset, $this->Discussion->CountComments);

      // Load the comments
      $this->SetData('CommentData', $this->CommentModel->Get($DiscussionID, $Limit, $this->Offset), TRUE);
      $this->SetData('Comments', $this->CommentData);

      // Build a pager
      $PagerFactory = new Gdn_PagerFactory();
      $this->Pager = $PagerFactory->GetPager('Pager', $this);
      $this->Pager->ClientID = 'Pager';
      $this->Pager->Configure(
         $this->Offset,
         $Limit,
         $ActualResponses,
         'discussion/'.$DiscussionID.'/'.Gdn_Format::Url($this->Discussion->Name).'/%1$s' //'discussions/%1$s'
      );

//      $this->Pager->MoreCode = '%1$s more comments';
//      $this->Pager->LessCode = '%1$s older comments';
//      $this->Pager->ClientID = 'Pager';
//      $this->Pager->Configure(
//         $this->Offset,
//         $Limit,
//         $ActualResponses,
//         'discussion/'.$DiscussionID.'/'.Gdn_Format::Url($this->Discussion->Name).'/%1$s/%2$s/'
//      );
      
      // Define the form for the comment input
      $this->Form = Gdn::Factory('Form', 'Comment');
      $this->Form->Action = Url('/vanilla/post/comment/');
      $this->DiscussionID = $this->Discussion->DiscussionID;
      $this->Form->AddHidden('DiscussionID', $this->DiscussionID);
      $this->Form->AddHidden('CommentID', '');

      // Retrieve & apply the draft if there is one:
      $DraftModel = new DraftModel();
      $Draft = $DraftModel->Get($Session->UserID, 0, 1, $this->Discussion->DiscussionID)->FirstRow();
      $this->Form->AddHidden('DraftID', $Draft ? $Draft->DraftID : '');
      if ($Draft)
         $this->Form->SetFormValue('Body', $Draft->Body);
      
      // Deliver json data if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_ALL) {
         $this->SetJson('LessRow', $this->Pager->ToString('less'));
         $this->SetJson('MoreRow', $this->Pager->ToString('more'));
         $this->View = 'comments';
      }

      // Add Modules
      $this->AddModule('NewDiscussionModule');
      $this->AddModule('CategoriesModule');
      $BookmarkedModule = new BookmarkedModule($this);
      $BookmarkedModule->GetData();
      $this->AddModule($BookmarkedModule);
      
      $this->FireEvent('BeforeDiscussionRender');
      $this->Render();
   }
   
   public function GetNew($DiscussionID, $LastCommentID = 0) {
      $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);
      
      // Check permissions.
      $this->Permission('Vanilla.Discussions.View', TRUE, 'Category', $this->Discussion->CategoryID);
      $this->SetData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, TRUE);
      
      // Get the comments.
      $this->SetData('CommentData', $this->CommentModel->GetNew($DiscussionID, $LastCommentID), TRUE);
      
      // Set the data.
      $CommentData = $this->CommentData->Result();
      if(count($CommentData) > 0) {
         $LastComment = $CommentData[count($CommentData) - 1];
         // Mark the comment read.
         $this->SetData('Offset', $this->Discussion->CountComments, TRUE);
         $this->CommentModel->SetWatch($this->Discussion, $this->Discussion->CountComments, $this->Discussion->CountComments, $this->Discussion->CountComments);
         
         $LastCommentID = $this->Json('LastCommentID');
         if(is_null($LastCommentID) || $LastComment->CommentID > $LastCommentID) {
            $this->Json('LastCommentID', $LastComment->CommentID);
         }
      } else {
         $this->SetData('Offset', $this->CommentModel->GetOffset($LastCommentID), TRUE);
      }
      
      $this->View = 'comments';
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      $this->Menu->HighlightRoute('/discussions');
   }

   // Used for pointing directly to a comment in a discussion (ie. start the discussion page with that comment)
   public function Comment($CommentID) {
      // Get the discussionID
      $Comment = $this->CommentModel->GetID($CommentID);
      if (!$Comment)
         Redirect('dashboard/home/filenotfound');
         
      $DiscussionID = $Comment->DiscussionID;
      
      // Figure out how many comments are before this one
      $Offset = $this->CommentModel->GetOffset($CommentID);
      $Limit = Gdn::Config('Vanilla.Comments.PerPage', 50);
      
      // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
      $Offset = floor($Offset / $Limit) * $Limit;
      
      $this->View = 'index';
      $this->Index($DiscussionID, $Offset, $Limit);
   }
   
   // Discussion Options:  
   public function DismissAnnouncement($DiscussionID = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) $this->DiscussionModel->DismissAnnouncement($DiscussionID, $Session->UserID);

      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('discussions');

      $this->Render();         
   }
   
   /**
    * Allows you to bookmark or unbookmark a discussion (depending on it's current state).
    */
   public function Bookmark($DiscussionID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      $State = FALSE;
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      )
      $Discussion = NULL;
      $State = $this->DiscussionModel->BookmarkDiscussion($DiscussionID, $Session->UserID, $Discussion);

      // Update the user's bookmark count
      $CountBookmarks = $this->DiscussionModel->SetUserBookmarkCount($Session->UserID);
      
      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_BOOL) {
         $Target = GetIncomingValue('Target', 'discussions/bookmarked');
         Redirect($Target);
      }
      
      $this->SetJson('State', $State);
      $this->SetJson('CountBookmarks', $CountBookmarks);
      $this->SetJson('ButtonLink', T($State ? 'Unbookmark this Discussion' : 'Bookmark this Discussion'));
      $this->SetJson('AnchorTitle', T($State ? 'Unbookmark' : 'Bookmark'));
      $this->SetJson('MenuText', T('My Bookmarks'));
      
      $Targets = array();
      if($State) {
         // Grab the individual bookmark and send it to the client.
         $Bookmarks = new BookmarkedModule($this);
         if($CountBookmarks == 1) {
            // When there is only one bookmark we have to get the whole module.
            $Target = '#Panel';
            $Type = 'Append';
            $Bookmarks->GetData();
            $Data = $Bookmarks->ToString();
         } else {
            $Target = '#Bookmark_List';
            $Type = 'Prepend';
            $Loc = $Bookmarks->FetchViewLocation('discussion');
            
            ob_start();
            include($Loc);
            $Data = ob_get_clean();
         }
         $Targets[] = array('Target' => $Target, 'Type' => $Type, 'Data' => $Data);
      } else {
         // Send command to remove bookmark html.
         if($CountBookmarks == 0) {
            $Targets[] = array('Target' => '#Bookmarks', 'Type' => 'Remove');
         } else {
            $Targets[] = array('Target' => '#Bookmark_'.$DiscussionID, 'Type' => 'Remove');
         }
      }
      $this->SetJson('Targets', $Targets);
      
      $this->Render();         
   }
   
   /**
    * Allows you to announce or unannounce a discussion (depending on it's current state).
    */
   public function Announce($DiscussionID = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $State = FALSE;
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);
         if ($Discussion && $Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $Discussion->CategoryID)) {
            $this->DiscussionModel->SetProperty($DiscussionID, 'Announce');
         } else {
            $this->Form->AddError('ErrPermission');
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('discussions');
         
      $this->RedirectUrl = Url('discussions');
      $this->StatusMessage = T('Your changes have been saved.');
      $this->Render();         
   }

   /**
    * Allows you to sink or unsink a discussion (depending on it's current state).
    */
   public function Sink($DiscussionID = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $State = '1';
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);
         if ($Discussion) {
            if ($Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $Discussion->CategoryID)) {
               $State = $this->DiscussionModel->SetProperty($DiscussionID, 'Sink');
            } else {
               $State = $Discussion->Sink;
               $this->Form->AddError('ErrPermission');
            }
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', 'discussions');
         Redirect($Target);
      }
         
      $State = $State == '1' ? TRUE : FALSE;   
      $this->SetJson('State', $State);
      $this->SetJson('LinkText', T($State ? 'Unsink' : 'Sink'));         
      $this->StatusMessage = T('Your changes have been saved.');
      $this->Render();         
   }

   /**
    * Allows you to close or re-open a discussion (depending on it's current state).
    */
   public function Close($DiscussionID = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      $State = '1';
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);
         if ($Discussion) {
            if ($Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $Discussion->CategoryID)) {
               $State = $this->DiscussionModel->SetProperty($DiscussionID, 'Closed');
            } else {
               $State = $Discussion->Closed;
               $this->Form->AddError('ErrPermission');
            }
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', 'discussions');
         Redirect($Target);
      }
      
      $State = $State == '1' ? TRUE : FALSE;   
      $this->SetJson('State', $State);
      $this->SetJson('LinkText', T($State ? 'Reopen' : 'Close'));         
      $this->StatusMessage = T('Your changes have been saved.');
      $this->Render();         
   }

   /**
    * Allows you to delete a discussion.
    */
   public function Delete($DiscussionID = '', $TransientKey = '') {
      $this->_DeliveryType = DELIVERY_TYPE_BOOL;
      $Session = Gdn::Session();
      if (
         is_numeric($DiscussionID)
         && $DiscussionID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Discussion = $this->DiscussionModel->GetID($DiscussionID);
         if ($Discussion && $Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->CategoryID)) {
            if (!$this->DiscussionModel->Delete($DiscussionID))
               $this->Form->AddError('Failed to delete discussion');
         } else {
            $this->Form->AddError('ErrPermission');
         }
      } else {
         $this->Form->AddError('ErrPermission');
      }
      
      // Redirect
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', '/discussions'));
         
      if ($this->Form->ErrorCount() > 0)
         $this->SetJson('ErrorMessage', $this->Form->Errors());
         
      $this->RedirectUrl = GetIncomingValue('Target', Url('discussions'));
      $this->Render();         
   }

   /**
    * Allows you to delete a comment. If the comment is the only one in the
    * discussion, the discussion will be deleted as well. Users without
    * administrative delete abilities should not be able to delete a comment
    * unless it is a draft.
    */
   public function DeleteComment($CommentID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      if (
         is_numeric($CommentID)
         && $CommentID > 0
         && $Session->UserID > 0
         && $Session->ValidateTransientKey($TransientKey)
      ) {
         $Comment = $this->CommentModel->GetID($CommentID);
         if ($Comment) {
            $Discussion = $this->DiscussionModel->GetID($Comment->DiscussionID);
            $HasPermission = $Comment->InsertUserID == $Session->UserID;
            if (!$HasPermission && $Discussion)
               $HasPermission = $Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $Discussion->CategoryID);
            
            if ($Discussion && $HasPermission) {
               if (!$this->CommentModel->Delete($CommentID))
                  $this->Form->AddError('Failed to delete comment');
            } else {
               $this->Form->AddError('ErrPermission');
            }
         }
      } else {
         $this->Form->AddError('ErrPermission');
      }
      
      // Redirect
      if ($this->_DeliveryType != DELIVERY_TYPE_BOOL) {
         $Target = GetIncomingValue('Target', 'discussions');
         Redirect($Target);
      }
         
      if ($this->Form->ErrorCount() > 0)
         $this->SetJson('ErrorMessage', $this->Form->Errors());
         
      $this->Render();         
   }

}