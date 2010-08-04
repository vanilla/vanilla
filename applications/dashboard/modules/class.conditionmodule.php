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
   protected $_Conditions = NULL;
   
   public $Prefix = 'Cond';

   public function Conditions($Value = NULL) {
      if (is_array($Value))
         $this->_Conditions = $Value;
      elseif ($this->_Conditions === NULL) {
         if ($this->_Sender->Form->AuthenticatedPostBack()) {
            $this->_Conditions = $this->_FromForm();
         } else {
            $this->_Conditions = array();
         }
      }

      if ($Value === TRUE) {
         // Remove blank conditions from the array. This is used for saving.
         $Result = array();
         foreach($this->_Conditions as $Condition) {
            if (count($Condition) < 2 || !$Condition[0])
               continue;
            $Result[] = $Condition;
         }
         return $Result;
      }
      return $this->_Conditions;
   }

   public function ToString() {
      $Form = $this->_Sender->Form;
      $this->_Sender->AddJsFile('condition.js');

      if ($Form->AuthenticatedPostBack()) {
         // Grab the conditions from the form and convert them to the conditions array.
         $this->Conditions($this->_FromForm());
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

   /** Grab the values from the form into the conditions array. */
   protected function _FromForm() {
      $Form = new Gdn_Form();
      $Px = $this->Prefix;

      $Types = (array)$Form->GetFormValue($Px.'Type', array());
      $PermissionFields = (array)$Form->GetFormValue($Px.'PermissionField', array());
      $RoleFields = (array)$Form->GetFormValue($Px.'RoleField', array());
      $Fields = (array)$Form->GetFormValue($Px.'Field', array());
      $Expressions = (array)$Form->GetFormValue($Px.'Expr', array());

      $Conditions = array();
      for ($i = 0; $i < count($Types) - 1; $i++) { // last condition always template row.

         $Condition = array($Types[$i]);
         switch ($Types[$i]) {
            case Gdn_Condition::PERMISSION:
               $Condition[1] = GetValue($i, $PermissionFields, '');
               break;
            case Gdn_Condition::REQUEST:
               $Condition[1] = GetValue($i, $Fields, '');
               $Condition[2] = GetValue($i, $Expressions, '');
               break;
            case Gdn_Condition::ROLE:
               $Condition[1] = GetValue($i, $RoleFields);
               break;
            case '':
               $Condition[1] = '';
               break;
            default:
               continue;
         }
         $Conditions[] = $Condition;
      }
      return $Conditions;
   }
}