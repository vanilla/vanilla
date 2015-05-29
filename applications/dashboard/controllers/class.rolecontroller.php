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
     * @var RoleModel
     */
    public $RoleModel;

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
        if (!$this->_Permission())
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
        if (!$this->_Permission($RoleID))
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
     * Show a warning if default roles are not setup yet.
     *
     * @since 2.0.?
     * @access public
     */
    public function DefaultRolesWarning() {
        // Do nothing (for now).
    }

    /**
     * Edit a role.
     *
     * @since 2.0.0
     * @access public
     */
    public function Edit($RoleID = FALSE) {
        if (!$this->_Permission($RoleID)) {
            return;
        }

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
            $Permissions = $PermissionModel->GetPermissionsEdit($RoleID ? $RoleID : 0, $LimitToSuffix);
            // Remove permissions the user doesn't have access to.
            if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
                foreach ($this->RoleModel->RankPermissions as $Permission) {
                    if (Gdn::Session()->CheckPermission($Permission)) {
                        continue;
                    }

                    list($Px, $Sx) = explode('.', $Permission, 2);
                    unset($Permissions['_'.$Px][$Sx]);
                }
            }

            $this->SetData('PermissionData', $Permissions, true);

            $this->Form->SetData($this->Role);
        } else {
            $this->RemoveRankPermissions();

            // If the form has been posted back...
            // 2. Save the data (validation occurs within):
            if ($RoleID = $this->Form->Save()) {
                $this->InformMessage(T('Your changes have been saved.'));
                $this->RedirectUrl = Url('dashboard/role');
                // Reload the permission data.
                $this->SetData('PermissionData', $PermissionModel->GetPermissionsEdit($RoleID, $LimitToSuffix), true);
            }
        }

        $this->SetData('_Types', $this->RoleModel->getDefaultTypes(true));

        $this->Render();
    }

    /**
     * Show list of roles.
     *
     * @since 2.0.0
     * @access public
     */
    public function Index($RoleID = NULL) {
        $this->_Permission();

        $this->AddSideMenu('dashboard/role');
        $this->AddJsFile('jquery.tablednd.js');
        $this->AddJsFile('jquery-ui.js');
        $this->Title(T('Roles & Permissions'));

        if (!$RoleID) {
            $RoleData = $this->RoleModel->GetWithRankPermissions()->ResultArray();

            // Check to see which roles can be modified.
            foreach ($RoleData as &$Row) {
                $CanModify = TRUE;

                if (!Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
                    foreach ($this->RoleModel->RankPermissions as $Permission) {
                        if ($Row[$Permission] && !Gdn::Session()->CheckPermission($Permission)) {
                            $CanModify = FALSE;
                            break;
                        }
                    }
                }
                $Row['CanModify'] = $CanModify;
            }
        } else {
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
    protected function _Permission($RoleID = NULL) {
        $this->Permission(array('Garden.Settings.Manage', 'Garden.Roles.Manage'), FALSE);

        if ($RoleID && !CheckPermission('Garden.Settings.Manage')) {
            // Make sure the user can assign this role.
            $Assignable = $this->RoleModel->GetAssignable();
            if (!isset($Assignable[$RoleID])) {
                throw PermissionException('@'.T("You don't have permission to modify this role."));
            }
        }
        return TRUE;
    }

    protected function RemoveRankPermissions() {
        if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            return;
        }

        // Remove ranking permissions.
        $Permissions = $this->Form->GetFormValue('Permission');
        foreach ($this->RoleModel->RankPermissions as $Permission) {
            if (!Gdn::Session()->CheckPermission($Permission) && in_array($Permission, $Permissions)) {
                $Index = array_search($Permission, $Permissions);
                unset($Permissions[$Index]);
            }
        }
        $this->Form->SetFormValue('Permission', $Permissions);
    }
}
