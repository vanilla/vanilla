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
class RoleController extends DashboardController {
   
   public $Uses = array('Database', 'Form', 'RoleModel');
   
   public function Add() {
		if(!$this->_Permission())
			return;

      $this->Title(T('Add Role'));

      // Use the edit form with no roleid specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   public function Delete($RoleID = FALSE) {
		if(!$this->_Permission())
			return;

		$this->Title(T('Delete Role'));
      $this->AddSideMenu('dashboard/role');
      
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
            $this->RedirectUrl = Url('dashboard/role');
            $this->StatusMessage = T('Deleting role...');
         }
      }
      $this->Render();
   }

   public function DefaultRoles() {
      $this->Permission('Garden.Roles.Manage');
      $this->AddSideMenu('');

      $this->Title(T('Default Roles'));

      // Load roles for dropdowns.
      $RoleModel = new RoleModel();
      $this->SetData('RoleData', $RoleModel->Get());

      if ($this->Form->AuthenticatedPostBack() === FALSE) {
         // Get a list of default member roles from the config.
         $DefaultRoles = C('Garden.Registration.DefaultRoles');
         $this->Form->SetValue('DefaultRoles', $DefaultRoles);

         // Get the guest roles.
         $GuestRolesData = $RoleModel->GetByUserID(0);
         $GuestRoles = ConsolidateArrayValuesByKey($GuestRolesData, 'RoleID');
         $this->Form->SetValue('GuestRoles', $GuestRoles);

         // The applicant role.
         $ApplicantRoleID = C('Garden.Registration.ApplicantRoleID', '');
         $this->Form->SetValue('ApplicantRoleID', $ApplicantRoleID);
      } else {
         $DefaultRoles = $this->Form->GetFormValue('DefaultRoles');
         $ApplicantRoleID = $this->Form->GetFormValue('ApplicantRoleID');
         SaveToConfig(array(
            'Garden.Registration.DefaultRoles' => $DefaultRoles,
            'Garden.Registration.ApplicantRoleID' => $ApplicantRoleID));

         $GuestRoles = $this->Form->GetFormValue('GuestRoles');
         $UserModel = new UserModel();
         $UserModel->SaveRoles(0, $GuestRoles, FALSE);

         $this->StatusMessage = T("Saved");
      }

      $this->Render();
   }

   public function DefaultRolesWarning() {
      // Check to see if there are no default roles for guests or members.
      $DefaultRolesWarning = FALSE;
      $DefaultRoles = C('Garden.Registration.DefaultRoles');
      if (!is_array($DefaultRoles) || count($DefaultRoles) == 0) {
         $DefaultRolesWarning = TRUE;
      } elseif (!C('Garden.Registration.ApplicantRoleID') && C('Garden.Registration.Method') == 'Approval') {
         $DefaultRolesWarning = TRUE;
      } else {
         $RoleModel = new RoleModel();
         $GuestRoles = $RoleModel->GetByUserID(0);
         if($GuestRoles->NumRows() == 0)
            $DefaultRolesWarning = TRUE;
      }

      if ($DefaultRolesWarning) {
         echo '<div class="Messages Errors"><ul><li>',
            sprintf(T('No default roles.', 'You don\'t have your default roles set up. To correct this problem click %s.'),
            Anchor(T('here'), 'dashboard/role/defaultroles')),
            '</div>';
      }
   }
   
   public function Edit($RoleID = FALSE) {
		if(!$this->_Permission())
			return;

      if ($this->Head && $this->Head->Title() == '')
         $this->Head->Title(T('Edit Role'));
         
      $this->AddSideMenu('dashboard/role');
      $PermissionModel = Gdn::PermissionModel();
      $this->Role = $this->RoleModel->GetByRoleID($RoleID);
      // $this->EditablePermissions = is_object($this->Role) ? $this->Role->EditablePermissions : '1';
      $this->AddJsFile('jquery.gardencheckboxgrid.js');
      
      // Set the model on the form.
      $this->Form->SetModel($this->RoleModel);
      
      // Make sure the form knows which item we are editing.
      $this->Form->AddHidden('RoleID', $RoleID);
      
      $LimitToSuffix = !$this->Role || $this->Role->CanSession == '1' ? '' : 'View';
      
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
            $this->RedirectUrl = Url('dashboard/role');
            // Reload the permission data.
            $this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit($RoleID, $LimitToSuffix), true);
         }
      }
      
      $this->Render();
   }
      
   public function Index() {
		$this->Permission('Garden.Roles.Manage');

      $this->AddSideMenu('dashboard/role');
      $this->AddJsFile('jquery.tablednd.js');
      $this->AddJsFile('jquery.ui.packed.js');
      $this->Title(T('Roles & Permissions'));
      $this->RoleData = $this->RoleModel->Get();
      $this->Render();
   }
   
   public function Initialize() {
      parent::Initialize();
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }

	protected function _Permission() {
      $this->Permission('Garden.Roles.Manage');
		if(!C('Garden.Roles.Manage', TRUE)) {
			Gdn::Dispatcher()->Dispatch('DefaultPermission');
			return FALSE;
		}
		return TRUE;
	}
}