<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class DashboardHooks implements Gdn_IPlugin {
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
            $Sender->AddAsset('Foot', $Sender->FetchView('previewtheme', 'settingscontroller', 'dashboard'));
            $Sender->AddCssFile('previewtheme.css');
         }
      }

      // Add Message Modules (if necessary)
      $MessageCache = Gdn::Config('Garden.Messages.Cache', array());
      $Location = $Sender->Application.'/'.substr($Sender->ControllerName, 0, -10).'/'.$Sender->RequestMethod;
		$Exceptions = array('[Base]');
		if ($Sender->MasterView == 'admin')
			$Exceptions[] = '[Admin]';
		else if (in_array($Sender->MasterView, array('', 'default')))
			$Exceptions[] = '[NonAdmin]';
			
      if ($Sender->MasterView != 'empty' && ArrayInArray($Exceptions, $MessageCache, FALSE) || InArrayI($Location, $MessageCache)) {
         $MessageModel = new MessageModel();
         $MessageData = $MessageModel->GetMessagesForLocation($Location, $Exceptions);
         foreach ($MessageData as $Message) {
            $MessageModule = new MessageModule($Sender, $Message);
            $Sender->AddModule($MessageModule);
         }
      }
		// If there are applicants, alert admins by showing in the main menu
		if (in_array($Sender->MasterView, array('', 'default')) && $Sender->Menu && C('Garden.Registration.Method') == 'Approval') {
			$CountApplicants = Gdn::UserModel()->GetApplicants()->NumRows();
			if ($CountApplicants > 0)
				$Sender->Menu->AddLink('Applicants', T('Applicants').' <span>'.$CountApplicants.'</span>', '/dashboard/user/applicants', array('Garden.Registration.Manage'));
		}
   }
   
   public function Base_GetAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Dashboard', T('Dashboard'), FALSE, array('class' => 'Dashboard'));
      $Menu->AddLink('Dashboard', T('Dashboard'), '/dashboard/settings', 'Garden.Settings.Manage');

      $Menu->AddItem('Appearance', T('Appearance'), FALSE, array('class' => 'Appearance'));
		$Menu->AddLink('Appearance', T('Banner'), '/dashboard/settings/banner', 'Garden.Settings.Manage');
      $Menu->AddLink('Appearance', T('Themes'), '/dashboard/settings/themes', 'Garden.Themes.Manage');
      if ($ThemeOptionsName = C('Garden.ThemeOptions.Name')) 
         $Menu->AddLink('Appearance', T('Theme Options'), '/dashboard/settings/themeoptions', 'Garden.Themes.Manage');

		$Menu->AddLink('Appearance', T('Messages'), '/dashboard/message', 'Garden.Messages.Manage');

      $Menu->AddItem('Users', T('Users'), FALSE, array('class' => 'Users'));
      $Menu->AddLink('Users', T('Users'), '/dashboard/user', array('Garden.Users.Add', 'Garden.Users.Edit', 'Garden.Users.Delete'));
		$Menu->AddLink('Users', T('Roles & Permissions'), 'dashboard/role', 'Garden.Roles.Manage');
		$Menu->AddLink('Users', T('Authentication'), 'dashboard/authentication', 'Garden.Settings.Manage');
			
      if (C('Garden.Registration.Manage', TRUE))
			$Menu->AddLink('Users', T('Registration'), 'dashboard/settings/registration', 'Garden.Registration.Manage');
			
      if (C('Garden.Registration.Method') == 'Approval')
         $Menu->AddLink('Users', T('Applicants'), 'dashboard/user/applicants', 'Garden.Applicants.Manage');
		
		$Menu->AddItem('Forum', T('Forum Settings'), FALSE, array('class' => 'Forum'));
		
		
		$Menu->AddItem('Add-ons', T('Addons'), FALSE, array('class' => 'Addons'));
      $Menu->AddLink('Add-ons', T('Plugins'), 'dashboard/settings/plugins', 'Garden.Plugins.Manage');
      $Menu->AddLink('Add-ons', T('Applications'), 'dashboard/settings/applications', 'Garden.Applications.Manage');
      $Menu->AddLink('Add-ons', T('Locales'), 'dashboard/settings/locales', 'Garden.Settings.Manage');

      $Menu->AddItem('Site Settings', T('Settings'), FALSE, array('class' => 'SiteSettings'));
      $Menu->AddLink('Site Settings', T('Outgoing Email'), 'dashboard/settings/email', 'Garden.Settings.Manage');
      $Menu->AddLink('Site Settings', T('Routes'), 'dashboard/routes', 'Garden.Routes.Manage');
		
		$Menu->AddItem('Import', T('Import'), FALSE, array('class' => 'Import'));
		$Menu->AddLink('Import', T('Import'), 'dashboard/import', 'Garden.Import');
		
   }
}