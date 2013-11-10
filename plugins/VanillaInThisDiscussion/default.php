<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

// Define the plugin:
$PluginInfo['VanillaInThisDiscussion'] = array(
   'Name' => 'In This Discussion',
   'Description' => "Adds a list of users taking part in the discussion to the side panel of the discussion page in Vanilla.",
   'Version' => '1',
   'Requires' => FALSE, // This would normally be an array of plugin names/versions that this plugin requires
   'HasLocale' => FALSE,
   'Author' => "Mark O'Sullivan",
   'AuthorEmail' => 'mark@vanillaforums.com',
   'AuthorUrl' => 'http://markosullivan.ca',
   'RegisterPermissions' => FALSE,
   'SettingsPermission' => 'Garden.Settings.Manage',
   'SettingsUrl' => '/settings/inthisdiscussion'
);

class VanillaInThisDiscussionPlugin extends Gdn_Plugin {
   
   // Setup settings page
   public function SettingsController_InThisDiscussion_Create($Sender) {
      $Sender->Permission('Garden.Settings.Manage');
      $Sender->SetData('Title', T('In This Discussion Settings'));
      $Sender->AddSideMenu('dashboard/settings/plugins');

      $Conf = new ConfigurationModule($Sender);
      $Conf->Initialize(array(          
          'Plugins.VanillaInThisDiscussion.Limit' => array(
             'Description' => T('User Limit'),
             'Default' => 20,
             'LabelCode' => T('Enter a limit for the number of users displayed')
          )
          ));

      
      $Conf->RenderAll();
   }

   public function DiscussionController_BeforeDiscussionRender_Handler($Sender) {
      // Handle limit
      $Limit = C('Plugins.VanillaInThisDiscussion.Limit', 20);
	  
      // Render  
      include_once(PATH_PLUGINS.DS.'VanillaInThisDiscussion'.DS.'class.inthisdiscussionmodule.php');
      $InThisDiscussionModule = new InThisDiscussionModule($Sender);
      $InThisDiscussionModule->GetData($Sender->Data('Discussion.DiscussionID'),$Limit);
      $Sender->AddModule($InThisDiscussionModule);

   }
   
   public function Setup() {
      // No setup required
   }
}