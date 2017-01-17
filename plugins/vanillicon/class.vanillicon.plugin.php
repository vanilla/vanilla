<?php
/**
 * Vanillicon plugin.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package vanillicon
 */

// Define the plugin:
$PluginInfo['vanillicon'] = array(
   'Name' => 'Vanillicon',
   'Description' => "Provides fun default user icons from vanillicon.com.",
   'Version' => '2.1.0',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => true,
   'SettingsUrl' => '/settings/vanillicon',
   'SettingsPermission' => 'Garden.Settings.Manage',
   'Icon' => 'vanillicon.png'
);

/**
 * Class VanilliconPlugin
 */
class VanilliconPlugin extends Gdn_Plugin {

   /**
    * Set up the plugin.
    */
    public function setup() {
        $this->structure();
    }

   /**
    * Perform any necessary database or configuration updates.
    */
    public function structure() {
        touchConfig('Plugins.Vanillicon.Type', 'v2');
    }

   /**
    * Set the vanillicon on the user' profile.
    *
    * @param ProfileController $Sender
    * @param array $Args
    */
    public function profileController_afterAddSideMenu_handler($Sender, $Args) {
        if (!$Sender->User->Photo) {
            $Sender->User->Photo = userPhotoDefaultUrl($Sender->User, array('Size' => 200));
        }
    }

   /**
    * The settings page for vanillicon.
    *
    * @param Gdn_Controller $sender
    */
    public function settingsController_vanillicon_create($sender) {
        $sender->permission('Garden.Settings.Manage');
        $cf = new ConfigurationModule($sender);

        $items = array(
         'v1' => 'Vanillicon 1',
         'v2' => 'Vanillicon 2'
        );

        $cf->initialize(array(
         'Plugins.Vanillicon.Type' => array(
            'LabelCode' => 'Vanillicon Set',
            'Control' => 'radiolist',
            'Description' => 'Which vanillicon set do you want to use?',
            'Items' => $items,
            'Options' => array('display' => 'after'),
            'Default' => 'v1'
         )
        ));

        
        $sender->setData('Title', sprintf(t('%s Settings'), 'Vanillicon'));
        $cf->renderAll();
    }

    /**
     * Overrides allowing admins to set the default avatar, since it has no effect when Vanillicon is on.
     * Adds messages to the top of avatar settings page and to the help panel asset.
     *
     * @param SettingsController $sender
     */
    public function settingsController_avatarSettings_handler($sender) {
        // We check if Gravatar is enabled before adding any messages as Gravatar overrides Vanillicon.
        if (!Gdn::addonManager()->isEnabled('gravatar', \Vanilla\Addon::TYPE_ADDON)) {
            $message = t('You\'re using Vanillicon avatars as your default avatars.');
            $message .= ' '.t('To set a custom default avatar, disable the Vanillicon plugin.');
            $messages = $sender->data('messages', []);
            $messages = array_merge($messages, [$message]);
            $sender->setData('messages', $messages);
            $sender->setData('canSetDefaultAvatar', false);
            $help = t('Your users\' default avatars are Vanillicon avatars.');
            helpAsset(t('How are my users\' default avatars set?'), $help);
        }
    }
}

if (!function_exists('UserPhotoDefaultUrl')) {
   /**
    * Calculate the user's default photo url.
    *
    * @param array|object $user The user to examine.
    * @param array $options An array of options.
    * - Size: The size of the photo.
    * @return string Returns the vanillicon url for the user.
    */
    function userPhotoDefaultUrl($user, $options = array()) {
        static $iconSize = null, $type = null;
        if ($iconSize === null) {
            $thumbSize = c('Garden.Thumbnail.Size');
            $iconSize = $thumbSize <= 50 ? 50 : 100;
        }
        if ($type === null) {
            $type = c('Plugins.Vanillicon.Type');
        }
        $size = val('Size', $options, $iconSize);

        $email = val('Email', $user);
        if (!$email) {
            $email = val('UserID', $user, 100);
        }
        $hash = md5($email);
        $px = substr($hash, 0, 1);

        switch ($type) {
            case 'v2':
                $photoUrl = "//w$px.vanillicon.com/v2/{$hash}.svg";
                break;
            default:
                $photoUrl = "//w$px.vanillicon.com/{$hash}_{$size}.png";
                break;
        }

        return $photoUrl;
    }
}
