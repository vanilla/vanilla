<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

class GardenHooks implements Gdn_IPlugin {
   public function Setup() {
      return TRUE;
   }
   
   public function Base_Render_Before(&$Sender) {
      // Add menu items.
      $Session = Gdn::Session();
      if ($Sender->Menu) {
         $Sender->Menu->AddLink('Dashboard', 'Dashboard', '/garden/settings', array('Garden.Settings.Manage'));
         $Sender->Menu->AddLink('Dashboard', 'Users', '/user/browse', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
         $Sender->Menu->AddLink('Activity', 'Activity', '/activity');
         $Authenticator = Gdn::Authenticator();
         if ($Session->IsValid()) {
            $Sender->Menu->AddLink('SignOut', 'Sign Out', '/entry/leave/{Session_TransientKey}', FALSE, array('class' => 'NonTab'));
            $Notifications = Gdn::Translate('Notifications');
            $CountNotifications = $Session->User->CountNotifications;
            if (is_numeric($CountNotifications) && $CountNotifications > 0)
               $Notifications .= '<span>'.$CountNotifications.'</span>';
               
            $Sender->Menu->AddLink('User', '{Username}', '/profile/{Username}', array('Garden.SignIn.Allow'));
            $Sender->Menu->AddLink('User', '\\'.$Notifications, 'profile/notifications/{Username}');
         } else {
            $Sender->Menu->AddLink('Entry', 'Sign In', $Authenticator->SignInUrl());
         }
      }
      // Enable theme previewing
      if ($Session->IsValid()) {
         $PreviewTheme = $Session->GetPreference('PreviewTheme', '');
         if ($PreviewTheme != '')
            $Sender->Theme = $PreviewTheme;
      }
      // Add Message Modules (if necessary)
      $MessageCache = Gdn::Config('Garden.Messages.Cache', array());
      $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
      if (in_array('Base', $MessageCache) || InArrayI($Location, $MessageCache)) {
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
      $Menu->AddItem('Site Settings', 'Site Settings');
      $Menu->AddLink('Site Settings', 'General', 'garden/settings/configure', 'Garden.Settings.Manage');
      $Menu->AddLink('Site Settings', 'Routes', 'garden/routes', 'Garden.Routes.Manage');
      $Menu->AddLink('Site Settings', 'Messages', 'garden/messages', 'Garden.Messages.Manage');
      
      $Menu->AddItem('Add-ons', 'Add-ons');
      $Menu->AddLink('Add-ons', 'Applications', 'garden/settings/applications', 'Garden.Applications.Manage');
      $Menu->AddLink('Add-ons', 'Plugins', 'garden/settings/plugins', 'Garden.Applications.Manage');
      $Menu->AddLink('Add-ons', 'Themes', 'garden/settings/themes', 'Garden.Themes.Manage');

      $Menu->AddItem('Users', 'Users');
      $Menu->AddLink('Users', 'Users', 'garden/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
      $Menu->AddLink('Users', 'Roles & Permissions', 'garden/role', 'Garden.Roles.Manage');
      $Menu->AddLink('Users', 'Registration', 'garden/settings/registration', 'Garden.Registration.Manage');
      if (Gdn::Config('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', 'Applicants', 'garden/user/applicants', 'Garden.Applicants.Manage');
   }
}