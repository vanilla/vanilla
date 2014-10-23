<?php if (!defined('APPLICATION')) exit();

/**
 * Handles content moderation
 *
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
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
         if (empty($CheckIDs))
            $CheckIDs = array();
         $CheckIDs = (array)$CheckIDs;
         
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
            if (GetValue('checked', $Check)) {
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
            T('You have selected %1$s in this discussion.'),
            Plural($CountComments, '%s comment', '%s comments')
         ), 'div');
         $ActionMessage = T('Take Action:');
         
         // Can the user delete the comment?
         $DiscussionModel = new DiscussionModel();
         $Discussion = $DiscussionModel->GetID($DiscussionID);
         $PermissionCategory = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
         if ($Session->CheckPermission('Vanilla.Comments.Delete', TRUE, 'Category', GetValue('PermissionCategoryID', $PermissionCategory)))
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
   public static function InformCheckedDiscussions($Sender, $Force = FALSE) {
      $Session = Gdn::Session();
      $HadCheckedDiscussions = $Force;
      if ($Session->IsValid() && Gdn::Request()->IsAuthenticatedPostBack()) {
         // Form was posted, so accept changes to checked items.
         $CheckIDs = GetValue('CheckIDs', $_POST);
         if (empty($CheckIDs))
            $CheckIDs = array();
         $CheckIDs = (array)$CheckIDs;
         
         $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
         if (!is_array($CheckedDiscussions))
            $CheckedDiscussions = array();
            
         // Were there checked discussions before the form was posted?
         $HadCheckedDiscussions |= count($CheckedDiscussions) > 0;

         foreach ($CheckIDs as $Check) {
            if (GetValue('checked', $Check)) {
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
            T('You have selected %1$s.'),
            Plural($CountDiscussions, '%s discussion', '%s discussions')
         ), 'div');
         $ActionMessage = T('Take Action:');
         $ActionMessage .= ' '.Anchor(T('Delete'), 'vanilla/moderation/confirmdiscussiondeletes/', 'Delete Popup');
         $ActionMessage .= ' '.Anchor(T('Move'), 'vanilla/moderation/confirmdiscussionmoves/', 'Move Popup');
         
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
      $PermissionCategory = CategoryModel::Categories($Discussion->CategoryID);
      $this->Permission('Vanilla.Comments.Delete', TRUE, 'Category', GetValue('PermissionCategoryID', $PermissionCategory));
      $this->Title(T('Confirm'));
      
      $CheckedComments = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedComments', array());
      if (!is_array($CheckedComments))
         $CheckedComments = array();
       
      $CommentIDs = array();
      $DiscussionIDs = array();
      foreach ($CheckedComments as $DiscID => $Comments) {
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
         ModerationController::InformCheckedComments($this);
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
      $this->Permission('Vanilla.Discussions.Delete', TRUE, 'Category', 'any');
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
         $PermissionCategory = CategoryModel::Categories(GetValue('CategoryID', $Discussion));
         $CountCheckedDiscussions = $DiscussionData->NumRows();
         if ($Session->CheckPermission('Vanilla.Discussions.Delete', TRUE, 'Category', GetValue('PermissionCategoryID', $PermissionCategory)))
            $AllowedDiscussions[] = $Discussion->DiscussionID;
      }
      $this->SetData('CountAllowed', count($AllowedDiscussions));
      $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
      $this->SetData('CountNotAllowed', $CountNotAllowed);

      if ($this->Form->AuthenticatedPostBack()) {
         // Delete the selected discussions (that the user has permission to delete).
         foreach ($AllowedDiscussions as $DiscussionID) {
            $Deleted = $DiscussionModel->Delete($DiscussionID);
            if ($Deleted) {
               $this->JsonTarget("#Discussion_$DiscussionID", '', 'SlideUp');
            }
         }

         // Clear selections
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', NULL);
         ModerationController::InformCheckedDiscussions($this, TRUE);
      }
      
      $this->Render();
   }

   /**
    * Form to ask for the destination of the move, confirmation and permission check.
    */
   public function ConfirmDiscussionMoves() {
      $Session = Gdn::Session();
      $this->Form = new Gdn_Form();
      $DiscussionModel = new DiscussionModel();

      $this->Title(T('Confirm'));

      $CheckedDiscussions = Gdn::UserModel()->GetAttribute($Session->User->UserID, 'CheckedDiscussions', array());
      if (!is_array($CheckedDiscussions))
         $CheckedDiscussions = array();

      $DiscussionIDs = $CheckedDiscussions;
      $CountCheckedDiscussions = count($DiscussionIDs);
      $this->SetData('CountCheckedDiscussions', $CountCheckedDiscussions);

      // Check for edit permissions on each discussion
      $AllowedDiscussions = array();
      $DiscussionData = $DiscussionModel->SQL->Select('DiscussionID, CategoryID')->From('Discussion')->WhereIn('DiscussionID', $DiscussionIDs)->Get();
      foreach ($DiscussionData->Result() as $Discussion) {
         $Category = CategoryModel::Categories($Discussion->CategoryID);
         if ($Category && $Category['PermsDiscussionsEdit'])
            $AllowedDiscussions[] = $Discussion->DiscussionID;
      }
      $this->SetData('CountAllowed', count($AllowedDiscussions));
      $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
      $this->SetData('CountNotAllowed', $CountNotAllowed);

      if ($this->Form->AuthenticatedPostBack()) {
         // Retrieve the category id
         $CategoryID = $this->Form->GetFormValue('CategoryID');
         $Category = CategoryModel::Categories($CategoryID);

         // User must have add permission on the target category
         if (!$Category['PermsDiscussionsAdd']) {
            throw ForbiddenException('@'.T('You do not have permission to add discussions to this category.'));
         }

         // Iterate and move.
         foreach ($AllowedDiscussions as $DiscussionID) {
            $DiscussionModel->SetField($DiscussionID, 'CategoryID', $CategoryID);
         }

         // Clear selections
         Gdn::UserModel()->SaveAttribute($Session->UserID, 'CheckedDiscussions', FALSE);
         ModerationController::InformCheckedDiscussions($this);
         
         $this->JsonTarget('', '', 'Refresh');
      }

      $this->Render();
   }
}
