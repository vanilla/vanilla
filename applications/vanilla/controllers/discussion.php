<?php if (!defined('APPLICATION')) exit();

/**
 * Discussion Controller
 */
class DiscussionController extends VanillaController {
   
   public $Uses = array('Gdn_DiscussionModel', 'Gdn_CommentModel', 'Form');
   public $CategoryID;
   
   public function Index($DiscussionID = '', $Offset = '', $Limit = '') {
      $this->AddCssFile('vanilla.css');
      $Session = Gdn::Session();
      $this->AddJsFile('/js/library/jquery.resizable.js');
      $this->AddJsFile('/js/library/jquery.ui.packed.js');
      $this->AddJsFile('/js/library/jquery.autogrow.js');
      $this->AddJsFile('/js/library/jquery.gardenmorepager.js');
      $this->AddJsFile('options.js');
      $this->AddJsFile('bookmark.js');
      $this->AddJsFile('discussion.js');
      $this->AddJsFile('autosave.js');
      
      // Load the discussion record
      $DiscussionID = (is_numeric($DiscussionID) && $DiscussionID > 0) ? $DiscussionID : 0;
      $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);
      if(!is_object($this->Discussion)) {
         Redirect('FileNotFound');
      }
      
      // Check Permissions
      $this->Permission('Vanilla.Discussions.View', $this->Discussion->CategoryID);
      $this->SetData('CategoryID', $this->CategoryID = $this->Discussion->CategoryID, TRUE);
      if ($this->Discussion === FALSE) {
         Redirect('garden/home/filenotfound');
      } else {
         // Setup
         $this->Title(Format::Text($this->Discussion->Name));
         
         // Define the query offset & limit
         if (!is_numeric($Limit) || $Limit < 0)
            $Limit = Gdn::Config('Vanilla.Comments.PerPage', 50);
         
         $this->Offset = $Offset;   
         if (!is_numeric($this->Offset) || $this->Offset < 0) {
            // Round down to the appropriate offset based on the user's read comments & comments per page
            $CountCommentWatch = $this->Discussion->CountCommentWatch > 0 ? $this->Discussion->CountCommentWatch : 0;
            if ($CountCommentWatch > $this->Discussion->CountComments)
               $CountCommentWatch = $this->Discussion->CountComments;
            
            // (((67 comments / 10 perpage) = 6.7) rounded down = 6) * 10 perpage = offset 60;
            $this->Offset = floor($CountCommentWatch / $Limit) * $Limit;
         }
         
         if ($this->Offset < 0)
            $this->Offset = 0;
            
         // Make sure to set the user's discussion watch records
         $this->CommentModel->SetWatch($this->Discussion, $Limit, $this->Offset, $this->Discussion->CountComments);
         
         // Load the comments
         $this->SetData('CommentData', $this->CommentData = $this->CommentModel->Get($DiscussionID, $Limit, $this->Offset), TRUE);

         // Build a pager
         $PagerFactory = new PagerFactory();
         $this->Pager = $PagerFactory->GetPager('MorePager', $this);
         $this->Pager->MoreCode = '%1$s more comments';
         $this->Pager->LessCode = '%1$s older comments';
         $this->Pager->ClientID = 'Pager';
         $this->Pager->Configure(
            $this->Offset,
            $Limit,
            $this->Discussion->CountComments,
            'vanilla/discussion/'.$DiscussionID.'/%1$s/%2$s/'.Format::Url($this->Discussion->Name)
         );
      }
      
      // Define the form for the comment input
      $this->Form = Gdn::Factory('Form', 'Comment');
      $this->Form->Action = Url('/vanilla/post/comment/');
      $this->DiscussionID = $this->Discussion->DiscussionID;
      $this->Form->AddHidden('DiscussionID', $this->DiscussionID);
      $this->Form->AddHidden('CommentID', '');

      // Retrieve & apply the draft if there is one:
      $DraftModel = new Gdn_DraftModel();
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
   
