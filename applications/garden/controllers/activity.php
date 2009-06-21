<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class ActivityController extends GardenController {
   
   public $Uses = array('Database', 'Form', 'ActivityModel', 'Html');
   
   public function Index() {
      if ($this->Head)
         $this->Head->AddScript('/applications/garden/js/activity.js');
         
      $Session = Gdn::Session();
      $this->ActivityData = $this->ActivityModel->Get();
      if ($this->ActivityData->NumRows() > 0) {
         $FirstActivityID = $this->ActivityData->LastRow()->ActivityID;
         $LastActivityID = $this->ActivityData->FirstRow()->ActivityID;
         $this->CommentData = $this->ActivityModel->GetComments($FirstActivityID, $LastActivityID);
      } else {
         $this->CommentData = FALSE;
      }
      
      $GuestModule = new GuestModule($this);
      $GuestModule->MessageCode = "Just checking up on recent activity? When you're ready to get involved, click one of these buttons!";
      $this->AddModule($GuestModule);
      
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
         Redirect(GetIncomingValue('Return', Url::WebRoot()));
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
               $ActivityID
            );
         }
      }
      // Redirect back to the sending location if this isn't an ajax request
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
         Redirect($this->Form->GetValue('Return', Url::WebRoot()));
      } else {
         $this->Html = new Html();
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