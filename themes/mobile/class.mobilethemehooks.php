<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class MobileThemeHooks implements Gdn_IPlugin {
	
   public function Setup() {
		return TRUE;
   }

   public function OnDisable() {
      return TRUE;
   }
	
	public function Base_Render_Before($Sender) {
		if (IsMobile() && is_object($Sender->Head)) {
			$Sender->Head->AddTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
		}
	}
	
   public function CategoriesController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }

   public function DiscussionController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }

   public function DraftsController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }
	
	public function MessagesController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Conversation');
	}
	
	private function _AddButton($Sender, $ButtonType) {
		if (is_object($Sender->Menu)) {
			if ($ButtonType == 'Discussion')
				$Sender->Menu->AddLink('NewDiscussion', Img('themes/mobile/design/images/new.png', array('alt' => T('New Discussion'))), '/post/discussion'.(array_key_exists('CategoryID', $Sender->Data) ? '/'.$Sender->Data['CategoryID'] : ''), array('Garden.SignIn.Allow'), array('class' => 'NewDiscussion'));
			elseif ($ButtonType == 'Conversation')
				$Sender->Menu->AddLink('NewConversation', Img('themes/mobile/design/images/new.png', array('alt' => T('New Conversation'))), '/messages/add', '', array('class' => 'NewConversation'));
		}
	}
	
	/* Add Counts after discussion title */
	public function Base_DiscussionMeta_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		$CountUnreadComments = 0;
		if (is_numeric($Discussion->CountUnreadComments))
			$CountUnreadComments = $Discussion->CountUnreadComments;
			
		$CssClass = 'Counts';
		if ($CountUnreadComments > 0)
			$CssClass .= ' NewCounts';
			
		echo '<span class="'.$CssClass.'">'
			.$Discussion->CountComments
			.($CountUnreadComments > 0 ? '/'.$CountUnreadComments : '')
		.'</span>';
	}
	
	/* Add Author Icon before discussion title */
	public function Base_BeforeDiscussionContent_Handler($Sender) {
		$Discussion = GetValue('Discussion', $Sender->EventArguments);
		$Author = UserBuilder($Discussion, 'First');
		echo UserPhoto($Author);
	}
   
}