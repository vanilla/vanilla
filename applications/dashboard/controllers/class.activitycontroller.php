<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ActivityController extends Gdn_Controller {
   
   public $Uses = array('Database', 'Form', 'ActivityModel');
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('jquery.js');
      $this->AddJsFile('jquery.livequery.js');
      $this->AddJsFile('jquery.form.js');
      $this->AddJsFile('jquery.popup.js');
      $this->AddJsFile('jquery.gardenhandleajaxform.js');
      $this->AddJsFile('global.js');
      
      $this->AddCssFile('style.css');
      $GuestModule = new GuestModule($this);
      $this->AddModule($GuestModule);
      parent::Initialize();
   }
   
   public function Index($RoleID = '') {
      $this->Permission('Garden.Activity.View');
      
      // Limit to specific RoleIDs?
      if ($RoleID != '')
         $RoleID = explode(',', $RoleID);
         
      $this->AddJsFile('activity.js');
      $this->Title(T('Recent Activity'));
         
      $Session = Gdn::Session();
      $Comment = $this->Form->GetFormValue('Comment');
      $this->CommentData = FALSE;
      if ($Session->UserID > 0 && $this->Form->AuthenticatedPostBack() && !StringIsNullOrEmpty($Comment)) {
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
            $this->ActivityData = $this->ActivityModel->GetWhere('ActivityID', $NewActivityID);
            $this->View = 'activities';
         }
      } else {
         $this->ActivityData = is_array($RoleID) ? $this->ActivityModel->GetForRole($RoleID) : $this->ActivityModel->Get();
         if ($this->ActivityData->NumRows() > 0) {
            $ActivityData = $this->ActivityData->ResultArray();
            $ActivityIDs = ConsolidateArrayValuesByKey($ActivityData, 'ActivityID');
            $this->CommentData = $this->ActivityModel->GetComments($ActivityIDs);
         }
         $this->View = 'all';
      }
      
      $this->Render();
   }
   
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

      $this->ControllerName = 'Home';
      $this->View = 'FileNotFound';
      $this->Render();
   }
   
   public function Comment() {
      $Session = Gdn::Session();
      $this->Form->SetModel($this->ActivityModel);
      $NewActivityID = 0;
      if ($this->Form->AuthenticatedPostBack()) {
         $Body = $this->Form->GetValue('Body', '');
         $ActivityID = $this->Form->GetValue('ActivityID', '');
         if (is_numeric($ActivityID) && $ActivityID > 0) {
            $NewActivityID = $this->ActivityModel->Add(
               $Session->UserID,
               'ActivityComment',
               $Body,
               '',
               $ActivityID,
               '',
               TRUE
            );
            $this->Form->SetValidationResults($this->ActivityModel->ValidationResults());
            if ($this->Form->ErrorCount() > 0)
               $this->StatusMessage = $this->Form->Errors();
         }
      }
      // Redirect back to the sending location if this isn't an ajax request
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         Redirect($this->Form->GetValue('Return', Gdn_Url::WebRoot()));
      } else {
         // Load the newly added comment
         $this->Comment = $this->ActivityModel->GetID($NewActivityID);
         $this->Comment->ActivityType .= ' Hidden'; // Hide it so jquery can reveal it
         // Set it in the appropriate view
         $this->View = 'comment';
      }

      // And render
      $this->Render();
   }   
}