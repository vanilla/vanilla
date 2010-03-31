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
 * RBAC (Role Based Access Control)
 */
class RoleController extends GardenController {
   
   public $Uses = array('Database', 'Form', 'Gdn_RoleModel');
   
   public function Add() {
      $this->Title(T('Add Role'));
         
      $this->Permission('Garden.Roles.Manage');
      
      // Load default permissions.
      //$PermissionModel = Gdn::PermissionModel();
      //$this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit(0, FALSE), TRUE);
      
      // Use the edit form with no roleid specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   public function Delete($RoleID = FALSE) {
      $this->Title(T('Delete Role'));
         
      $this->Permission('Garden.Roles.Manage');
      $this->AddSideMenu('garden/role');
      
      $Role = $this->RoleModel->GetByRoleID($RoleID);
      if ($Role->Deletable == '0')
         $this->Form->AddError('You cannot delete this role.');
      
      // Make sure the form knows which item we are deleting.
      $this->Form->AddHidden('RoleID', $RoleID);
      
      // Figure out how many users will be affected by this deletion
      $this->AffectedUsers = $this->RoleModel->GetUserCount($RoleID);
      
      // Figure out how many users will be orphaned by this deletion
      $this->OrphanedUsers = $this->RoleModel->GetUserCount($RoleID, TRUE);

      // Get a list of roles other than this one that can act as a replacement
      $this->ReplacementRoles = $this->RoleModel->GetByNotRoleID($RoleID);
      
      if ($this->Form->AuthenticatedPostBack()) {
         // Make sure that a replacement role has been selected if there were going to be orphaned users
         if ($this->OrphanedUsers > 0) {
            $Validation = new Gdn_Validation();
            $Validation->ApplyRule('ReplacementRoleID', 'Required', 'You must choose a replacement role for orphaned users.');
            $Validation->Validate($this->Form->FormValues());
            $this->Form->SetValidationResults($Validation->Results());
         }
         if ($this->Form->ErrorCount() == 0) {
            // Go ahead and delete the Role
            $this->RoleModel->Delete($RoleID, $this->Form->GetValue('ReplacementRoleID'));
            $this->RedirectUrl = Url('garden/role');
            $this->StatusMessage = T('Deleting role...');
         }
      }
      $this->Render();
   }
   
   //public $HasJunctionPermissionData;
   public function Edit($RoleID = FALSE) {
      if ($this->Head && $this->Head->Title() == '')
         $this->Head->Title(T('Edit Role'));
         
      $this->Permission('Garden.Roles.Manage');
      $this->AddSideMenu('garden/role');
      $PermissionModel = Gdn::PermissionModel();
      $this->Role = $this->RoleModel->GetByRoleID($RoleID);
      // $this->EditablePermissions = is_object($this->Role) ? $this->Role->EditablePermissions : '1';
      $this->AddJsFile('/js/library/jquery.gardencheckboxgrid.js');
      
      // Set the model on the form.
      $this->Form->SetModel($this->RoleModel);
      
      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('RoleID', $RoleID);
      
      $LimitToSuffix = !$this->Role || $this->Role->CanSession == '1' ? '' : 'View';
      
      // Load all permissions based on enabled applications and plugins
      //$this->SetData('PermissionData', $PermissionModel->GetPermissions($RoleID, $LimitToSuffix), TRUE);

      // If seeing the form for the first time...
      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Get the role data for the requested $RoleID and put it into the form.
         $this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit($RoleID ? $RoleID : 0, $LimitToSuffix), true);
            
         $this->Form->SetData($this->Role);
      } else {
         // If the form has been posted back...
         // 2. Save the data (validation occurs within):
         if ($RoleID = $this->Form->Save()) {
            $this->StatusMessage = T('Your changes have been saved.');
            $this->RedirectUrl = Url('garden/role');
            // Reload the permission data.
            $this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit($RoleID, $LimitToSuffix), true);
         }
      }
      
      $this->Render();
   }
      
   public function Index() {
      $this->Permission('Garden.Roles.Manage');
      $this->AddSideMenu('garden/role');
      $this->AddJsFile('/js/library/jquery.tablednd.js');
      $this->AddJsFile('/js/library/jquery.ui.packed.js');
      $this->Title(T('Roles & Permissions'));
      $this->RoleData = $this->RoleModel->Get();
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/garden/settings');
   }
}