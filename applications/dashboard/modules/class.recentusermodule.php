<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Renders the recently active users. Built for use in a side panel.
 */
class RecentUserModule extends Gdn_Module {
   
   public function __construct($Sender = '') {
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 20) {
      $UserModel = new UserModel();
      $this->_Sender->RecentUserData = $UserModel->GetActiveUsers($Limit);
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (!C('Garden.Modules.ShowRecentUserModule'))
         return '';

      $Data = $this->_Sender->RecentUserData;
      if ($Data !== FALSE && $Data->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}