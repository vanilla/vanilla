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
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

/*
CHANGELOG
=========

2.0 - 2011-11-03 Matt Lincoln Russell
- Rewrite to use UserMeta (instead of Attributes array)
- Add ability to put fields on entry pages

*/

class ProfileExtenderPlugin extends Gdn_Plugin {
	/**
	 * Render the custom fields on the admin edit user form.
	 */
	public function UserController_AfterFormInputs_Handler($Sender) {
		echo '<ul>';
		$this->_FormFields($Sender);
		echo '</ul>';
	}
	
	/**
	 * Render the custom fields on the profile edit user form.
	 */
	public function ProfileController_EditMyAccountAfter_Handler($Sender) {
		$this->_FormFields($Sender);
	}
	
	/**
	 * Render the custom fields.
	 */
	private function _FormFields($Sender) {
		// Retrieve user's existing profile fields
		$SuggestedFields = C('Plugins.CustomProfileFields.SuggestedFields', '');
		$SuggestedFields = explode(',', $SuggestedFields);
		$IsPostBack = $Sender->Form->IsPostBack();
		$ProfileFields = array();
		if (is_object($Sender->User))
			$ProfileFields = Gdn::UserModel()->GetAttribute($Sender->User->UserID, 'CustomProfileFields', array());
		
		// Write out the suggested fields first
		if (count($SuggestedFields) > 0)
			echo Wrap(Wrap(T('More Information'), 'label'), 'li');
			
		$CountFields = 0;
		foreach ($SuggestedFields as $Field) {
			$CountFields++;
			$Value = $IsPostBack ? GetValue($Field, $_POST, '') : GetValue($Field, $ProfileFields, '');
			echo '<li>';
				echo $Sender->Form->Hidden('CustomProfileFieldLabel[]', array('value' => $Field));
				echo $Sender->Form->Label($Field, 'CustomProfileFieldValue[]');
				echo $Sender->Form->TextBox('CustomProfileFieldValue[]', array('value' => $Value));
			echo '</li>';
		}
		if (!C('Plugins.CustomProfileFields.Disallow')) {
		?>
<li>
	<label><?php echo T('Custom Information'); ?></label>
	<div><?php echo T('Use these fields to create custom profile information. You can enter things like "Relationship Status", "Skype ID", "Favorite Dinosaur", etc. Be creative!'); ?></div>
	<div class="CustomProfileFieldLabel"><?php echo T('Label'); ?></div>
	<div class="CustomProfileFieldValue"><?php echo T('Value'); ?></div>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$("input.CustomProfileFieldLabel").live('blur', function() {
				var lastLabel = $('input.CustomProfileFieldLabel:last'),
					lastVal = $('input.CustomProfileFieldValue:last');
				
				if (lastLabel.val() != '' || lastLabel.index() == $(this).index()) {
					$(lastVal).after(lastVal.clone().val(''));
					$(lastVal).after(lastLabel.clone().val(''));
				}
				return;
			});
		});
	</script>
	<style type="text/css">
	div.CustomProfileFieldLabel,
	div.CustomProfileFieldValue {
		display: inline-block;
		font-weight: bold;
		width: 49%;
	}
	input.CustomProfileFieldLabel,
	input.CustomProfileFieldValue {
		width: 47%;
		margin-bottom: 4px;
	}
	input.CustomProfileFieldLabel {
		margin-right: 10px;
	}
	</style>
</li>
<?php
            // Write out user-defined custom fields
            $CustomProfileFieldLabel = GetValue('CustomProfileFieldLabel', $Sender->Form->FormValues(), array());
            $CustomProfileFieldValue = GetValue('CustomProfileFieldValue', $Sender->Form->FormValues(), array());
            foreach ($ProfileFields as $Field => $Value) {
               if (!in_array($Field, $SuggestedFields)) {
                  if ($IsPostBack) {
                     $Field = GetValue($CountFields, $CustomProfileFieldLabel, '');
                     $Value = GetValue($CountFields, $CustomProfileFieldValue, '');
                  }
                  $CountFields++;
                  echo '<li>';
                     echo $Sender->Form->TextBox('CustomProfileFieldLabel[]', array('value' => $Field, 'class' => 'CustomProfileFieldLabel'));
                     echo $Sender->Form->TextBox('CustomProfileFieldValue[]', array('value' => $Value, 'class' => 'CustomProfileFieldValue'));
                  echo '</li>';
               }
            }
            // Write out one empty row
            echo '<li>';
               echo $Sender->Form->TextBox('CustomProfileFieldLabel[]', array('class' => 'CustomProfileFieldLabel'));
               echo $Sender->Form->TextBox('CustomProfileFieldValue[]', array('class' => 'CustomProfileFieldValue'));
            echo '</li>';
         }
	}
	
	/**
	 * Save the custom profile fields when saving the user.
	 */
	public function UserModel_AfterSave_Handler($Sender) {
      $ValueLimit = Gdn::Session()->CheckPermission('Garden.Moderation.Manage') ? 255 : C('Plugins.CustomProfileFields.ValueLength', 255);
		$UserID = GetValue('UserID', $Sender->EventArguments);
		$FormPostValues = GetValue('FormPostValues', $Sender->EventArguments);
		$CustomProfileFieldLabels = FALSE;
		$CustomProfileFieldValues = FALSE;
		$CustomProfileFields = FALSE;
		if (is_array($FormPostValues)) {
			$CustomProfileFieldLabels = GetValue('CustomProfileFieldLabel', $FormPostValues);
			$CustomProfileFieldValues = GetValue('CustomProfileFieldValue', $FormPostValues);
			if (is_array($CustomProfileFieldLabels) && is_array($CustomProfileFieldValues)) {
				$this->_TrimValues($CustomProfileFieldLabels, 50);
				$this->_TrimValues($CustomProfileFieldValues, $ValueLimit);
				$CustomProfileFields = array_combine($CustomProfileFieldLabels, $CustomProfileFieldValues);
			}
			
			// Don't save any empty values or labels
			if (is_array($CustomProfileFields)) {
				foreach ($CustomProfileFields as $Field => $Value) {
					if ($Field == '' || $Value == '')
						unset($CustomProfileFields[$Field]);
				}
			}
		}
			
		if ($UserID > 0 && is_array($CustomProfileFields))
			Gdn::UserModel()->SaveAttribute($UserID, 'CustomProfileFields', $CustomProfileFields);
	}
	
	/**
	 * Loop through values, trimming them to the specified length.
	 */
	private function _TrimValues(&$Array, $Length = 200) {
		foreach ($Array as $Key => $Val) {
			$Array[$Key] = substr($Val, 0, $Length);
		}
	}
	
	/**
	 * Render the values on the profile page.
	 */
	public function UserInfoModule_OnBasicInfo_Handler($Sender) {
		// Render the custom fields
		try {
         $HideFields = (array)explode(',', C('Plugins.CustomProfileFields.HideFields'));
         
			$CustomProfileFields = GetValue('CustomProfileFields', $Sender->User->Attributes, array());
			foreach ($CustomProfileFields as $Label => $Value) {
            if (in_array($Label, $HideFields))
               continue;
            
            $Value = Gdn_Format::Links(htmlspecialchars($Value));
            
				echo '<dt class="CustomProfileField CustomProfileField-'.Gdn_Format::Url($Label).'">'.Gdn_Format::Text($Label).'</dt>';
				echo '<dd class="CustomProfileField CustomProfileField-'.Gdn_Format::Url($Label).'">'.$Value.'</dd>';
			}
		} catch (Exception $ex) {
			// No errors
		}
	}
	
	/**
	 * Configuration screen
	 */
	public function PluginController_CustomProfileFields_Create($Sender) {
		$Conf = new ConfigurationModule($Sender);
		$Conf->Initialize(array(
			'Plugins.CustomProfileFields.SuggestedFields' => array('Control' => 'TextBox', 'Options' => array('MultiLine' => TRUE)),
			'Plugins.CustomProfileFields.Disallow' => array('Type' => 'bool', 'Control' => 'CheckBox', 'LabelCode' => "Don't allow custom fields.")
		));

     $Sender->AddSideMenu('plugin/customprofilefields');
     $Sender->SetData('Title', T('Custom Profile Field Settings'));
     $Sender->ConfigurationModule = $Conf;
     $Conf->RenderAll();
	}
	
	/**
	 * Add the admin config menu option.
	 */
	public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddLink('Users', T('Custom Profile Fields'), 'plugin/customprofilefields', 'Garden.User.Edit');
	}
	
   /**
    * Add suggested fields on install. These are configurable in conf/config.php.
    */
   public function Setup() {
		$SuggestedFields = C('Plugins.CustomProfileFields.SuggestedFields');
		if (!$SuggestedFields)
			SaveToConfig(
				'Plugins.CustomProfileFields.SuggestedFields',
				'Facebook,Twitter,Website,Xbox Live,Playstation ID,Wii Friend Code,Steam ID,WoW'
			);
   }
}