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
	
	/**
	 * Remove plugins that are not mobile friendly!
	 */
	public function Gdn_Dispatcher_AfterAnalyzeRequest_Handler($Sender) {
		// Remove plugins so they don't mess up layout or functionality.
		if (in_array($Sender->Application(), array('vanilla', 'dashboard', 'conversations')))
			Gdn::PluginManager()->RemoveMobileUnfriendlyPlugins();
	}
	
	/**
	 * Add mobile meta info. Add script to hide iphone browser bar on pageload.
	 */
	public function Base_Render_Before($Sender) {
		if (IsMobile() && is_object($Sender->Head)) {
			$Sender->Head->AddTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
			
			$Sender->Head->AddString('<script type="text/javascript">
// If not looking for a specific comment, hide the address bar in iphone
var hash = window.location.href.split("#")[1];
if (typeof(hash) == "undefined") {
	setTimeout(function () {
	  window.scrollTo(0, 1);
	}, 1000);
}
</script>');
		}
	}
	
	/**
	 * Add new discussion & conversation buttons to various pages.
	 */
   public function CategoriesController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }
   
   public function DiscussionsController_Render_Before($Sender) {
		// Make sure that discussion clicks (anywhere in a discussion row) take the user to the discussion.
		if (property_exists($Sender, 'Head') && is_object($Sender->Head)) {
			$Sender->Head->AddString('<script type="text/javascript">
jQuery(document).ready(function($) {
	$("ul.DataList li.Item").click(function() {
		document.location = $(this).find("a.Title").attr("href");
	});
});
</script>');
		}
		// Add the new discussion button to the page.
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

   public function PostController_Render_Before($Sender) {
		$this->_AddButton($Sender, 'Discussion');
   }
	
	private function _AddButton($Sender, $ButtonType) {
		if (is_object($Sender->Menu)) {
			if ($ButtonType == 'Discussion')
				$Sender->Menu->AddLink('NewDiscussion', Img('themes/mobile/design/images/new.png', array('alt' => T('New Discussion'))), '/post/discussion'.(array_key_exists('CategoryID', $Sender->Data) ? '/'.$Sender->Data['CategoryID'] : ''), array('Garden.SignIn.Allow'), array('class' => 'NewDiscussion'));
			elseif ($ButtonType == 'Conversation')
				$Sender->Menu->AddLink('NewConversation', Img('themes/mobile/design/images/new.png', array('alt' => T('New Conversation'))), '/messages/add', '', array('class' => 'NewConversation'));
		}
	}
	
	// Change all pagers to be "more" pagers instead of standard numbered pagers
	public function DiscussionsController_BeforeBuildPager_Handler($Sender) {
		$Sender->EventArguments['PagerType'] = 'MorePager';
	}
   
	public function DiscussionController_BeforeBuildPager_Handler($Sender) {
		$Sender->EventArguments['PagerType'] = 'MorePager';
		$Sender->AddJsFile('jquery.gardenmorepager.js');
	}
	
   public function DiscussionController_BeforeDiscussion_Handler($Sender) {
		echo $Sender->Pager->ToString('less');
	}
	
	public function DiscussionController_AfterBuildPager_Handler($Sender) {
		$Sender->Pager->LessCode = 'Older Comments';
		$Sender->Pager->MoreCode = 'More Comments';
	}
	
	public function DiscussionsController_AfterBuildPager_Handler($Sender) {
		$Sender->Pager->MoreCode = 'More Discussions';
	}

}