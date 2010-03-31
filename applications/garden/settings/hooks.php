<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class GardenHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }
   
   public function Base_Render_Before(&$Sender) {
      $Session = Gdn::Session();

      // Enable theme previewing
      if ($Session->IsValid()) {
         $PreviewThemeFolder = $Session->GetPreference('PreviewThemeFolder', '');
         // echo 'test'.$PreviewThemeFolder;
         if ($PreviewThemeFolder != '') {
            $Sender->Theme = $PreviewThemeFolder;
            $Sender->AddAsset('Content', $Sender->FetchView('previewtheme', 'settingscontroller', 'garden'));
            $Sender->AddCssFile('previewtheme.css');
         }
      }

      // Add Message Modules (if necessary)
      $MessageCache = Gdn::Config('Garden.Messages.Cache', array());
      $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
      if ($Sender->MasterView != 'empty' && in_array('Base', $MessageCache) || InArrayI($Location, $MessageCache)) {
         $MessageModel = new Gdn_MessageModel();
         $MessageData = $MessageModel->GetMessagesForLocation($Location);
         foreach ($MessageData as $Message) {
            $MessageModule = new Gdn_MessageModule($Sender, $Message);
            $Sender->AddModule($MessageModule);
         }
      }
   }
   
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Dashboard', T('Dashboard'));
      $Menu->AddLink('Dashboard', T('Dashboard'), 'garden/settings', 'Garden.Settings.Manage');

      $Menu->AddItem('Site Settings', T('Site Settings'));
      $Menu->AddLink('Site Settings', T('General'), 'garden/settings/configure', 'Garden.Settings.Manage');
      $Menu->AddLink('Site Settings', T('Routes'), 'garden/routes', 'Garden.Routes.Manage');
      $Menu->AddLink('Site Settings', T('Messages'), 'garden/message', 'Garden.Messages.Manage');
      
      $Menu->AddItem('Add-ons', T('Add-ons'));
      $Menu->AddLink('Add-ons', T('Applications'), 'garden/settings/applications', 'Garden.Applications.Manage');
      $Menu->AddLink('Add-ons', T('Plugins'), 'garden/settings/plugins', 'Garden.Plugins.Manage');
      $Menu->AddLink('Add-ons', T('Themes'), 'garden/settings/themes', 'Garden.Themes.Manage');

      $Menu->AddItem('Users', T('Users'));
      $Menu->AddLink('Users', T('Users'), 'garden/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
      $Menu->AddLink('Users', T('Roles & Permissions'), 'garden/role', 'Garden.Roles.Manage');
      $Menu->AddLink('Users', T('Registration'), 'garden/settings/registration', 'Garden.Registration.Manage');
      if (Gdn::Config('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', T('Applicants'), 'garden/user/applicants', 'Garden.Applicants.Manage');
   }
}