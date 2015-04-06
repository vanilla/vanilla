<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 * @author Todd Burry <todd@vanillaforums.com>
 */

// Define the plugin:
$PluginInfo['vanillicon'] = array(
   'Name' => T('Vanillicon'),
   'Description' => T("Provides fun default user icons from vanillicon.com."),
   'Version' => '2.0.0-beta',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'MobileFriendly' => true,
   'SettingsUrl' => '/settings/vanillicon',
   'SettingsPermission' => 'Garden.Settings.Manage'
);

class VanilliconPlugin extends Gdn_Plugin {
   /// Methods ///

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
      TouchConfig('Plugins.Vanillicon.Type', 'v1');
   }

   /// Properties ///

   /**
    * Set the vanillicon on the user' profile.
    *
    * @param ProfileController $Sender
    * @param array $Args
    */
   public function ProfileController_AfterAddSideMenu_Handler($Sender, $Args) {
      if (!$Sender->User->Photo) {
         $Sender->User->Photo = UserPhotoDefaultUrl($Sender->User, array('Size' => 200));
      }
   }

   /**
    * The settings page for vanillicon.
    *
    * @param Gdn_Controller $sender
    */
   public function SettingsController_Vanillicon_Create($sender) {
      $sender->Permission('Garden.Settings.Manage');
      $cf = new ConfigurationModule($sender);

      $items = array(
         'v1' => 'Vanillicon 1',
         'v2' => 'Vanillicon 2 (beta)'
      );

      $cf->Initialize(array(
         'Plugins.Vanillicon.Type' => array(
            'LabelCode' => 'Vanillicon Set',
            'Control' => 'radiolist',
            'Description' => 'Which vanillicon set do you want to use?',
            'Items' => $items,
            'Options' => array('list' => true, 'listclass' => 'icon-list', 'display' => 'after'),
            'Default' => 'v1'
         )
      ));

      $sender->AddSideMenu();
      $sender->SetData('Title', sprintf(T('%s Settings'), 'Vanillicon'));
      $cf->RenderAll();
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
   function UserPhotoDefaultUrl($user, $options = array()) {
      static $iconSize = null, $type = null;
      if ($iconSize === null) {
         $thumbSize = C('Garden.Thumbnail.Size');
         $iconSize = $thumbSize <= 50 ? 50 : 100;
      }
      if ($type === null) {
         $type = C('Plugins.Vanillicon.Type');
      }
      $size = val('Size', $options, $iconSize);

      $email = val('Email', $user);
      if (!$email) {
         $email = GetValue('UserID', $user, 100);
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