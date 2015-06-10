<?php
/**
 * Mobile Theme hooks.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Mobile Theme
 * @since 2.0
 */

/**
 * Customizations for the mobile theme.
 */
class MobileThemeHooks implements Gdn_IPlugin {

    /**
     * No setup required.
     */
    public function setup() {
    }

    /**
     * Remove plugins that are not mobile friendly!
     */
    public function gdn_dispatcher_afterAnalyzeRequest_handler($Sender) {
        // Remove plugins so they don't mess up layout or functionality.
        $inPublicDashboard = ($Sender->application() == 'dashboard' && in_array($Sender->controller(), array('Activity', 'Profile', 'Search')));
        if (in_array($Sender->application(), array('vanilla', 'conversations')) || $inPublicDashboard) {
            Gdn::pluginManager()->removeMobileUnfriendlyPlugins();
        }
        saveToConfig('Garden.Format.EmbedSize', '240x135', false);
    }

    /**
     * Add mobile meta info. Add script to hide iPhone browser bar on pageload.
     */
    public function base_render_before($Sender) {
        if (isMobile() && is_object($Sender->Head)) {
            $Sender->Head->addTag('meta', array('name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"));
            $Sender->Head->addString('
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

    /**
     * Add button, remove options, increase click area on discussions list.
     */
    public function categoriesController_render_before($Sender) {
        $Sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($Sender, 'Discussion');
        $this->discussionsClickable($Sender);
    }

    /**
     * Add button, remove options, increase click area on discussions list.
     */
    public function discussionsController_render_before($Sender) {
        $Sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($Sender, 'Discussion');
        $this->discussionsClickable($Sender);
    }

    /**
     * Add New Discussion button.
     */
    public function discussionController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add New Discussion button.
     */
    public function draftsController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add New Conversation button.
     */
    public function messagesController_render_before($Sender) {
        $this->addButton($Sender, 'Conversation');
    }

    /**
     * Add New Discussion button.
     */
    public function postController_render_before($Sender) {
        $this->addButton($Sender, 'Discussion');
    }

    /**
     * Add a button to the navbar.
     */
    private function addButton($Sender, $ButtonType) {
        if (is_object($Sender->Menu)) {
            if ($ButtonType == 'Discussion') {
                $Sender->Menu->addLink(
                    'NewDiscussion',
                    img('themes/mobile/design/images/new.png', array('alt' => t('New Discussion'))),
                    '/post/discussion'.(array_key_exists('CategoryID', $Sender->Data) ? '/'.$Sender->Data['CategoryID'] : ''),
                    array('Garden.SignIn.Allow'),
                    array('class' => 'NewDiscussion')
                );
            } elseif ($ButtonType == 'Conversation')
                $Sender->Menu->addLink(
                    'NewConversation',
                    img('themes/mobile/design/images/new.png', array('alt' => t('New Conversation'))),
                    '/messages/add',
                    '',
                    array('class' => 'NewConversation')
                );
        }
    }

    /**
     * Increases clickable area on a discussions list.
     */
    private function discussionsClickable($Sender) {
        // Make sure that discussion clicks (anywhere in a discussion row) take the user to the discussion.
        if (property_exists($Sender, 'Head') && is_object($Sender->Head)) {
            $Sender->Head->addString('
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

    /**
     * Add the user photo before the user Info on the profile page.
     */
    public function profileController_beforeUserInfo_handler($Sender) {
        $UserPhoto = new UserPhotoModule();
        echo $UserPhoto->toString();
    }
}
