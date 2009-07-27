<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * Messages are used to display (optionally dismissable) information in various parts of the applications.
 */
class MessagesController extends GardenController {
   
   public $Uses = array('Form', 'Gdn_MessageModel');
   
   public function Add() {
      $this->Permission('Garden.Messages.Manage');
      // Use the edit form with no MessageID specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   public function Delete($MessageID = '', $TransientKey = FALSE) {
      $this->Permission('Garden.Messages.Manage');
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $Session = Gdn::Session();
      
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey)) {
         $Message = $this->MessageModel->Delete(array('MessageID' => $MessageID));
         // Reset the message cache
         $this->_SetMessageCache();
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('garden/messages');

      $this->Render();      
   }
   
   public function Dismiss($MessageID = '', $TransientKey = FALSE) {
      $Session = Gdn::Session();
      
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey)) {
         $Prefs = $Session->GetPreference('DismissedMessages', array());
         $Prefs[] = $MessageID;
         $UserModel = Gdn::UserModel();
         $UserModel->SavePreference($Session->UserID, 'DismissedMessages', $Prefs);
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', '/vanilla/discussions'));

      $this->Render();      
   }
   
   public function Edit($MessageID = '') {
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/applications/garden/js/messages.js');
      }
         
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('garden/messages');
      
      // Generate some Controller & Asset data arrays
      $this->LocationData = $this->_GetLocationData();
      $this->AssetData = $this->_GetAssetData();
      
      // Set the model on the form.
      $this->Form->SetModel($this->MessageModel);
      $this->Message = $this->MessageModel->GetID($MessageID);
      
      // Make sure the form knows which item we are editing.
      if (is_numeric($MessageID) && $MessageID > 0)
         $this->Form->AddHidden('MessageID', $MessageID);


      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($this->Message);
      } else {
         if ($MessageID = $this->Form->Save()) {
            // Reset the message cache
            $this->_SetMessageCache();
            
            // Redirect
            $this->StatusMessage = Gdn::Translate('Your changes have been saved.');
            $this->RedirectUrl = Url('garden/messages');
         }
      }
      $this->Render();
   }
   
   public function Index() {
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('garden/messages');
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/js/library/jquery.tablednd.js');
         $this->Head->AddScript('/js/library/jquery.ui.packed.js');
         $this->Head->AddScript('/applications/garden/js/messages.js');
      }
         
      // Load all messages from the db
      $this->MessageData = $this->MessageModel->Get('Sort');
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }   
   
   protected function _GetAssetData() {
      $AssetData = array();
      $AssetData['Content'] = 'Above Main Content';
      $AssetData['Panel'] = 'Below Sidebar';
      return $AssetData;
   }
   
   protected function _GetLocationData() {
      $ControllerData = array();
      $ControllerData['Garden/Settings/Index'] = 'Dashboard';
      $ControllerData['Garden/Profile/Index'] = 'Profile Page';
      $ControllerData['Vanilla/Discussions/Index'] = 'Discussions Page';
      $ControllerData['Vanilla/Discussion/Index'] = 'Comments Page';
      $ControllerData['Base'] = 'Every Page';
      return $ControllerData;
   }

   protected function _SetMessageCache() {
      // Retrieve an array of all controllers that have enabled messages associated
      $Config = Gdn::Factory(Gdn::AliasConfig);
      $Config->Load(PATH_CONF . DS . 'config.php', 'Save');
      $Config->Set('Garden.Messages.Cache', $this->MessageModel->GetEnabledLocations());
      $Config->Save();
   }
}
