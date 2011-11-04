<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['ProfileExtender'] = array(
   'Name' => 'Profile Extender',
   'Description' => 'Add custom fields (like Status, Location, or gamer tags) to member profiles and registration form.',
   'Version' => '2.0',
   'RequiredApplications' => array('Vanilla' => '2.1a1'),
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => array('Plugins.ProfileExtender.Add'),
   'SettingsUrl' => '/dashboard/settings/profileextender',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class ProfileExtenderPlugin extends Gdn_Plugin {
   /**
    * Add the admin config menu option.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', T('Profile Fields'), 'settings/profileextender', 'Garden.Settings.Manage');
   }
   
   /**
    * Render the custom fields on the profile edit user form.
    */
   public function ProfileController_EditMyAccountAfter_Handler($Sender) {
      $this->ProfileFields($Sender);
   }
   
   /**
    * Render the custom profile fields.
    */
   private function ProfileFields($Sender) {
      // Retrieve user's existing profile fields
      $Sender->ProfileFields = C('Plugins.ProfileExtender.ProfileFields', '');
      $Sender->ProfileFields = explode(',', $Sender->ProfileFields);
      $Sender->IsPostBack = $Sender->Form->IsPostBack();
      
      $Sender->UserFields = array();
      if (is_object($Sender->User))
         $Sender->UserFields = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Profile_%', 'Profile_');
               
      $Sender->Render($this->GetView('profilefields.php'));
   }
   
   /**
    * Settings page.
    */
   public function SettingsController_ProfileExtender_Create($Sender) {
      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
         'Plugins.ProfileExtender.ProfileFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
         'Plugins.ProfileExtender.HideFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
         'Plugins.ProfileExtender.TextMaxLength' => array('Control' => 'TextBox'),
      ));

      $Sender->AddSideMenu('settings/profileextender');
      $Sender->SetData('Title', T('Profile Fields'));
      $Sender->ConfigurationModule = $Conf;
      $Conf->RenderAll();
   }
   
   /**
    * Loop through values, trimming them to the specified length.
    */
   private function TrimValues(&$Array, $Length = 200) {
      foreach ($Array as $Key => $Val) {
         $Array[$Key] = substr($Val, 0, $Length);
      }
   }
   
   /**
    * Render the custom fields on the admin edit user form.
    */
   public function UserController_AfterFormInputs_Handler($Sender) {
      echo '<ul>';
      $this->ProfileFields($Sender);
      echo '</ul>';
   }
   
   /**
    * Render the values on the profile page.
    */
   public function UserInfoModule_OnBasicInfo_Handler($Sender) {
      // Render the custom fields
      try {
         $HideFields = (array)explode(',', C('Plugins.ProfileExtender.HideFields'));
         $Fields = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Profile_%', 'Profile_');
         
         foreach ($Fields as $Label => $Value) {
            if (in_array($Label, $HideFields))
               continue;
            
            $Value = Gdn_Format::Links(htmlspecialchars($Value));
            
            echo '<dt class="ProfileExtend Profile'.Gdn_Format::AlphaNumeric($Label).'">'.Gdn_Format::Text($Label).'</dt>';
            echo '<dd class="ProfileExtend Profile'.Gdn_Format::AlphaNumeric($Label).'">'.$Value.'</dd>';
         }
      } catch (Exception $ex) {
         // No errors
      }
   }
   
   /**
    * Save the custom profile fields when saving the user.
    */
   public function UserModel_AfterSave_Handler($Sender) {
      $ValueLimit = Gdn::Session()->CheckPermission('Garden.Moderation.Manage') ? 255 : C('Plugins.ProfileExtender.TextMaxLength', 140);
      $UserID = GetValue('UserID', $Sender->EventArguments);
      $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments);

      $Fields = FALSE;
      if (is_array($FormPostValues)) {
         $CustomLabels = GetValue('CustomLabel', $FormPostValues);
         $CustomValues = GetValue('CustomValue', $FormPostValues);
         if (is_array($CustomLabels) && is_array($CustomValues)) {
            $this->TrimValues($CustomLabels, 50);
            $this->TrimValues($CustomValues, $ValueLimit);
            $Fields = array_combine($CustomLabels, $CustomValues);
         }
         
         // Don't save any empty values or labels
         if (is_array($Fields)) {
            foreach ($Fields as $Field => $Value) {
               if ($Field == '' || $Value == '')
                  $Fields[$Field] = NULL;
            }
         }
      }
      
      // Update UserMeta
      if ($UserID > 0 && is_array($Fields)) {
         $UserModel = new UserModel();
         $UserModel->SetMeta($UserID, $Fields, 'Profile_');
      }
   }
   
   /**
    * Add suggested fields on install & convert CustomProfileField.
    */
   public function Setup() {
      // Import CustomProfileFields settings
      
            
      // New defaults
      if (!C('Plugins.ProfileExtender.ProfileFields', FALSE))
         SaveToConfig('Plugins.ProfileExtender.ProfileFields', 'Location,Facebook,Twitter,Website');
      if (!C('Plugins.ProfileExtender.TextMaxLength', FALSE))
         SaveToConfig('Plugins.ProfileExtender.TextMaxLength', 140);
         
      // Import CustomProfileFields data
      // Gdn::UserModel()->GetAttribute($Sender->User->UserID, 'CustomProfileFields', array());
      // Attributes array -> UserMeta
   }
}