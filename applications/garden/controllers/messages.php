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
   
   public $Uses = array('Form');
   
   public function Index() {
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('garden/messages');
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/applications/garden/js/messages.js');
      }
         
      // Load all messages from the db
      $MessageModel = new Model('Message');
      $this->MessageData = $MessageModel->Get();
      $this->Render();
   }
   
   public function Add() {
      $this->Permission('Garden.Messages.Manage');
      // Use the edit form with no MessageID specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   public function Edit($MessageID = '') {
      if ($this->Head) {
         $this->Head->AddScript('js/library/jquery.autogrow.js');
         $this->Head->AddScript('/applications/garden/js/messages.js');
      }
         
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('garden/messages');
      $MessageModel = new Model('Message');
      $this->Message = $MessageModel->GetWhere(array('MessageID' => $MessageID));
      $this->Message = $this->Message->NumRows() == 0 ? FALSE : $this->Message->FirstRow();
      
      // Generate some Controller & Asset data arrays
      $this->ControllerData = $this->_GetControllerData();
      $this->AssetData = $this->_GetAssetData();
      
      // Set the model on the form.
      $this->Form->SetModel($MessageModel);
      
      // Make sure the form knows which item we are editing.
      if (is_numeric($MessageID) && $MessageID > 0)
         $this->Form->AddHidden('MessageID', $MessageID);

      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         $this->Form->SetData($this->Message);
      } else {
         if ($MessageID = $this->Form->Save()) {
            $this->StatusMessage = Gdn::Translate('Your changes have been saved.');
            $this->RedirectUrl = Url('garden/messages');
         }
      }
      $this->Render();
   }
   
   protected function _GetControllerData() {
      $ControllerData = array();
      $ControllerData['Settings'] = 'Dashboard';
      $ControllerData['Base'] = 'Every Page';
      $ControllerData['Discussions'] = 'Discussions Page';
      $ControllerData['Discussion'] = 'Comments Page';
      return $ControllerData;
   }
   
   protected function _GetAssetData() {
      $AssetData = array();
      $AssetData['Content'] = 'Above Main Content';
      $AssetData['Panel'] = 'Below Sidebar';
      return $AssetData;
   }
   
   public function Delete($MessageID = '', $TransientKey = FALSE) {
      $this->Permission('Garden.Messages.Manage');
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $Session = Gdn::Session();
      
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey)) {
         $MessageModel = new Model('Message');
         $Message = $MessageModel->Delete(array('MessageID' => $MessageID));
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('garden/messages');

      $this->Render();      
   }
   
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }   
}
