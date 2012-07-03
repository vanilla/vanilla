<?php if (!defined('APPLICATION')) exit();

/**
 * Sprites Plugin
 * 
 * @author Mark O'Sullivan <mark@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 */

$PluginInfo['Sprites'] = array(
   'Name' => 'Sprites',
   'Description' => 'Adds sprites (icons) to all menus and nav buttons throughout Vanilla.',
   'Version' => '1.0',
   'MobileFriendly' => TRUE,
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'RequiredTheme' => FALSE, 
   'RequiredPlugins' => FALSE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.com'
);

class SpritesPlugin extends Gdn_Plugin {

   public function Base_Render_Before($Sender) {
      if ($Sender->MasterView == '' || $Sender->MasterView == 'default')
         $Sender->AddCssFile('sprites.css', 'plugins/Sprites');
   }

   public function Setup() {}

   public function Structure() {}

}