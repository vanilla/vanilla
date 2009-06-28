<?php if (!defined('APPLICATION')) exit();

/// <summary>
/// Discussion Controller
/// </summary>
class PostController extends VanillaController {
   
   public $Uses = array('Form', 'Database', 'CommentModel', 'DiscussionModel');
   
   /// <summary>
   /// Create a discussion.
   /// </summary>
   /// <param name="CategoryID" type="int" required="FALSE" default="empty">
   /// The CategoryID to add the discussion to.
   /// </param>
   public function Discussion($CategoryID = '') {
      $Session = Gdn::Session();
      $DiscussionID = isset($this->Discussion) ? $this->Discussion->DiscussionID : '';
      $this->CategoryID = isset($this->Discussion) ? $this->Discussion->CategoryID : $CategoryID;
      if (Gdn::Config('Vanilla.Categories.Use') === TRUE) {
         $CategoryModel = new CategoryModel();
         $this->CategoryData = $CategoryModel->GetFull();
      }
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/applications/vanilla/js/post.js');
         $this->Head->AddScript('/applications/vanilla/js/autosave.js');
      }
      
      if (isset($this->Discussion)) {
         if ($this->Discussion->InsertUserID != $Session->UserID)
            $this->Permission('Vanilla.Discussions.Edit', $this->Discussion->CategoryID);

      } else {
         $this->Permission('Vanilla.Discussions.Add');
      }
      
