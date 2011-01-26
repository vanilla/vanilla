<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/


class UpdateHooks implements Gdn_IPlugin {

   public function Base_UpdateAppSettingsMenuItems_Handler(&$Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      $Menu->AddItem('Update', T('Update'), FALSE, array('class' => 'UpdateSettings'));
      $Menu->AddLink('Update', T('Check for Updates'), 'update/check', 'Garden.Settings.Manage');
      $Menu->AddLink('Update', T('Backups'), 'update/backup', 'Garden.Settings.Manage');
   }
   
   public function Base_GetAppSettingsMenuItems_Handler($Sender) {
      $Menu = &$Sender->EventArguments['SideMenu'];
      
      $Menu->AddItem('Site Settings', T('Settings'), FALSE, array('class' => 'SiteSettings'));
      $Menu->AddLink('Site Settings', T('Update'), 'update/check', 'Garden.Settings.Manage');
   }
   
   public function Setup() {
      // Got Setup?
   }
}
