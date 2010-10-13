<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class EmbedFriendlyThemeHooks implements Gdn_IPlugin {
	
   public function Setup() {
		// Set the order for the modules (make sure new discussion module is before content).
		SaveToConfig('Modules.Vanilla.Content', array('MessageModule', 'Notices', 'NewConversationModule', 'NewDiscussionModule', 'Content', 'Ads'));
		SaveToConfig('Modules.Conversations.Content', array('MessageModule', 'Notices', 'NewConversationModule', 'NewDiscussionModule', 'Content', 'Ads'));
   }

   public function OnDisable() {
      return TRUE;
   }
	
	public function SettingsController_AfterCurrentTheme_Handler($Sender) {
		$SingleColumn = C('Themes.EmbedFriendly.SingleColumn');
		echo Wrap(
			T('This theme allows you to hide the side panel next to your forum and conversations. This is super handy if the website you are embedding in does not have a lot of width to squeeze into.')
			.Wrap(Anchor(
				T($SingleColumn ? 'Show the side panel' : 'Hide the side panel'),
				'settings/embedfriendlytogglepanel/'.Gdn::Session()->TransientKey(),
				'SmallButton'
			), 'div')
		, 'div', array('class' => 'Description'));
	}
	
	public function SettingsController_EmbedFriendlyTogglePanel_Create($Sender) {
		$this->_TogglePanel($Sender);
		Redirect('settings/themes');
	}
	
	public function PluginController_BeforeEmbedRecommend_Handler($Sender) {
		$SingleColumn = C('Themes.EmbedFriendly.SingleColumn');
		echo '<div class="EmbedRecommend">
			<strong>Theme Options</strong>'
			.Wrap(
				T('This theme allows you to hide the side panel next to your forum and conversations. This is super handy if the website you are embedding in does not have a lot of width to squeeze into.')
				.Wrap(Anchor(
					T($SingleColumn ? 'Show the side panel' : 'Hide the side panel'),
					'plugin/embedfriendlytogglepanel/'.Gdn::Session()->TransientKey(),
					'SmallButton'
				), 'div', array('style' => 'margin-top: 10px;'))
			, 'em')
		.'</div>';
	}
	
	public function PluginController_EmbedFriendlyTogglePanel_Create($Sender) {
		$this->_TogglePanel($Sender);
		Redirect('plugin/embed');
	}

	private function _TogglePanel($Sender) {
		$Sender->Permission('Garden.Themes.Manage');
		$TransientKey = GetValue(0, $Sender->RequestArgs);
		if (Gdn::Session()->ValidateTransientKey($TransientKey))
			SaveToConfig('Themes.EmbedFriendly.SingleColumn', C('Themes.EmbedFriendly.SingleColumn') ? FALSE : TRUE);
	}


	public function Base_Render_Before($Sender) {
		if (($Sender->MasterView == 'default' || $Sender->MasterView == '') && C('Themes.EmbedFriendly.SingleColumn'))
			$Sender->AddCSSFile('singlecolumn.css');
	}
	
   public function CategoriesController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'NewDiscussionModule');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'NewDiscussionModule');
   }

   public function DiscussionController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'NewDiscussionModule');
   }

   public function DraftsController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'NewDiscussionModule');
   }
	
	public function MessagesController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'NewConversationModule');
	}
	
	private function _AddButton($Sender, $ModuleName) {
		if (C('Themes.EmbedFriendly.SingleColumn'))
			$Sender->AddModule($ModuleName, 'Content');
	}
   
}