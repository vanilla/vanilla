<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

$PluginInfo['ProfileExtender'] = array(
   'Name' => 'Profile Extender',
   'Description' => 'Add fields (like status, location, or gamer tags) to profiles and registration.',
   'Version' => '2.0.1',
   'RequiredApplications' => array('Vanilla' => '2.1a1'),
   'MobileFriendly' => TRUE,
   'RegisterPermissions' => array('Plugins.ProfileExtender.Add'),
   'SettingsUrl' => '/dashboard/settings/profileextender',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Author' => "Matt Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Plugin to add additional fields to user profiles.
 *
 * Based on Mark O'Sullivan's (mark@vanillaforums.com) CustomProfileFields plugin.
 * When enabled, this plugin will import content from CustomProfileFields.
 */
class ProfileExtenderPlugin extends Gdn_Plugin {
   /** @var array */
   public $MagicLabels = array('Twitter', 'Google+', 'Real Name');
   
   /**
    * Add the Dashboard menu item.
    */
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', T('Profile Fields'), 'settings/profileextender', 'Garden.Settings.Manage');
   }
   
   /**
    * Add fields to registration forms.
    */
   public function EntryController_RegisterBeforePassword_Handler($Sender) {
      $Sender->RegistrationFields = $this->GetFields('Registration');               
      include($this->GetView('registrationfields.php'));
   }
   
   /**
    * Get array of current fields.
    *
    * @param string $Type Profile, Registration, or Hide
    */
   public function GetFields($Type = 'Profile') {
      return array_filter((array)explode(',', C('Plugins.ProfileExtender.'.$Type.'Fields', '')));
   }
   
   /**
    * Special manipulations.
    */
   public function ParseSpecialFields($Fields = array()) {
      foreach ($Fields as $Label => $Value) {
         switch ($Label) {
            case 'Twitter':
               $Fields['Twitter'] = Anchor($Value, 'http://twitter.com/'.$Value);
               break;
            case 'Google+':
               $Fields['Google+'] = Anchor('Google+', $Value, '', array('rel' => 'me'));
               break;
            case 'Real Name':
               $Fields['Real Name'] = Wrap(htmlspecialchars($Value), 'span', array('itemprop' => 'name'));
               break;
         }
      }
      
      return $Fields;
   }
      
   /**
    * Add fields to edit profile form.
    */
   public function ProfileController_EditMyAccountAfter_Handler($Sender) {
      $this->ProfileFields($Sender);
   }
   
   /**
    * Display custom profile fields.
    *
    * @access private
    */
   private function ProfileFields($Sender) {
      // Retrieve user's existing profile fields
      $this->ProfileFields = $this->GetFields('Profile');
      $this->IsPostBack = $Sender->Form->IsPostBack();
      
      $this->UserFields = array();
      if (is_object($Sender->User))
         $this->UserFields = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Profile.%', 'Profile.');
      
      include($this->GetView('profilefields.php'));
   }
   
   /**
    * Settings page.
    */
   public function SettingsController_ProfileExtender_Create($Sender) {
      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
         'Plugins.ProfileExtender.ProfileFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
         'Plugins.ProfileExtender.RegistrationFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
         'Plugins.ProfileExtender.HideFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
         'Plugins.ProfileExtender.TextMaxLength' => array('Control' => 'TextBox'),
      ));

      $Sender->AddSideMenu('settings/profileextender');
      $Sender->SetData('Title', T('Profile Fields'));
      $Sender->ConfigurationModule = $Conf;
      $Conf->RenderAll();
   }
   
   /**
    * Trim values in array to specified length.
    *
    * @access private
    */
   private function TrimValues(&$Array, $Length = 140) {
      foreach ($Array as $Key => $Val) {
         $Array[$Key] = substr($Val, 0, $Length);
      }
   }
   
   /**
    * Display custom fields on Edit User form.
    */
   public function UserController_AfterFormInputs_Handler($Sender) {
      echo '<ul>';
      $this->ProfileFields($Sender);
      echo '</ul>';
   }
   
   /**
    * Display custom fields on Profile.
    */
   public function UserInfoModule_OnBasicInfo_Handler($Sender) {
      try {
         // Get the custom fields
         $Fields = Gdn::UserModel()->GetMeta($Sender->User->UserID, 'Profile.%', 'Profile.');
         
         // Reorder the custom fields
         // Use order of Plugins.ProfileExtender.ProfileFields first
         $Listed = $this->GetFields('Profile');
         $Fields1 = array();
         foreach ($Listed as $FieldName) {
            if (isset($Fields[$FieldName]))
               $Fields1[$FieldName] = $Fields[$FieldName];
         }
         // Then append the user's arbitrary custom fields (if they have any) alphabetically by label
         $Fields2 = array_diff_key($Fields, $Listed);
         ksort($Fields2);
         $Fields = array_merge($Fields1, $Fields2);
         
         // Import from CustomProfileFields if available
         if (!count($Fields) && is_object($Sender->User) && C('Plugins.CustomProfileFields.SuggestedFields', FALSE)) {
			   $Fields = Gdn::UserModel()->GetAttribute($Sender->User->UserID, 'CustomProfileFields', FALSE);
			   if ($Fields) {
			      // Migrate to UserMeta & delete original
			      Gdn::UserModel()->SetMeta($Sender->User->UserID, $Fields, 'Profile.');
			      Gdn::UserModel()->SaveAttribute($Sender->User->UserID, 'CustomProfileFields', FALSE);
			   }
         }
         
         // Send them off for magic formatting
         $Fields = $this->ParseSpecialFields($Fields);
         
         // Display all non-hidden fields
         $HideFields = $this->GetFields('Hide');
         foreach ($Fields as $Label => $Value) {
            if (in_array($Label, $HideFields))
               continue;
            if (!in_array($Label, $this->MagicLabels))
               $Value = Gdn_Format::Links(htmlspecialchars($Value));
            echo ' <dt class="ProfileExtend Profile'.Gdn_Format::AlphaNumeric($Label).'">'.Gdn_Format::Text($Label).'</dt> ';
            echo ' <dd class="ProfileExtend Profile'.Gdn_Format::AlphaNumeric($Label).'">'.$Value.'</dd> ';
         }
      } catch (Exception $ex) {
         // No errors
      }
   }
   
   /**
    * Save custom profile fields when saving the user.
    */
   public function UserModel_AfterSave_Handler($Sender) {
      // Confirm we have submitted form values
      $FormPostValues = GetValue('FormPostValues', $Sender->EventArguments);
      if (is_array($FormPostValues)) {
         // Confirm we have custom fields
         $CustomLabels = GetValue('CustomLabel', $FormPostValues);
         $CustomValues = GetValue('CustomValue', $FormPostValues);         
         if (is_array($CustomLabels) && is_array($CustomValues)) {
            $UserID = GetValue('UserID', $Sender->EventArguments);
            
            // Trim fields to proper length & build array
            $ValueLimit = Gdn::Session()->CheckPermission('Garden.Moderation.Manage') ? 255 : C('Plugins.ProfileExtender.TextMaxLength', 140);
            $this->TrimValues($CustomLabels, 50);
            $this->TrimValues($CustomValues, $ValueLimit);
            $Fields = array_combine($CustomLabels, $CustomValues);
            
            // Delete custom fields that had their value removed
            foreach ($Fields as $Label => $Value) {
               if ($Value == '')
                  $Fields[$Label] = NULL;
            }
            
            // Delete custom fields that had their label removed
            $ExitingFields = Gdn::UserModel()->GetMeta($UserID, 'Profile.%', 'Profile.');
            foreach ($ExitingFields as $Label => $Value) {
               if (!array_key_exists($Label, $Fields))
                  $Fields[$Label] = NULL;
            }
            
            // Update UserMeta
            Gdn::UserModel()->SetMeta($UserID, $Fields, 'Profile.');
         }
      }
   }
   
   /**
	 * Save custom fields during registration.
	 */
	public function UserModel_AfterInsertUser_Handler($Sender) {
      if (!(Gdn::Controller() instanceof Gdn_Controller)) return;
      
	   // Get user-submitted
	   $FormPostValues = Gdn::Controller()->Form->FormValues();
	   $CustomLabels = GetValue('CustomLabel', $FormPostValues);
      $CustomValues = GetValue('CustomValue', $FormPostValues);
	   
	   if (is_array($CustomLabels) && is_array($CustomValues)) {
         $Fields = array_combine($CustomLabels, $CustomValues);
	   
   	   // Only grab valid fields
   	   $RegistrationFields = array_flip((array)explode(',', C('Plugins.ProfileExtender.RegistrationFields')));
         $SaveFields = array_intersect_key($Fields, $RegistrationFields);
      
         Gdn::UserModel()->SetMeta(GetValue('InsertUserID', $Sender->EventArguments), $SaveFields, 'Profile.');
      }
	}
   
   /**
    * Add suggested fields on install & convert CustomProfileField settings.
    */
   public function Setup() {
      // Import CustomProfileFields settings
      if ($Suggested = C('Plugins.CustomProfileFields.SuggestedFields', FALSE))
         SaveToConfig('Plugins.ProfileExtender.ProfileFields', $Suggested);
      if ($Hidden = C('Plugins.CustomProfileFields.HideFields', FALSE))
         SaveToConfig('Plugins.ProfileExtender.HideFields', $Hidden);
      if ($Length = C('Plugins.CustomProfileFields.ValueLength', FALSE))
         SaveToConfig('Plugins.ProfileExtender.TextMaxLength', $Length);
            
      // Set defaults
      if (!C('Plugins.ProfileExtender.ProfileFields', FALSE))
         SaveToConfig('Plugins.ProfileExtender.ProfileFields', 'Location,Facebook,Twitter,Website');
      if (!C('Plugins.ProfileExtender.RegistrationFields', FALSE))
         SaveToConfig('Plugins.ProfileExtender.RegistrationFields', 'Location');
      if (!C('Plugins.ProfileExtender.TextMaxLength', FALSE))
         SaveToConfig('Plugins.ProfileExtender.TextMaxLength', 140);
   }
}