      // Set the model on the form.
      $this->Form->SetModel($this->DiscussionModel);
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         if (isset($this->Discussion))
            $this->Form->SetData($this->Discussion);
         else
            $this->Form->SetData(array('CategoryID' => $CategoryID));
            
      } else {
         // Save as a draft?
         $FormValues = $this->Form->FormValues();
         $Draft = $this->Form->ButtonExists('Post Discussion') ? FALSE : TRUE;
         $Preview = $this->Form->ButtonExists('Preview') ? TRUE : FALSE;
         $FormValues['Draft'] = $Draft ? '1' : '0';
         if (!$Preview) {
            // Check category permissions
            if ($this->Form->GetFormValue('Announce', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Announce', $this->CategoryID))
               $this->Form->AddError('You do not have permission to announce in this category', 'Announce');

            if ($this->Form->GetFormValue('Close', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Close', $this->CategoryID))
               $this->Form->AddError('You do not have permission to close in this category', 'Close');

            if ($this->Form->GetFormValue('Sink', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Sink', $this->CategoryID))
               $this->Form->AddError('You do not have permission to sink in this category', 'Sink');
               
            if ($this->Form->ErrorCount() == 0) {
               $DiscussionID = $this->DiscussionModel->Save($FormValues, $this->CommentModel);
               $this->Form->SetValidationResults($this->DiscussionModel->ValidationResults());
            }
         } else {
            // If this was a preview click, create a discussion/comment shell with the values for this comment
            // if ($DiscussionID > 0) {
            //    $this->Discussion = $this->DiscussionModel->GetID($DiscussionID);
            //    $this->Comment = $this->CommentModel->GetID($this->Discussion->FirstCommentID);
            // } else {
               $this->Discussion = new stdClass();
               $this->Discussion->Name = $this->Form->GetValue('Name', '');
               $this->Comment = new stdClass();
               $this->Comment->InsertName = $Session->User->Name;
               $this->Comment->InsertPhoto = $Session->User->Photo;
               $this->Comment->DateInserted = Format::Date();
               $this->Comment->Body = ArrayValue('Body', $FormValues, '');
            // }
            if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
               $this->AddAsset('Content', $this->FetchView('preview'));
            } else {
               $this->View = 'preview';
            }
         }
         
         if ($this->Form->ErrorCount() > 0) {
            // Return the form errors
            $this->StatusMessage = $this->Form->Errors();
         } else if ($DiscussionID > 0) {
            // Make sure that the ajax request form knows about the newly created discussion id
            $this->SetJson('DiscussionID', $DiscussionID);
            
            // If the discussion was not a draft
            if (!$Draft) {
               // Redirect to the new discussion
               $Discussion = $this->DiscussionModel->GetID($DiscussionID);
               if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                  Redirect('/vanilla/discussion/'.$DiscussionID.'/'.Format::Url($Discussion->Name));
               } else {
                  $this->RedirectUrl = Url('/vanilla/discussion/'.$DiscussionID.'/'.Format::Url($Discussion->Name));
               }
            } else if ($Draft && !$Preview) {
               // If this was a draft save, notify the user about the save
               $this->StatusMessage = sprintf(Gdn::Translate('Draft saved at %s'), Format::Date());
            }
         }
      }
      $this->Form->AddHidden('DiscussionID', $DiscussionID);
      $this->Render();
   }
   
   /// <summary>
   /// Edit a discussion.
   /// </summary>
   /// <param name="DiscussionID" type="int" required="FALSE" default="empty">
   /// The DiscussionID of the discussion to edit. If blank, this method will
   /// throw an error.
   /// </param>
   public function EditDiscussion($DiscussionID = '') {
      $this->Discussion = $this->DiscussionModel->GetID($DiscussionID);
      $this->CategoryID = $this->Discussion->CategoryID;
      $this->View = 'Discussion';
      $this->Discussion($this->CategoryID);
   }
   
   /// <summary>
   /// Create a comment.
   /// </summary>
   /// <param name="DiscussionID" type="int" required="FALSE" default="empty">
   /// The DiscussionID to add the comment to. If blank, this method will throw
   /// an error.
   /// </param>
   public function Comment($DiscussionID = '') {
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/applications/vanilla/js/post.js');
         $this->Head->AddScript('/applications/vanilla/js/autosave.js');
      }

      $Session = Gdn::Session();
      $this->Form->SetModel($this->CommentModel);
      $CommentID = isset($this->Comment) ? $this->Comment->CommentID : '';
      $this->EventArguments['CommentID'] = $CommentID;
      $Editing = $CommentID > 0;
      $this->EventArguments['Editing'] = $Editing;
      $DiscussionID = is_numeric($DiscussionID) ? $DiscussionID : $this->Form->GetFormValue('DiscussionID', 0);
      $this->Form->AddHidden('DiscussionID', $DiscussionID);
      $this->Form->AddHidden('CommentID', $CommentID);
      $this->DiscussionID = $DiscussionID;
      $Discussion = $this->DiscussionModel->GetID($DiscussionID);
      if ($Editing) {
         if ($this->Comment->InsertUserID != $Session->UserID)
            $this->Permission('Vanilla.Comments.Edit', $Discussion->CategoryID);

      } else {
         $this->Permission('Vanilla.Comments.Add', $Discussion->CategoryID);
      }

      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         if (isset($this->Comment))
            $this->Form->SetData($this->Comment);
            
      } else {
         // Save as a draft?
         $FormValues = $this->Form->FormValues();
         $Draft = $this->Form->ButtonExists('Post Comment') ? FALSE : TRUE;
         $this->EventArguments['Draft'] = $Draft;
         $Preview = $this->Form->ButtonExists('Preview') ? TRUE : FALSE;
         $FormValues['Draft'] = $Draft ? '1' : '0';
         if (!$Preview) {
            $CommentID = $this->CommentModel->Save($FormValues);
            $this->Form->SetValidationResults($this->CommentModel->ValidationResults());
         }
         
         // Handle non-ajax requests first:
         if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            if ($this->Form->ErrorCount() == 0) {
               // Make sure that this form knows what comment we are editing.
               if ($CommentID > 0)
                  $this->Form->AddHidden('CommentID', $CommentID);
               
               // If the comment was not a draft
               if (!$Draft) {
                  // Redirect redirect to the new comment
                  $Discussion = $this->DiscussionModel->GetID($DiscussionID);
                  Redirect('/vanilla/discussion/'.$DiscussionID.'/'.Format::Url($Discussion->Name).'/#Comment_'.$CommentID);
               } elseif ($Preview) {
                  // If this was a preview click, create a comment shell with the values for this comment
                  $this->Comment = new stdClass();
                  $this->Comment->InsertName = $Session->User->Name;
                  $this->Comment->InsertPhoto = $Session->User->Photo;
                  $this->Comment->DateInserted = Format::Date();
                  $this->Comment->Body = ArrayValue('Body', $FormValues, '');
                  $this->AddAsset('Content', $this->FetchView('preview'));
               } else {
                  // If this was a draft save, notify the user about the save
                  $this->StatusMessage = sprintf(Gdn::Translate('Draft saved at %s'), Format::Date());
               }
            }
         } else {
            // Handle ajax-based requests
            if ($this->Form->ErrorCount() > 0) {
               // Return the form errors
               $this->StatusMessage = $this->Form->Errors();               
            } else {
               // Make sure that the ajax request form knows about the newly created comment id
               $this->SetJson('CommentID', $CommentID);
               
               // If the comment was not a draft
               if (!$Draft) {
                  // If adding a comment 
                  if ($Editing) {
                     // Just reload the comment in question
                     $this->Offset = $this->CommentModel->GetOffset($CommentID);
                     $this->CommentData = $this->CommentModel->Get($DiscussionID, 1, $this->Offset-1);
                     // Load the discussion
                     $this->Discussion = $this->DiscussionModel->GetID($DiscussionID);
                     $this->ControllerName = 'discussion';
                     $this->View = 'comments';
                     
                     // Also define the discussion url in case this request came from the post screen and needs to be redirected to the discussion
                     $this->SetJson('DiscussionUrl', Url('/discussion/'.$DiscussionID.'/'.Format::Url($this->Discussion->Name).'/#Comment_'.$CommentID));
                  } else {
                     // Otherwise load all new comments that the user hasn't seen yet
                     $LastCommentID = $this->Form->GetFormValue('LastCommentID');
                     if (!is_numeric($LastCommentID))
                        $LastCommentID = $CommentID - 1; // Failsafe back to this new comment if the lastcommentid was not defined properly
                        
                     // Make sure the view knows the current offset
                     $this->Offset = $this->CommentModel->GetOffset($LastCommentID);
                     // Make sure to load all new comments since the page was last loaded by this user
                     $this->CommentData = $this->CommentModel->GetNew($DiscussionID, $LastCommentID);
                     // Load the discussion
                     $this->Discussion = $this->DiscussionModel->GetID($DiscussionID);
                     $this->ControllerName = 'discussion';
                     $this->View = 'comments';
   
                     // Make sure to set the user's discussion watch records
                     $CountComments = $this->CommentModel->GetCount($DiscussionID);
                     $Limit = $this->CommentData->NumRows();
                     $Offset = $CountComments - $Limit;
                     $this->CommentModel->SetWatch($this->Discussion, $Limit, $Offset, $CountComments);
                  }
               } elseif ($Preview) {
                  // If this was a preview click, create a comment shell with the values for this comment
                  $this->Comment = new stdClass();
                  $this->Comment->InsertName = $Session->User->Name;
                  $this->Comment->InsertPhoto = $Session->User->Photo;
                  $this->Comment->DateInserted = Format::Date();
                  $this->Comment->Body = ArrayValue('Body', $FormValues, '');
                  $this->View = 'preview';
               } else {
                  // If this was a draft save, notify the user about the save
                  $this->StatusMessage = sprintf(Gdn::Translate('Draft saved at %s'), Format::Date());
               }
               // And update the draft count
               $UserModel = Gdn::UserModel();
               $CountDrafts = $UserModel->GetAttribute($Session->UserID, 'CountDrafts', 0);
               $MyDrafts = Gdn::Translate('My Drafts');
               if (is_numeric($CountDrafts) && $CountDrafts > 0)
                  $MyDrafts .= '<span>'.$CountDrafts.'</span>';                  
               
               $this->SetJson('MyDrafts', $MyDrafts);
               
            }
         }
      }
      $this->FireEvent('CommentRenderBefore');
      $this->Render();
   }
   
   /// <summary>
   /// Edit a comment.
   /// </summary>
   /// <param name="CommentID" type="int" required="FALSE" default="empty">
   /// The CommentID of the comment to edit.
   /// </param>
   public function EditComment($CommentID = '') {
      $this->Form->SetModel($this->CommentModel);
      $this->Comment = $this->CommentModel->GetID($CommentID);
      $this->View = 'Comment';
      $this->Comment($this->Comment->DiscussionID);
   }
   
   public function Initialize() {
      $this->AddCssFile('vanilla.screen.css');
      parent::Initialize();
   }
}