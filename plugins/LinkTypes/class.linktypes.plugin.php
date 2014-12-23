<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright 2009-2014 Vanilla Forums, Inc.
 * @license GNU GPLv2
 */

$PluginInfo['LinkTypes'] = array(
   'Name' => 'Link Types',
   'Description' => 'Open external URLs in a new tab.',
   // @todo: Make this plugin configurable to decide how different URL types should behave.
   // Always maintain external => _blank as a default.
   'Version' => '1.0',
   'RequiredApplications' => array('Vanilla' => '2.1'),
   'Author' => "Lincoln Russell",
   'AuthorEmail' => 'lincoln@vanillaforums.com'
);

class LinkTypesPlugin extends Gdn_Plugin {
   /**
    * Add JS file.
    *
    * @param $Sender AssetModel
    */
   public function Base_Render_Before($Sender) {
      $Sender->AddJsFile('linktypes.js', 'plugins/LinkTypes');
   }
   
   /**
    * Plugin setup.
    */
   public function Setup() {

   }
}