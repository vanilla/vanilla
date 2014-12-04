<?php if (!defined('APPLICATION')) exit();

/**
 * Messages are used to display (optionally dismissable) information in various parts of the applications.
 *
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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
      $this->Permission('Garden.Community.Manage');
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
      $this->Permission('Garden.Community.Manage');
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
      $this->AddJsFile('jquery.autosize.min.js');
      $this->AddJsFile('messages.js');

      $this->Permission('Garden.Community.Manage');
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

      $CategoriesData = CategoryModel::Categories();
      $Categories = array();
      foreach ($CategoriesData as $Row) {
         if ($Row['CategoryID'] < 0)
            continue;

         $Categories[$Row['CategoryID']] = str_repeat('&nbsp;&nbsp;&nbsp;', max(0, $Row['Depth'] - 1)).$Row['Name'];
      }
      $this->SetData('Categories', $Categories);

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
      $this->Permission('Garden.Community.Manage');
      $this->AddSideMenu('dashboard/message');
      $this->AddJsFile('jquery.autosize.min.js');
      $this->AddJsFile('jquery.tablednd.js');
      $this->AddJsFile('jquery-ui.js');
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
      $ControllerData['Vanilla/Post/Discussion'] = T('New Discussion Form');
      $ControllerData['Dashboard/Entry/SignIn'] = T('Sign In');
      $ControllerData['Dashboard/Entry/Register'] = T('Registration');
      // 2011-09-09 - mosullivan - No longer allowing messages in dashboard
      // $ControllerData['Dashboard/Settings/Index'] = 'Dashboard Home';
      $this->EventArguments['ControllerData'] = &$ControllerData;
      $this->FireEvent('AfterGetLocationData');
      return $ControllerData;
   }
}
