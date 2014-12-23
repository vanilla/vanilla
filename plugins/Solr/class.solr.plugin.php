<?php if (!defined('APPLICATION')) exit();
/**
 * @copyright Copyright 2008, 2009 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPLv2
 */

// Define the plugin:
$PluginInfo['Solr'] = array(
   'Name' => 'Solr Search',
   'Description' => "Allows Vanilla's search functionality to use solr instead of MySQL fulltext search.",
   'Version' => '1.0b',
   'RequiredApplications' => array('Vanilla' => '2.0.18'),
   'Author' => 'Todd Burry',
   'AuthorEmail' => 'todd@vanillaforums.com',
   'AuthorUrl' => 'http://www.vanillaforums.org/profile/todd',
   'SettingsUrl' => '/settings/solr',
);

class SolrPlugin extends Gdn_Plugin {
   public function SettingsController_Solr_Create($Sender, $Args = array()) {
      $Sender->Permission('Garden.Settings.Manage');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(
          'Plugins.Solr.SearchUrl' => array('Default' => 'http://localhost:8983/solr/select/?')
      ));

      $Sender->AddSideMenu();
      $Sender->SetData('Title', T('Solr Search Settings'));
      $Sender->ConfigurationModule = $Conf;
//      $Conf->RenderAll();
      $Sender->Render('Settings', '', 'plugins/Solr');
   }
}