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
   
   public $Uses = array('Database', 'Form', 'Gdn_ActivityModel');
   
   public function Initialize() {
      $this->Head = new HeadModule($this);
      $this->AddJsFile('js/library/jquery.js');
      $this->AddJsFile('js/library/jquery.livequery.js');
      $this->AddJsFile('js/library/jquery.form.js');
      $this->AddJsFile('js/library/jquery.popup.js');
      $this->AddJsFile('js/library/jquery.menu.js');
      $this->AddJsFile('js/library/jquery.gardenhandleajaxform.js');
      $this->AddJsFile('js/global.js');
      
      $this->AddCssFile('style.css');
      $GuestModule = new GuestModule($this);
      $GuestModule->MessageCode = "It looks like you're new here. If you want to take part in the discussions, click one of these buttons!";
      $this->AddModule($GuestModule);
      parent::Initialize();
   }
   public function Index() {
      $this->AddJsFile('activity.js');
      $this->Title(T('Recent Activity'));
         
      $Session = Gdn::Session();
      $this->ActivityData = $this->ActivityModel->Get();
      if ($this->ActivityData->NumRows() > 0) {
         $ActivityData = $this->ActivityData->ResultArray();
         $ActivityIDs = ConsolidateArrayValuesByKey($ActivityData, 'ActivityID');
         $this->CommentData = $this->ActivityModel->GetComments($ActivityIDs);
      } else {
         $this->CommentData = FALSE;
      }
      
      $this->View = 'all';
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
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         Redirect(GetIncomingValue('Return', Gdn_Url::WebRoot()));
      }
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
         if ($Body != '' && is_numeric($ActivityID) && $ActivityID > 0) {
            $NewActivityID = $this->ActivityModel->Add(
               $Session->UserID,
               'ActivityComment',
               $Body,
               '',
               $ActivityID,
               '',
               TRUE
            );
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
         // And render
         $this->Render();         
      }
   }   
}