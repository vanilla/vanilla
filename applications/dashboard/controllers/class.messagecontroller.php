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
 * Message Controller
 * @package Dashboard
 */

/**
 * Messages are used to display (optionally dismissable) information in various parts of the applications.
 *
 * @package Dashboard
 */
class MessageController extends DashboardController {
   /** @var array Objects to prep. */
   public $Uses = array('Form', 'MessageModel');
   
   /**
    * Form to create a new message.
    *
    * @since 2.0.0
    * @access public
    */
   public function Add() {
      $this->Permission('Garden.Messages.Manage');
      // Use the edit form with no MessageID specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   /**
    * Delete a message.
    *
    * @since 2.0.0
    * @access public
    */
   public function Delete($MessageID = '', $TransientKey = FALSE) {
      $this->Permission('Garden.Messages.Manage');
      $this->DeliveryType(DELIVERY_TYPE_BOOL);
      $Session = Gdn::Session();
      
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey)) {
         $Message = $this->MessageModel->Delete(array('MessageID' => $MessageID));
         // Reset the message cache
         $this->MessageModel->SetMessageCache();
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect('dashboard/message');

      $this->Render();      
   }
   
   /**
    * Dismiss a message (per user).
    *
    * @since 2.0.0
    * @access public
    */
   public function Dismiss($MessageID = '', $TransientKey = FALSE) {
      $Session = Gdn::Session();
      
      if ($TransientKey !== FALSE && $Session->ValidateTransientKey($TransientKey)) {
         $Prefs = $Session->GetPreference('DismissedMessages', array());
         $Prefs[] = $MessageID;
         $Session->SetPreference('DismissedMessages', $Prefs);
      }
      
      if ($this->_DeliveryType === DELIVERY_TYPE_ALL)
         Redirect(GetIncomingValue('Target', '/discussions'));

      $this->Render();      
   }
   
   /**
    * Form to edit an existing message.
    *
    * @since 2.0.0
    * @access public
    */
   public function Edit($MessageID = '') {
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('messages.js');
         
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('dashboard/message');
      
      // Generate some Controller & Asset data arrays
      $this->SetData('Locations', $this->_GetLocationData());
      $this->AssetData = $this->_GetAssetData();
      
      // Set the model on the form.
      $this->Form->SetModel($this->MessageModel);
      $this->Message = $this->MessageModel->GetID($MessageID);
      $this->Message = $this->MessageModel->DefineLocation($this->Message);
      
      // Make sure the form knows which item we are editing.
      if (is_numeric($MessageID) && $MessageID > 0)
         $this->Form->AddHidden('MessageID', $MessageID);


      // If seeing the form for the first time...
      if (!$this->Form->AuthenticatedPostBack()) {
         $this->Form->SetData($this->Message);
      } else {
         if ($MessageID = $this->Form->Save()) {
            // Reset the message cache
            $this->MessageModel->SetMessageCache();
            
            // Redirect
            $this->InformMessage(T('Your changes have been saved.'));
            //$this->RedirectUrl = Url('dashboard/message');
         }
      }
      $this->Render();
   }
   
   /**
    * Main page. Show all messages.
    *
    * @since 2.0.0
    * @access public
    */
   public function Index() {
      $this->Permission('Garden.Messages.Manage');
      $this->AddSideMenu('dashboard/message');
      $this->AddJsFile('jquery.autogrow.js');
      $this->AddJsFile('jquery.tablednd.js');
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->AddJsFile('messages.js');
      $this->Title(T('Messages'));
         
      // Load all messages from the db
      $this->MessageData = $this->MessageModel->Get('Sort');
      $this->Render();
   }
   
   /**
    * Always triggers first. Highlight path.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }   
   
   /**
    * Get descriptions of asset locations on page.
    *
    * @since 2.0.0
    * @access protected
    */
   protected function _GetAssetData() {
      $AssetData = array();
      $AssetData['Content'] = T('Above Main Content');
      $AssetData['Panel'] = T('Below Sidebar');
      $this->EventArguments['AssetData'] = &$AssetData;
      $this->FireEvent('AfterGetAssetData');
      return $AssetData;
   }
   
   /**
    * Get descriptions of asset locations across site.
    *
    * @since 2.0.0
    * @access protected
    */
   protected function _GetLocationData() {
      $ControllerData = array();
      $ControllerData['[Base]'] = T('All Pages');
      $ControllerData['[NonAdmin]'] = T('All Forum Pages');
      // 2011-09-09 - mosullivan - No longer allowing messages in dashboard
      // $ControllerData['[Admin]'] = 'All Dashboard Pages';
      $ControllerData['Dashboard/Profile/Index'] = T('Profile Page');
      $ControllerData['Vanilla/Discussions/Index'] = T('Discussions Page');
      $ControllerData['Vanilla/Discussion/Index'] = T('Comments Page');
      $ControllerData['Dashboard/Entry/SignIn'] = T('Sign In');
      // 2011-09-09 - mosullivan - No longer allowing messages in dashboard
      // $ControllerData['Dashboard/Settings/Index'] = 'Dashboard Home';
      $this->EventArguments['ControllerData'] = &$ControllerData;
      $this->FireEvent('AfterGetLocationData');
      return $ControllerData;
   }
}
