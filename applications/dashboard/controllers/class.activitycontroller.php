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
 * Activity Controller
 *
 * @package Dashboard
 */
 
/**
 * Manages the activity stream.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class ActivityController extends Gdn_Controller {
   /**
    * Models to include.
    * 
    * @since 2.0.0
    * @access public
    * @var array
    */
   public $Uses = array('Database', 'Form', 'ActivityModel');
   
   public function __get($Name) {
      switch ($Name) {
         case 'CommentData':
            Deprecated('ActivityController->CommentData', "ActivityController->Data('Activities')");
            $Result = new Gdn_DataSet(array(), DATASET_TYPE_OBJECT);
            return $Result;
         case 'ActivityData':
            Deprecated('ActivityController->ActivityData', "ActivityController->Data('Activities')");
            $Result = new Gdn_DataSet($this->Data('Activities'), DATASET_TYPE_ARRAY);
            $Result->DatasetType(DATASET_TYPE_OBJECT);
            return $Result;
      }
   }
   
   /**
    * Include JS, CSS, and modules used by all methods.
    *
    * Always called by dispatcher before controller's requested method.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      
      // Add Modules
      $this->AddModule('GuestModule');
      $this->AddModule('SignedInModule');
      
      parent::Initialize();
   }
   
   /**
    * Display a single activity item & comments.
    * 
    * Email notifications regarding activities link to this method.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $ActivityID Unique ID of activity item to display.
    */
   public function Item($ActivityID = 0) {
      $this->AddJsFile('activity.js');
      $this->Title(T('Activity Item'));

      if (!is_numeric($ActivityID) || $ActivityID < 0)
         $ActivityID = 0;
         
      $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $ActivityID);
      $this->SetData('Comments', $this->ActivityModel->GetComments(array($ActivityID)));
      $this->SetData('ActivityData', $this->ActivityData);
      
      $this->Render();
   }
   
   /**
    * Default activity stream.
    * 
    * @since 2.0.0
    * @access public
    * @todo Validate comment length rather than truncating.
    * 
    * @param int $RoleID Unique ID of role to limit activity to.
    * @param int $Offset Number of activity items to skip.
    */
   public function Index($RoleID = '', $Page = FALSE) {
      $this->Permission('Garden.Activity.View');
      
      // Limit to specific RoleIDs?
      if ($RoleID == 0)
         $RoleID = '';
         
      if ($RoleID != '')
         $RoleID = explode(',', $RoleID);
         
      // Which page to load
      list($Offset, $Limit) = OffsetLimit($Page, 30);
      $Offset = is_numeric($Offset) ? $Offset : 0;
      if ($Offset < 0)
         $Offset = 0;
      
      // Page meta.
      $this->AddJsFile('activity.js');
      $this->Title(T('Recent Activity'));
      
      // Comment submission 
      $Session = Gdn::Session();
      $Comment = $this->Form->GetFormValue('Comment');
      $Activities = array();
      
      if ($Session->UserID > 0 && $this->Form->AuthenticatedPostBack() && !StringIsNullOrEmpty($Comment)) {
         $this->Permission('Garden.Profiles.Edit');
         $Comment = substr($Comment, 0, 1000); // Limit to 1000 characters...
         
         // Update About if necessary
         $ActivityType = 'WallComment';
         $NewActivityID = $this->ActivityModel->Add(
            $Session->UserID,
            $ActivityType,
            $Comment);
         
         if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            Redirect('activity');
         } else {
            // Load just the single new comment
            $this->HideActivity = TRUE;
            $Activities = $this->ActivityModel->GetWhere('ActivityID', $NewActivityID)->ResultArray();
            $this->View = 'activities';
         }
      } else {
         $Activities = $this->ActivityModel->Get('', $Offset, $Limit)->ResultArray();
         $this->View = 'all';
      }
      $this->ActivityModel->JoinComments($Activities);
      $this->SetData('Activities', $Activities);

      $this->CanonicalUrl(Url('/activity', TRUE));
      
      $this->Render();
   }
   
   public function DeleteComment($ID, $TK, $Target = '') {
      $Session = Gdn::Session();
      
      if (!$Session->ValidateTransientKey($TK))
         throw PermissionException();
      
      $Comment = $this->ActivityModel->GetComment($ID);
      if (!$ID)
         throw NotFoundException();
      
      if ($Session->CheckPermission('Garden.Activity.Delete') || $Comment['InsertUserID'] = $Session->UserID) {
         $this->ActivityModel->DeleteComment($ID);
      } else {
         throw PermissionException();
      }
      
      if ($this->DeliveryType() === DELIVERY_TYPE_ALL)
         Redirect($Target);
      
      $this->Render('Blank', 'Utility', 'Dashboard');
   }
   
   /**
    * Delete an activity item.
    * 
    * @since 2.0.0
    * @access public
    * 
    * @param int $ActivityID Unique ID of item to delete.
    * @param string $TransientKey Verify intent.
    */
   public function Delete($ActivityID = '', $TransientKey = '') {
      $Session = Gdn::Session();
      if (
         $Session->ValidateTransientKey($TransientKey)
         && is_numeric($ActivityID)
      ) {
         $HasPermission = $Session->CheckPermission('Garden.Activity.Delete');
         if (!$HasPermission) {
            $Activity = $this->ActivityModel->GetID($ActivityID);
            $HasPermission = $Activity->InsertUserID == $Session->UserID;
         }
         if ($HasPermission)
            $this->ActivityModel->Delete($ActivityID);
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', $this->SelfUrl));
      
      // Still here? Getting a 404.
      $this->ControllerName = 'Home';
      $this->View = 'FileNotFound';
      $this->Render();
   }
   
   /**
    * Comment on an activity item.
    * 
    * @since 2.0.0
    * @access public
    */
   public function Comment() {
      $this->Permission('Garden.Profiles.Edit');
      
      $Session = Gdn::Session();
      $this->Form->SetModel($this->ActivityModel);
      $NewActivityID = 0;
      
      // Form submitted
      if ($this->Form->AuthenticatedPostBack()) {
         $Body = $this->Form->GetValue('Body', '');
         $ActivityID = $this->Form->GetValue('ActivityID', '');
         if (is_numeric($ActivityID) && $ActivityID > 0) {
            $ActivityComment = array(
                'ActivityID' => $ActivityID,
                'Body' => $Body,
                'Format' => 'Text');
            
            $ID = $this->ActivityModel->Comment($ActivityComment);
            $this->Form->SetValidationResults($this->ActivityModel->ValidationResults());
            if ($this->Form->ErrorCount() > 0)
               $this->ErrorMessage($this->Form->Errors());
         }
      }
      
      // Redirect back to the sending location if this isn't an ajax request
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         Redirect($this->Form->GetValue('Return', Gdn_Url::WebRoot()));
      } else {
         // Load the newly added comment.
         $this->SetData('Comment', $this->ActivityModel->GetComment($ID));
         
         // Set it in the appropriate view.
         $this->View = 'comment';
      }

      // And render
      $this->Render();
   }   
}