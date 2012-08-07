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
 * Customizations for the mobile theme.
 */
class MobileThemeHooks implements Gdn_IPlugin {
   /** No setup required. */
   public function Setup() { }
   
   /** Remove plugins that are not mobile friendly! */
   public function Gdn_Dispatcher_AfterAnalyzeRequest_Handler($Sender) {
      // Remove plugins so they don't mess up layout or functionality.
      if (in_array($Sender->Application(), array('vanilla', 'conversations')) || ($Sender->Application() == 'dashboard' && in_array($Sender->Controller(), array('Activity', 'Profile', 'Search')))) {
         Gdn::PluginManager()->RemoveMobileUnfriendlyPlugins();
      }
      SaveToConfig('Garden.Format.EmbedSize', '240x135', FALSE);
   }
   
   /** Add mobile meta info. Add script to hide iPhone browser bar on pageload. */
   public function Base_Render_Before($Sender) {
      if (IsMobile() && is_object($Sender->Head)) {
         $Sender->Head->AddTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
         $Sender->Head->AddString('
<script type="text/javascript">
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
   
   /** Add button, remove options, increase click area on discussions list. */
   public function CategoriesController_Render_Before($Sender) {      
      $Sender->ShowOptions = FALSE;
      SaveToConfig('Vanilla.AdminCheckboxes.Use', FALSE, FALSE);
      $this->AddButton($Sender, 'Discussion');
      $this->DiscussionsClickable($Sender);      
   }
   
   /** Add button, remove options, increase click area on discussions list. */
   public function DiscussionsController_Render_Before($Sender) {
      $Sender->ShowOptions = FALSE;
      SaveToConfig('Vanilla.AdminCheckboxes.Use', FALSE, FALSE);
      $this->AddButton($Sender, 'Discussion');
      $this->DiscussionsClickable($Sender);
   }
   
   /** Add New Discussion button. */
   public function DiscussionController_Render_Before($Sender) {
      $this->AddButton($Sender, 'Discussion');
   }
   
   /** Add New Discussion button. */
   public function DraftsController_Render_Before($Sender) {
      $this->AddButton($Sender, 'Discussion');
   }
   
   /** Add New Conversation button. */
   public function MessagesController_Render_Before($Sender) {
      $this->AddButton($Sender, 'Conversation');
   }

   /** Add New Discussion button. */
   public function PostController_Render_Before($Sender) {
      $this->AddButton($Sender, 'Discussion');
   }
   
   /** Add a button to the navbar. */
   private function AddButton($Sender, $ButtonType) {
      if (is_object($Sender->Menu)) {
         if ($ButtonType == 'Discussion')
            $Sender->Menu->AddLink('NewDiscussion', Img('themes/mobile/design/images/new.png', array('alt' => T('New Discussion'))), '/post/discussion'.(array_key_exists('CategoryID', $Sender->Data) ? '/'.$Sender->Data['CategoryID'] : ''), array('Garden.SignIn.Allow'), array('class' => 'NewDiscussion'));
         elseif ($ButtonType == 'Conversation')
            $Sender->Menu->AddLink('NewConversation', Img('themes/mobile/design/images/new.png', array('alt' => T('New Conversation'))), '/messages/add', '', array('class' => 'NewConversation'));
      }
   }
   
   /** Increases clickable area on a discussions list. */
   private function DiscussionsClickable($Sender) {
      // Make sure that discussion clicks (anywhere in a discussion row) take the user to the discussion.
      if (property_exists($Sender, 'Head') && is_object($Sender->Head)) {
         $Sender->Head->AddString('
<script type="text/javascript">
   jQuery(document).ready(function($) {
      $("ul.DataList li.Item").click(function() {
         var href = $(this).find(".Title a").attr("href");
         if (typeof href != "undefined")
            document.location = href;
      });
   });
</script>');
      }
   }
   
   /** Add the user photo before the user Info on the profile page. */
   public function ProfileController_BeforeUserInfo_Handler($Sender) {
      $UserPhoto = new UserPhotoModule();
      echo $UserPhoto->ToString();
   }
}