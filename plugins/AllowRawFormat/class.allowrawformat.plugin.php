<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license Proprietary
 */

// Define the plugin:
$PluginInfo['AllowRawFormat'] = array(
   'Name' => 'Allow Raw Format',
   'Description' => 'Adds a permission to allow users with permission to post raw html.',
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RegisterPermissions' => array('Plugins.AllowRawFormat.Allow'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd'
);

class AllowRawFormatPlugin extends Gdn_Plugin {
   public function Base_BeforeDispatch_Handler($Sender, $Args) {
      if (Gdn::Session()->CheckPermission('Plugins.AllowRawFormat.Allow')) {
         SaveToConfig('Garden.InputFormatter', 'Raw', array('Save' => FALSE));
      }
   }
}