   public function GetNew($DiscussionID, $LastCommentID) {
      $this->SetData('Discussion', $this->DiscussionModel->GetID($DiscussionID), TRUE);
      
      // Check permissions.
      $this->Permission('Vanilla.Discussions.View', $this->Discussion->CategoryID);
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
         Redirect('/vanilla/discussions');

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

      $CountBookmarks = $this->DiscussionModel->BookmarkCount($Session->UserID);
      
      // Update the user's bookmark count
      $SQL = Gdn::SQL();
      
      $SQL
         ->Update('User')
         ->Set('CountBookmarks', $CountBookmarks)
         ->Where('UserID', $Session->UserID)
         ->Put();
      
      // Redirect back where the user came from if necessary
      if ($this->_DeliveryType != DELIVERY_TYPE_BOOL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions/bookmarked');
         Redirect($Target);
      }
      
      $MyBookmarks = Gdn::Translate('My Bookmarks');
      if (is_numeric($CountBookmarks) && $CountBookmarks > 0)
         $MyBookmarks .= '<span>'.$CountBookmarks.'</span>';            

      $this->SetJson('State', $State);
      $this->SetJson('ButtonLink', Gdn::Translate($State ? 'Unbookmark this Discussion' : 'Bookmark this Discussion'));
      $this->SetJson('AnchorTitle', Gdn::Translate($State ? 'Unbookmark' : 'Bookmark'));
      $this->SetJson('MenuLink', $MyBookmarks);
      
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
         if ($Discussion && $Session->CheckPermission('Vanilla.Discussions.Announce', $Discussion->CategoryID)) {
            $this->DiscussionModel->SetProperty($DiscussionID, 'Announce');
         } else {
            $this->Form->AddError('ErrPermission');
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('/vanilla/discussions');
         
      $this->RedirectUrl = Url('/vanilla/discussions');
      $this->StatusMessage = Gdn::Translate('Your changes have been saved.');
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
            if ($Session->CheckPermission('Vanilla.Discussions.Sink', $Discussion->CategoryID)) {
               $State = $this->DiscussionModel->SetProperty($DiscussionID, 'Sink');
            } else {
               $State = $Discussion->Sink;
               $this->Form->AddError('ErrPermission');
            }
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions');
         Redirect($Target);
      }
         
      $State = $State == '1' ? TRUE : FALSE;   
      $this->SetJson('State', $State);
      $this->SetJson('LinkText', Translate($State ? 'Unsink' : 'Sink'));         
      $this->StatusMessage = Gdn::Translate('Your changes have been saved.');
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
            if ($Session->CheckPermission('Vanilla.Discussions.Close', $Discussion->CategoryID)) {
               $State = $this->DiscussionModel->SetProperty($DiscussionID, 'Closed');
            } else {
               $State = $Discussion->Closed;
               $this->Form->AddError('ErrPermission');
            }
         }
      }
      
      // Redirect to the front page
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions');
         Redirect($Target);
      }
      
      $State = $State == '1' ? TRUE : FALSE;   
      $this->SetJson('State', $State);
      $this->SetJson('LinkText', Translate($State ? 'Re-Open' : 'Close'));         
      $this->StatusMessage = Gdn::Translate('Your changes have been saved.');
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
         if ($Discussion && $Session->CheckPermission('Vanilla.Discussions.Delete', $Discussion->CategoryID)) {
            if (!$this->DiscussionModel->Delete($DiscussionID))
               $this->Form->AddError('Failed to delete discussion');
         } else {
            $this->Form->AddError('ErrPermission');
         }
      } else {
         $this->Form->AddError('ErrPermission');
      }
      
      // Redirect
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         $Target = GetIncomingValue('Target', '/vanilla/discussions');
         Redirect($Target);
      }
         
      if ($this->Form->ErrorCount() > 0)
         $this->SetJson('ErrorMessage', $this->Form->Errors());
         
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
            $HasPermission = $Comment->InsertUserID = $Session->UserID;
            if (!$HasPermission && $Discussion)
               $HasPermission = $Session->CheckPermission('Vanilla.Comments.Delete', $Discussion->CategoryID);
            
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
         $Target = GetIncomingValue('Target', '/vanilla/discussions');
         Redirect($Target);
      }
         
      if ($this->Form->ErrorCount() > 0)
         $this->SetJson('ErrorMessage', $this->Form->Errors());
         
      $this->Render();         
   }

}