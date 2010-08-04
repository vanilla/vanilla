<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ConditionModule extends Gdn_Module {
   protected $_Conditions = array();
   
   public $Prefix = 'Cond';

   public function Conditions($Value = NULL) {
      if ($Value !== NULL)
         $this->_Conditions = $Value;
      return $this->_Conditions;
   }

   public function ToString() {
      $Form = $this->_Sender->Form;

      if ($Form->IsPostBack()) {
         // Grab the conditions from the form and convert them to the conditions array.
      } else {
      }

      $this->Types = array_merge(array('' => '('.sprintf(T('Select a %s'), T('Condition Type', 'Type')).')'), Gdn_Condition::AllTypes());
      //die(print_r($this->Types));

      // Get all of the permissions that are valid for the permissions dropdown.
      $PermissionModel = new PermissionModel();
      $Permissions = $PermissionModel->GetGlobalPermissions(0);
      $Permissions = array_keys($Permissions);
      sort($Permissions);
      $Permissions = array_combine($Permissions, $Permissions);
      $Permissions = array_merge(array('' => '('.sprintf(T('Select a %s'), T('Permission')).')'), $Permissions);
      $this->Permissions = $Permissions;

      // Get all of the roles.
      $RoleModel = new RoleModel();
      $Roles = $RoleModel->GetArray();
      $Roles = array_merge(array('-' => '('.sprintf(T('Select a %s'), T('Role')).')'), $Roles);
      $this->Roles = $Roles;

      $this->Form = $Form;
      return parent::ToString();
   }

   protected function _FromForm() {
   }
}