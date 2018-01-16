<?php
/**
 * Mobile Theme hooks.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
    public function gdn_dispatcher_afterAnalyzeRequest_handler($sender) {
        // Remove plugins so they don't mess up layout or functionality.
        $inPublicDashboard = ($sender->application() == 'dashboard' && in_array($sender->controller(), ['Activity', 'Profile', 'Search']));
        if (in_array($sender->application(), ['vanilla', 'conversations']) || $inPublicDashboard) {
            Gdn::pluginManager()->removeMobileUnfriendlyPlugins();
        }
        saveToConfig('Garden.Format.EmbedSize', '240x135', false);
    }

    /**
     * Add mobile meta info. Add script to hide iPhone browser bar on pageload.
     */
    public function base_render_before($sender) {
        if (isMobile() && is_object($sender->Head)) {
            $sender->Head->addTag('meta', ['name' => 'viewport', 'content' => "width=device-width,minimum-scale=1.0,maximum-scale=1.0"]);
            $sender->Head->addString('
<script>
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
    public function categoriesController_render_before($sender) {
        $sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($sender, 'Discussion');
        $this->discussionsClickable($sender);
    }

    /**
     * Add button, remove options, increase click area on discussions list.
     */
    public function discussionsController_render_before($sender) {
        $sender->ShowOptions = false;
        saveToConfig('Vanilla.AdminCheckboxes.Use', false, false);
        $this->addButton($sender, 'Discussion');
        $this->discussionsClickable($sender);
    }

    /**
     * Add New Discussion button.
     */
    public function discussionController_render_before($sender) {
        $this->addButton($sender, 'Discussion');
    }

    /**
     * Add New Discussion button.
     */
    public function draftsController_render_before($sender) {
        $this->addButton($sender, 'Discussion');
    }

    /**
     * Add New Conversation button.
     */
    public function messagesController_render_before($sender) {
        $this->addButton($sender, 'Conversation');
    }

    /**
     * Add New Discussion button.
     */
    public function postController_render_before($sender) {
        $this->addButton($sender, 'Discussion');
    }

    /**
     * Add a button to the navbar.
     */
    private function addButton($sender, $buttonType) {
        if (is_object($sender->Menu)) {
            if ($buttonType == 'Discussion') {
                $sender->Menu->addLink(
                    'NewDiscussion',
                    img('themes/mobile/design/images/new.png', ['alt' => t('New Discussion')]),
                    '/post/discussion'.(array_key_exists('CategoryID', $sender->Data) ? '/'.$sender->Data['CategoryID'] : ''),
                    ['Garden.SignIn.Allow'],
                    ['class' => 'NewDiscussion']
                );
            } elseif ($buttonType == 'Conversation')
                $sender->Menu->addLink(
                    'NewConversation',
                    img('themes/mobile/design/images/new.png', ['alt' => t('New Conversation')]),
                    '/messages/add',
                    '',
                    ['class' => 'NewConversation']
                );
        }
    }

    /**
     * Increases clickable area on a discussions list.
     */
    private function discussionsClickable($sender) {
        // Make sure that discussion clicks (anywhere in a discussion row) take the user to the discussion.
        if (property_exists($sender, 'Head') && is_object($sender->Head)) {
            $sender->Head->addString('
<script>
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
    public function profileController_beforeUserInfo_handler($sender) {
        $userPhoto = new UserPhotoModule();
        echo $userPhoto->toString();
    }
}
