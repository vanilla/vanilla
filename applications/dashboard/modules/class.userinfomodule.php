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
 * Renders information about a user in the user profile (email, join date, visits, etc).
 */
class UserInfoModule extends Gdn_Module {
   
   public $User;
   public $Roles;
   
   public function __construct($Sender = '') {
      $this->User = FALSE;
      $this->Path(__FILE__);
      parent::__construct($Sender);
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   public function LoadData() {
      $UserID = Gdn::Controller()->Data('Profile.UserID', Gdn::Session()->UserID);
      $this->User = Gdn::UserModel()->GetID($UserID);
      $this->Roles = Gdn::UserModel()->GetRoles($UserID)->ResultArray();
   }

   public function ToString() {
      if (!$this->User)
         $this->LoadData();
      
      if (is_object($this->User))
         return parent::ToString();

      return '';
   }
}