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
 * Post Controller
 *
 * @package Vanilla
 */
 
/**
 * Handles posting and editing comments, discussions, and drafts.
 *
 * @since 2.0.0
 * @package Vanilla
 */
class PostController extends VanillaController {
   /**
    * @var Gdn_Form
    */
   public $Form;

   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Form', 'Database', 'CommentModel', 'DiscussionModel', 'DraftModel');
   
   /**
    * Alias for Discussion method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Index() {
      $this->View = 'discussion';
      $this->Discussion();
   }
   
   /**
    * Create or update a discussion.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $CategoryID Unique ID of the category to add the discussion to.
    */
   public function Discussion($CategoryID = '') {
      // Override CategoryID if categories are disabled
      $UseCategories = $this->ShowCategorySelector = (bool)C('Vanilla.Categories.Use');
      if (!$UseCategories) 
         $CategoryID = 0;
         
      // Setup head
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('post.js');
      $this->AddJsFile('autosave.js');
      
      $Session = Gdn::Session();
      
      // Set discussion, draft, and category data
      $DiscussionID = isset($this->Discussion) ? $this->Discussion->DiscussionID : '';
      $DraftID = isset($this->Draft) ? $this->Draft->DraftID : 0;
      $this->CategoryID = isset($this->Discussion) ? $this->Discussion->CategoryID : $CategoryID;
      $Category = CategoryModel::Categories($this->CategoryID);
      if ($Category)
         $this->Category = (object)$Category;
      else
         $this->Category = NULL;

      if ($UseCategories)
			$CategoryData = CategoryModel::Categories();
      
      // Check permission 
      if (isset($this->Discussion)) {
         $Foo = 'bar';
         // Permission to edit
         if ($this->Discussion->InsertUserID != $Session->UserID)
            $this->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', $this->Category->PermissionCategoryID);

         // Make sure that content can (still) be edited.
         $EditContentTimeout = C('Garden.EditContentTimeout', -1);
         $CanEdit = $EditContentTimeout == -1 || strtotime($this->Discussion->DateInserted) + $EditContentTimeout > time();
         if (!$CanEdit)
            $this->Permission('Vanilla.Discussions.Edit', TRUE, 'Category', $this->Category->PermissionCategoryID);

         $this->Title(T('Edit Discussion'));
      } else {
         // Permission to add
         $this->Permission('Vanilla.Discussions.Add');
         $this->Title(T('Start a New Discussion'));
      }
      
      // Set the model on the form
      $this->Form->SetModel($this->DiscussionModel);
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Prep form with current data for editing
         if (isset($this->Discussion)) {
            $this->Form->SetData($this->Discussion);
         } elseif (isset($this->Draft))
            $this->Form->SetData($this->Draft);
         elseif ($this->Category !== NULL)
            $this->Form->SetData(array('CategoryID' => $this->Category->CategoryID));
            
      } else { // Form was submitted
         // Save as a draft?
         $FormValues = $this->Form->FormValues();
         $FormValues = $this->DiscussionModel->FilterForm($FormValues);
         $this->DeliveryType(GetIncomingValue('DeliveryType', $this->_DeliveryType));
         if ($DraftID == 0)
            $DraftID = $this->Form->GetFormValue('DraftID', 0);
            
         $Draft = $this->Form->ButtonExists('Save Draft') ? TRUE : FALSE;
         $Preview = $this->Form->ButtonExists('Preview') ? TRUE : FALSE;
         if (!$Preview) {
            if (!is_object($this->Category) && isset($FormValues['CategoryID']))
               $this->Category = $CategoryData[$FormValues['CategoryID']];

            if (is_object($this->Category)) {
               // Check category permissions.
               if ($this->Form->GetFormValue('Announce', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Announce', TRUE, 'Category', $this->Category->PermissionCategoryID))
                  $this->Form->AddError('You do not have permission to announce in this category', 'Announce');

               if ($this->Form->GetFormValue('Close', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Close', TRUE, 'Category', $this->Category->PermissionCategoryID))
                  $this->Form->AddError('You do not have permission to close in this category', 'Close');

               if ($this->Form->GetFormValue('Sink', '') != '' && !$Session->CheckPermission('Vanilla.Discussions.Sink', TRUE, 'Category', $this->Category->PermissionCategoryID))
                  $this->Form->AddError('You do not have permission to sink in this category', 'Sink');

               if (!$Session->CheckPermission('Vanilla.Discussions.Add', TRUE, 'Category', $this->Category->PermissionCategoryID))
                  $this->Form->AddError('You do not have permission to start discussions in this category', 'CategoryID');
            }

            // Make sure that the title will not be invisible after rendering
            $Name = trim($this->Form->GetFormValue('Name', ''));
            if ($Name != '' && Gdn_Format::Text($Name) == '')
               $this->Form->AddError(T('You have entered an invalid discussion title'), 'Name');
            else {
               // Trim the name.
               $FormValues['Name'] = $Name;
               $this->Form->SetFormValue('Name', $Name);
            }

            if ($this->Form->ErrorCount() == 0) {
               if ($Draft) {
                  $DraftID = $this->DraftModel->Save($FormValues);
                  $this->Form->SetValidationResults($this->DraftModel->ValidationResults());
               } else {
                  $DiscussionID = $this->DiscussionModel->Save($FormValues, $this->CommentModel);
                  $this->Form->SetValidationResults($this->DiscussionModel->ValidationResults());
                  if ($DiscussionID > 0 && $DraftID > 0)
                     $this->DraftModel->Delete($DraftID);
                  if ($DiscussionID == SPAM) {
                     $this->StatusMessage = T('Your post has been flagged for moderation.');
                     $this->Render('Spam');
                     return;
                  }
               }
            }
         } else {
            // If this was a preview click, create a discussion/comment shell with the values for this comment
            $this->Discussion = new stdClass();
            $this->Discussion->Name = $this->Form->GetValue('Name', '');
            $this->Comment = new stdClass();
            $this->Comment->InsertUserID = $Session->User->UserID;
            $this->Comment->InsertName = $Session->User->Name;
            $this->Comment->InsertPhoto = $Session->User->Photo;
            $this->Comment->DateInserted = Gdn_Format::Date();
            $this->Comment->Body = ArrayValue('Body', $FormValues, '');
            
            $this->EventArguments['Discussion'] = &$this->Discussion;
            $this->EventArguments['Comment'] = &$this->Comment;
            $this->FireEvent('BeforeDiscussionPreview');

            if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
               $this->AddAsset('Content', $this->FetchView('preview'));
            } else {
               $this->View = 'preview';
            }
         }
         if ($this->Form->ErrorCount() > 0) {
            // Return the form errors
            $this->ErrorMessage($this->Form->Errors());
         } else if ($DiscussionID > 0 || $DraftID > 0) {
            // Make sure that the ajax request form knows about the newly created discussion or draft id
            $this->SetJson('DiscussionID', $DiscussionID);
            $this->SetJson('DraftID', $DraftID);
            
            if (!$Preview) {
               // If the discussion was not a draft
               if (!$Draft) {
                  // Redirect to the new discussion
                  $Discussion = $this->DiscussionModel->GetID($DiscussionID);
                  $this->EventArguments['Discussion'] = $Discussion;
                  $this->FireEvent('AfterDiscussionSave');
                  
                  if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                     Redirect('/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($Discussion->Name));
                  } else {
                     $this->RedirectUrl = Url('/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($Discussion->Name));
                  }
               } else {
                  // If this was a draft save, notify the user about the save
                  $this->InformMessage(sprintf(T('Draft saved at %s'), Gdn_Format::Date()));
               }
            }
         }
      }
      
      // Add hidden fields for editing
      $this->Form->AddHidden('DiscussionID', $DiscussionID);
      $this->Form->AddHidden('DraftID', $DraftID, TRUE);
      
      $this->FireEvent('BeforeDiscussionRender');
      
      // Render view (posts/discussion.php or post/preview.php)
      $this->Render();
   }
   
   /**
    * Edit a discussion (wrapper for PostController::Discussion). 
    *
    * Will throw an error if both params are blank.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID of the discussion to edit.
    * @param int $DraftID Unique ID of draft discussion to edit.
    */
   public function EditDiscussion($DiscussionID = '', $DraftID = '') {
      if ($DraftID != '') {
         $this->Draft = $this->DraftModel->GetID($DraftID);
         $this->CategoryID = $this->Draft->CategoryID;
      } else {
         $this->Discussion = $this->DiscussionModel->GetID($DiscussionID);
         $this->CategoryID = $this->Discussion->CategoryID;
      }
      
      // Set view and render
      $this->View = 'Discussion';
      $this->Discussion($this->CategoryID);
   }
   
   /**
    * Create or update a comment.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $DiscussionID Unique ID to add the comment to. If blank, this method will throw an error.
    */
   public function Comment($DiscussionID = '') {
      // Get $DiscussionID from RequestArgs if valid
      if ($DiscussionID == '' && count($this->RequestArgs))
         if (is_numeric($this->RequestArgs[0]))
            $DiscussionID = $this->RequestArgs[0];
            
      // If invalid $DiscussionID, get from form.
      $this->Form->SetModel($this->CommentModel);
      $DiscussionID = is_numeric($DiscussionID) ? $DiscussionID : $this->Form->GetFormValue('DiscussionID', 0);
      
      // Set discussion data
      $this->DiscussionID = $DiscussionID;
      $this->Discussion = $Discussion = $this->DiscussionModel->GetID($DiscussionID);      
            
      // Setup head
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('post.js');
      $this->AddJsFile('autosave.js');
      
      // Setup comment model, $CommentID, $DraftID
      $Session = Gdn::Session();
      $CommentID = isset($this->Comment) && property_exists($this->Comment, 'CommentID') ? $this->Comment->CommentID : '';
      $DraftID = isset($this->Comment) && property_exists($this->Comment, 'DraftID') ? $this->Comment->DraftID : '';
      $this->EventArguments['CommentID'] = $CommentID;
      $this->EventArguments['DraftID'] = $DraftID;
      
      // Determine whether we are editing
      $Editing = $CommentID > 0 || $DraftID > 0;
      $this->EventArguments['Editing'] = $Editing;
      
      // If closed, cancel & go to discussion
      if ($Discussion->Closed == 1 && !$Editing)
         Redirect('discussion/'.$DiscussionID.'/'.Gdn_Format::Url($Discussion->Name));
      
      // Add hidden IDs to form
      $this->Form->AddHidden('DiscussionID', $DiscussionID);
      $this->Form->AddHidden('CommentID', $CommentID);
      $this->Form->AddHidden('DraftID', $DraftID, TRUE);
      
      // Check permissions
      if ($Editing) {
         // Permisssion to edit
         if ($this->Comment->InsertUserID != $Session->UserID)
            $this->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID);
            
         // Make sure that content can (still) be edited.
         $EditContentTimeout = C('Garden.EditContentTimeout', -1);
         $CanEdit = $EditContentTimeout == -1 || strtotime($this->Comment->DateInserted) + $EditContentTimeout > time();
         if (!$CanEdit)
            $this->Permission('Vanilla.Comments.Edit', TRUE, 'Category', $Discussion->PermissionCategoryID);

      } else {
         // Permission to add
         $this->Permission('Vanilla.Comments.Add', TRUE, 'Category', $Discussion->PermissionCategoryID);
      }

      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Form was validly submitted
         if (isset($this->Comment))
            $this->Form->SetData($this->Comment);
            
      } else {
         // Save as a draft?
         $FormValues = $this->Form->FormValues();
         $FormValues = $this->CommentModel->FilterForm($FormValues);
         if ($DraftID == 0)
            $DraftID = $this->Form->GetFormValue('DraftID', 0);
         
         $Type = GetIncomingValue('Type');
         $Draft = $Type == 'Draft';
         $this->EventArguments['Draft'] = $Draft;
         $Preview = $Type == 'Preview';
         if ($Draft) {
            $DraftID = $this->DraftModel->Save($FormValues);
            $this->Form->AddHidden('DraftID', $DraftID, TRUE);
            $this->Form->SetValidationResults($this->DraftModel->ValidationResults());
         } else if (!$Preview) {
            $Inserted = !$CommentID;
            $CommentID = $this->CommentModel->Save($FormValues);

            // The comment is now half-saved.
            if (is_numeric($CommentID) && $CommentID > 0) {
               if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
                  $this->CommentModel->Save2($CommentID, $Inserted, TRUE, TRUE);
               } else {
                  $this->JsonTarget('', Url("/vanilla/post/comment2/$CommentID/$Inserted"), 'Ajax');
               }

               // $Discussion = $this->DiscussionModel->GetID($DiscussionID);
               $Comment = $this->CommentModel->GetID($CommentID);

               $this->EventArguments['Discussion'] = $Discussion;
               $this->EventArguments['Comment'] = $Comment;
               $this->FireEvent('AfterCommentSave');
            } elseif ($CommentID === SPAM) {
               $this->StatusMessage = T('Your post has been flagged for moderation.');
            }
            
            $this->Form->SetValidationResults($this->CommentModel->ValidationResults());
            if ($CommentID > 0 && $DraftID > 0)
               $this->DraftModel->Delete($DraftID);
         }
         
         // Handle non-ajax requests first:
         if ($this->_DeliveryType == DELIVERY_TYPE_ALL) {
            if ($this->Form->ErrorCount() == 0) {
               // Make sure that this form knows what comment we are editing.
               if ($CommentID > 0)
                  $this->Form->AddHidden('CommentID', $CommentID);
               
               // If the comment was not a draft
               if (!$Draft) {
                  // Redirect to the new comment.
                  if ($CommentID > 0)
                     Redirect("discussion/comment/$CommentID/#Comment_$CommentID");
                  elseif ($CommentID == SPAM) {
                     $this->SetData('DiscussionUrl', '/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($Discussion->Name));
                     $this->View = 'Spam';
           
                  }
               } elseif ($Preview) {
                  // If this was a preview click, create a comment shell with the values for this comment
                  $this->Comment = new stdClass();
                  $this->Comment->InsertUserID = $Session->User->UserID;
                  $this->Comment->InsertName = $Session->User->Name;
                  $this->Comment->InsertPhoto = $Session->User->Photo;
                  $this->Comment->DateInserted = Gdn_Format::Date();
                  $this->Comment->Body = ArrayValue('Body', $FormValues, '');
                  $this->AddAsset('Content', $this->FetchView('preview'));
               } else {
                  // If this was a draft save, notify the user about the save
                  $this->InformMessage(sprintf(T('Draft saved at %s'), Gdn_Format::Date()));
               }
            }
         } else {
            // Handle ajax-based requests
            if ($this->Form->ErrorCount() > 0) {
               // Return the form errors
               $this->ErrorMessage($this->Form->Errors());
            } else {
               // Make sure that the ajax request form knows about the newly created comment or draft id
               $this->SetJson('CommentID', $CommentID);
               $this->SetJson('DraftID', $DraftID);
               
               if ($Preview) {
                  // If this was a preview click, create a comment shell with the values for this comment
                  $this->Comment = new stdClass();
                  $this->Comment->InsertUserID = $Session->User->UserID;
                  $this->Comment->InsertName = $Session->User->Name;
                  $this->Comment->InsertPhoto = $Session->User->Photo;
                  $this->Comment->DateInserted = Gdn_Format::Date();
                  $this->Comment->Body = ArrayValue('Body', $FormValues, '');
                  $this->View = 'preview';
               } elseif (!$Draft) { // If the comment was not a draft
                  // If Editing a comment 
                  if ($Editing) {
                     // Just reload the comment in question
                     $this->Offset = 1;
                     $this->SetData('CommentData', $this->CommentModel->GetIDData($CommentID), TRUE);
                     // Load the discussion
                     $this->ControllerName = 'discussion';
                     $this->View = 'comments';
                     
                     // Also define the discussion url in case this request came from the post screen and needs to be redirected to the discussion
                     $this->SetJson('DiscussionUrl', Url('/discussion/'.$DiscussionID.'/'.Gdn_Format::Url($this->Discussion->Name).'/#Comment_'.$CommentID));
                  } else {
                     // If the comment model isn't sorted by DateInserted or CommentID then we can't do any fancy loading of comments.
                     $OrderBy = GetValueR('0.0', $this->CommentModel->OrderBy());
                     $Redirect = !in_array($OrderBy, array('c.DateInserted', 'c.CommentID'));
							$DisplayNewCommentOnly = $this->Form->GetFormValue('DisplayNewCommentOnly');

                     if (!$Redirect) {
                        // Otherwise load all new comments that the user hasn't seen yet
                        $LastCommentID = $this->Form->GetFormValue('LastCommentID');
                        if (!is_numeric($LastCommentID))
                           $LastCommentID = $CommentID - 1; // Failsafe back to this new comment if the lastcommentid was not defined properly

                        // Don't reload the first comment if this new comment is the first one.
                        $this->Offset = $LastCommentID == 0 ? 1 : $this->CommentModel->GetOffset($LastCommentID);
                        // Do not load more than a single page of data...
                        $Limit = C('Vanilla.Comments.PerPage', 50);

                        // Redirect if the new new comment isn't on the same page.
                        $Redirect |= !$DisplayNewCommentOnly && PageNumber($this->Offset, $Limit) != PageNumber($Discussion->CountComments - 1, $Limit);
                     }

                     if ($Redirect) {
                        // The user posted a comment on a page other than the last one, so just redirect to the last page.
                        $this->RedirectUrl = Gdn::Request()->Url("discussion/comment/$CommentID/#Comment_$CommentID", TRUE);
                        $this->CommentData = NULL;
                     } else {
                        // Make sure to load all new comments since the page was last loaded by this user
								if ($DisplayNewCommentOnly)
									$this->SetData('CommentData', $this->CommentModel->GetIDData($CommentID), TRUE);
								else 
									$this->SetData('CommentData', $this->CommentModel->GetNew($DiscussionID, $LastCommentID), TRUE);

                        $this->SetData('NewComments', TRUE);
                        $this->ControllerName = 'discussion';
                        $this->View = 'comments';
                     }
                     
                     // Make sure to set the user's discussion watch records
                     $CountComments = $this->CommentModel->GetCount($DiscussionID);
                     $Limit = is_object($this->CommentData) ? $this->CommentData->NumRows() : $Discussion->CountComments;
                     $Offset = $CountComments - $Limit;
                     $this->CommentModel->SetWatch($this->Discussion, $Limit, $Offset, $CountComments);
                  }
               } else {
                  // If this was a draft save, notify the user about the save
                  $this->InformMessage(sprintf(T('Draft saved at %s'), Gdn_Format::Date()));
               }
               // And update the draft count
               $UserModel = Gdn::UserModel();
               $CountDrafts = $UserModel->GetAttribute($Session->UserID, 'CountDrafts', 0);
               $this->SetJson('MyDrafts', T('My Drafts'));
               $this->SetJson('CountDrafts', $CountDrafts);
            }
         }
      }
      
      // Include data for FireEvent
      if (property_exists($this,'Discussion'))
         $this->EventArguments['Discussion'] = $this->Discussion;
      if (property_exists($this,'Comment'))
         $this->EventArguments['Comment'] = $this->Comment;
         
      $this->FireEvent('BeforeCommentRender');
      
      // Render default view
      $this->Render();
   }
   
   /**
    * Triggers saving the extra info about a comment
    * like notifications and unread totals.
    *
    * @since 2.0.?
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment.
    * @param bool $Inserted
    */
   public function Comment2($CommentID, $Inserted = FALSE) {
      $this->CommentModel->Save2($CommentID, $Inserted);
   }
   
   /**
    * Edit a comment (wrapper for PostController::Comment).
    *
    * Will throw an error if both params are blank.
    *
    * @since 2.0.0
    * @access public
    * 
    * @param int $CommentID Unique ID of the comment to edit.
    * @param int $DraftID Unique ID of the draft to edit.
    */
   public function EditComment($CommentID = '', $DraftID = '') {
      if (is_numeric($CommentID) && $CommentID > 0) {
         $this->Form->SetModel($this->CommentModel);
         $this->Comment = $this->CommentModel->GetID($CommentID);
      } else {
         $this->Form->SetModel($this->DraftModel);
         $this->Comment = $this->DraftModel->GetID($DraftID);
      }
      $this->View = 'Comment';
      $this->Comment($this->Comment->DiscussionID);
   }
   
   /**
    * Include CSS for all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      $this->AddCssFile('vanilla.css');
   }
}