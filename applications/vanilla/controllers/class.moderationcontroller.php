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
 * Moderation Controller
 *
 * @package Vanilla
 */
 
/**
 * Handles content moderation
 *
 * @since 2.0.18
 * @package Vanilla
 */
class ModerationController extends VanillaController {
   
   /**
    * Looks at the user's attributes and form postback to see if any comments
    * have been checked for administration, and if so, puts an inform message on
    * the screen to take action.
    */
   public function CheckedComments() {
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      ModerationController::InformCheckedComments($this);
      $this->Render();
   }
   
   /**
    * Looks at the user's attributes and form postback to see if any discussions
    * have been checked for administration, and if so, puts an inform message on
    * the screen to take action.
    */
   public function CheckedDiscussions() {
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $this->DeliveryMethod(DELIVERY_METHOD_JSON);
      ModerationController::InformCheckedDiscussions($this);
      $this->Render();
   }

   /**
    * Looks at the user's attributes and form postback to see if any comments
    * have been checked for administration, and if so, adds an inform message to
    * $Sender to take action.
    */
   public static function InformCheckedComments($Sender) {
      $Session = Gdn::Session();
      $HadCheckedComments = FALSE;
      $TransientKey = GetValue('TransientKey', $_POST);
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey)) {
         // Form was posted, so accept changes to checked items.
         $DiscussionID = GetValue('DiscussionID', $_POST, 0);
         $CheckIDs = GetValue('CheckIDs', $_POST);
         $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
         if (!is_array($CheckedComments))
            $CheckedComments = array();
            
         if (!array_key_exists($DiscussionID, $CheckedComments)) {
            $CheckedComments[$DiscussionID] = array();
         } else {
            // Were there checked comments in this discussion before the form was posted?
            $HadCheckedComments = count($CheckedComments[$DiscussionID]) > 0; 
         }
         foreach ($CheckIDs as $Check) {
            if ($Check['checked'] == 'true') {
               if (!ArrayHasValue($CheckedComments, $Check['checkId']))
                  $CheckedComments[$DiscussionID][] = $Check['checkId'];
            } else {
               RemoveValueFromArray($CheckedComments[$DiscussionID], $Check['checkId']);
            }
         }
         
         if (count($CheckedComments[$DiscussionID]) == 0)
            unset($CheckedComments[$DiscussionID]);
            
         Gdn::UserModel()->SaveAttribute($Session->User->UserID, 'CheckedComments', $CheckedComments);
      } else if ($Session->IsValid()) {
         // No form posted, just retrieve checked items for display
         $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;
         $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
         if (!is_array($CheckedComments))
            $CheckedComments = array();
            
      }

      // Retrieve some information about the checked items
      $CountDiscussions = count($CheckedComments);
      $CountComments = 0;
      foreach ($CheckedComments as $DiscID => $Comments) {
         if ($DiscID == $DiscussionID)
            $CountComments += count($Comments); // Sum of comments in this discussion
      }
      if ($CountComments > 0) {
         $SelectionMessage =  Wrap(sprintf(
            'You have selected %1$s in this discussion.',
            Plural($CountComments, '%s comment', '%s comments')
         ), 'div');
         $ActionMessage = T('Take Action:');
         // Can the user delete the comment?
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($DiscussionID);
         if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', $Discussion->CategoryID))
            $ActionMessage .= ' '.Anchor(T('Delete'), 'vanilla/moderation/confirmcommentdeletes/'.$DiscussionID, 'Delete Popup');
         
         $Sender->EventArguments['SelectionMessage'] = &$SelectionMessage;
         $Sender->EventArguments['ActionMessage'] = &$ActionMessage;
         $Sender->EventArguments['Discussion'] = $Discussion;
         $Sender->FireEvent('BeforeCheckComments');
         $ActionMessage .= ' '.Anchor(T('Cancel'), 'vanilla/moderation/clearcommentselections/'.$DiscussionID.'/{TransientKey}/?Target={SelfUrl}', 'CancelAction');
         
         $Sender->InformMessage(
            $SelectionMessage
            .Wrap($ActionMessage, 'div', array('class' => 'Actions')),
            array(
               'CssClass' => 'NoDismiss',
               'id' => 'CheckSummary'
            )
         );
      } else if ($HadCheckedComments) {
         // Remove the message completely if there were previously checked comments in this discussion, but none now
         $Sender->InformMessage('', array('id' => 'CheckSummary'));
      }
   }
   
   /**
    * Looks at the user's attributes and form postback to see if any discussions
    * have been checked for administration, and if so, adds an inform message to
    * $Sender to take action.
    */
   public static function InformCheckedDiscussions($Sender) {
      $Session = Gdn::Session();
      $HadCheckedDiscussions = FALSE;
      $TransientKey = GetValue('TransientKey', $_POST);
      if ($Session->IsValid() && $Session->ValidateTransientKey($TransientKey)) {
         // Form was posted, so accept changes to checked items.
         $CheckIDs = GetValue('CheckIDs', $_POST);
         $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
         if (!is_array($CheckedDiscussions))
            $CheckedDiscussions = array();
            
         // Were there checked discussions before the form was posted?
         $HadCheckedDiscussions = count($CheckedDiscussions) > 0; 

         foreach ($CheckIDs as $Check) {
            if ($Check['checked'] == 'true') {
               if (!ArrayHasValue($CheckedDiscussions, $Check['checkId']))
                  $CheckedDiscussions[] = $Check['checkId'];
            } else {
               RemoveValueFromArray($CheckedDiscussions, $Check['checkId']);
            }
         }
         
         Gdn::UserModel()->SaveAttribute($Session->User->UserID, 'CheckedDiscussions', $CheckedDiscussions);
      } else if ($Session->IsValid()) {
         // No form posted, just retrieve checked items for display
         $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
         if (!is_array($CheckedDiscussions))
            $CheckedDiscussions = array();
            
      }

      // Retrieve some information about the checked items
      $CountDiscussions = count($CheckedDiscussions);
      if ($CountDiscussions > 0) {
         $SelectionMessage =  Wrap(sprintf(
            'You have selected %1$s.',
            Plural($CountDiscussions, '%s discussion', '%s discussions')
         ), 'div');
         $ActionMessage = T('Take Action:');
         $ActionMessage .= ' '.Anchor(T('Delete'), 'vanilla/moderation/confirmdiscussiondeletes/', 'Delete Popup');
         
         $Sender->EventArguments['SelectionMessage'] = &$SelectionMessage;
         $Sender->EventArguments['ActionMessage'] = &$ActionMessage;
         $Sender->FireEvent('BeforeCheckDiscussions');
         $ActionMessage .= ' '.Anchor(T('Cancel'), 'vanilla/moderation/cleardiscussionselections/{TransientKey}/?Target={SelfUrl}', 'CancelAction');
         
         $Sender->InformMessage(
            $SelectionMessage
            .Wrap($ActionMessage, 'div', array('class' => 'Actions')),
            array(
               'CssClass' => 'NoDismiss',
               'id' => 'CheckSummary'
            )
         );
      } else if ($HadCheckedDiscussions) {
         // Remove the message completely if there were previously checked comments in this discussion, but none now
         $Sender->InformMessage('', array('id' => 'CheckSummary'));
      }
   }
   
   /**
    * Remove all comments checked for administration from the user's attributes.
    */
   public function ClearCommentSelections($DiscussionID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey)) {
         $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
         unset($CheckedComments[$DiscussionID]);
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
      }

      Redirect(GetIncomingValue('Target', '/discussions'));
   }

   /**
    * Remove all discussions checked for administration from the user's attributes.
    */
   public function ClearDiscussionSelections($TransientKey = '') {
      $Session = Gdn::Session();
      if ($Session->ValidateTransientKey($TransientKey))
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', FALSE);

      Redirect(GetIncomingValue('Target', '/discussions'));
   }
   
   /**
    * Form to confirm that the administrator wants to delete the selected
    * comments (and has permission to do so).
    */
   public function ConfirmCommentDeletes($DiscussionID = '') {
      $Session = Gdn::Session();
      $this->Form = new Gdn_Form();
      $DiscussionModel = new DiscussionModel();
      $Discussion = $DiscussionModel->GetID($DiscussionID);
      if (!$Discussion)
         return;
      
      // Verify that the user has permission to perform the delete
      $this->Permission('Vanilla.Comment.Delete', TRUE, 'Category', $Discussion->CategoryID);
      $this->Title(T('Confirm'));
      
      $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
      if (!is_array($CheckedComments))
         $CheckedComments = array();
       
      $CommentIDs = array();
      $DiscussionIDs = array();
      foreach ($CheckedComments as $DiscD => $Comments) {
         foreach ($Comments as $Comment) {
            if (substr($Comment, 0, 11) == 'Discussion_')
               $DiscussionIDs[] = str_replace('Discussion_', '', $Comment);
            else if ($DiscID == $DiscussionID)
               $CommentIDs[] = str_replace('Comment_', '', $Comment);
         }
      }
      $CountCheckedComments = count($CommentIDs);  
      $this->SetData('CountCheckedComments', $CountCheckedComments);
      
      if ($this->Form->AuthenticatedPostBack()) {
         // Delete the selected comments
         $CommentModel = new CommentModel();
         foreach ($CommentIDs as $CommentID) {
            $CommentModel->Delete($CommentID);
         }

         // Clear selections
         unset($CheckedComments[$DiscussionID]);
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
         ModerationController::InformCheckSelections($this);
         $this->RedirectUrl = 'discussions';
      }
      
      $this->Render();
   }
   
   /**
    * Form to confirm that the administrator wants to delete the selected
    * discussions (and has permission to do so).
    */
   public function ConfirmDiscussionDeletes() {
      $Session = Gdn::Session();
      $this->Form = new Gdn_Form();
      $DiscussionModel = new DiscussionModel();
      
      // Verify that the user has permission to perform the deletes
      $this->Permission('Vanilla.Comment.Delete', TRUE, 'Category', 'any');
      $this->Title(T('Confirm'));
      
      $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
      if (!is_array($CheckedDiscussions))
         $CheckedDiscussions = array();

      $DiscussionIDs = $CheckedDiscussions;
      $CountCheckedDiscussions = count($DiscussionIDs);  
      $this->SetData('CountCheckedDiscussions', $CountCheckedDiscussions);
      
      // Check permissions on each discussion to make sure the user has permission to delete them
      $AllowedDiscussions = array();
      $DiscussionData = $DiscussionModel->SQL->Select('DiscussionID, CategoryID')->From('Discussion')->WhereIn('DiscussionID', $DiscussionIDs)->Get();
      foreach ($DiscussionData->Result() as $Discussion) {
         if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', $Discussion->CategoryID))
            $AllowedDiscussions[] = $Discussion->DiscussionID;
      }
      $this->SetData('CountAllowed', count($AllowedDiscussions));
      $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
      $this->SetData('CountNotAllowed', $CountNotAllowed);

      if ($this->Form->AuthenticatedPostBack()) {
         // Delete the selected discussions (that the user has permission to delete).
         foreach ($AllowedDiscussions as $DiscussionID) {
            $DiscussionModel->Delete($DiscussionID);
         }

         // Clear selections
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', FALSE);
         ModerationController::InformCheckedDiscussions($this);
         $this->RedirectUrl = 'discussions';
      }
      
      $this->Render();
   }
}
