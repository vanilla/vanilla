<?php if (!defined('APPLICATION')) exit();

/**
 * RBAC (Role Based Access Control) system.
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class RoleController extends DashboardController {
   /** @var array Models to automatically instantiate. */
   public $Uses = array('Database', 'Form', 'RoleModel');
   
   /**
    * Set menu path. Automatically run on every use.
    *
    * @since 2.0.0
    * @access public
    */
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
      if ($this->Menu)
         $this->Menu->HighlightRoute('/dashboard/settings');
   }
   
   /**
    * Create new role.
    *
    * @since 2.0.0
    * @access public
    */
   public function Add() {
		if(!$this->_Permission())
			return;

      $this->Title(T('Add Role'));

      // Use the edit form with no roleid specified.
      $this->View = 'Edit';
      $this->Edit();
   }
   
   /**
    * Remove a role.
    *
    * @since 2.0.0
    * @access public
    */
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
            $this->InformMessage(T('Deleting role...'));
         }
      }
      $this->Render();
   }
   
   /**
    * Manage default role assignments.
    *
    * @since 2.0.?
    * @access public
    */
   public function DefaultRoles() {
      $this->Permission('Garden.Settings.Manage');
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

         $this->InformMessage(T("Saved"));
      }

      $this->Render();
   }
   
   /**
    * Show a warning if default roles are not setup yet.
    *
    * @since 2.0.?
    * @access public
    */
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
   
   /**
    * Edit a role.
    *
    * @since 2.0.0
    * @access public
    */
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
            $this->InformMessage(T('Your changes have been saved.'));
            $this->RedirectUrl = Url('dashboard/role');
            // Reload the permission data.
            $this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit($RoleID, $LimitToSuffix), true);
         }
      }
      
      $this->Render();
   }
   
   /**
    * Show list of roles.
    *
    * @since 2.0.0
    * @access public
    */
   public function Index($RoleID = NULL) {
		$this->Permission('Garden.Settings.Manage');

      $this->AddSideMenu('dashboard/role');
      $this->AddJsFile('jquery.tablednd.js');
      $this->AddJsFile('jquery-ui-1.8.17.custom.min.js');
      $this->Title(T('Roles & Permissions'));
      
      if (!$RoleID)
         $RoleData = $this->RoleModel->Get()->ResultArray();
      else {
         $Role = $this->RoleModel->GetID($RoleID);
         $RoleData = array($Role);
      }
      
      $this->SetData('Roles', $RoleData);
      $this->Render();
   }

   /**
    * Do permission check.
    *
    * @since 2.0.0
    * @access protected
    */
	protected function _Permission() {
      $this->Permission('Garden.Settings.Manage');
		return TRUE;
	}
}