<?php
/**
 * RBAC (Role Based Access Control) system.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /role endpoint.
 */
class RoleController extends DashboardController {

    /** @var array Models to automatically instantiate. */
    public $Uses = array('Database', 'Form', 'RoleModel');

    /** @var RoleModel */
    public $RoleModel;

    /**
     * Set menu path. Automatically run on every use.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        if ($this->Menu) {
            $this->Menu->highlightRoute('/dashboard/settings');
        }
    }

    /**
     * Create new role.
     *
     * @since 2.0.0
     * @access public
     */
    public function add() {
        if (!$this->_permission()) {
            return;
        }

        $this->title(t('Add Role'));

        // Use the edit form with no roleid specified.
        $this->View = 'Edit';
        $this->edit();
    }

    /**
     * Remove a role.
     *
     * @since 2.0.0
     * @access public
     */
    public function delete($RoleID = false) {
        if (!$this->_permission($RoleID)) {
            return;
        }

        $this->title(t('Delete Role'));
        $this->addSideMenu('dashboard/role');

        $Role = $this->RoleModel->getByRoleID($RoleID);
        if ($Role->Deletable == '0') {
            $this->Form->addError('You cannot delete this role.');
        }

        // Make sure the form knows which item we are deleting.
        $this->Form->addHidden('RoleID', $RoleID);

        // Figure out how many users will be affected by this deletion
        $this->AffectedUsers = $this->RoleModel->getUserCount($RoleID);

        // Figure out how many users will be orphaned by this deletion
        $this->OrphanedUsers = $this->RoleModel->getUserCount($RoleID, true);

        // Get a list of roles other than this one that can act as a replacement
        $this->ReplacementRoles = $this->RoleModel->getByNotRoleID($RoleID);

        if ($this->Form->authenticatedPostBack()) {
            // Make sure that a replacement role has been selected if there were going to be orphaned users
            if ($this->OrphanedUsers > 0) {
                $Validation = new Gdn_Validation();
                $Validation->applyRule('ReplacementRoleID', 'Required', 'You must choose a replacement role for orphaned users.');
                $Validation->validate($this->Form->formValues());
                $this->Form->setValidationResults($Validation->results());
            }
            if ($this->Form->errorCount() == 0) {
                // Go ahead and delete the Role
                $this->RoleModel->delete($RoleID, $this->Form->getValue('ReplacementRoleID'));
                $this->RedirectUrl = url('dashboard/role');
                $this->informMessage(t('Deleting role...'));
            }
        }
        $this->render();
    }

    /**
     * Show a warning if default roles are not setup yet.
     *
     * @since 2.0.?
     * @access public
     */
    public function defaultRolesWarning() {
        // Do nothing (for now).
    }

    /**
     * Edit a role.
     *
     * @since 2.0.0
     * @access public
     */
    public function edit($RoleID = false) {
        if (!$this->_permission($RoleID)) {
            return;
        }

        if ($this->Head && $this->Head->title() == '') {
            $this->Head->title(t('Edit Role'));
        }

        $this->addSideMenu('dashboard/role');
        $PermissionModel = Gdn::permissionModel();
        $this->Role = $this->RoleModel->getByRoleID($RoleID);
        // $this->EditablePermissions = is_object($this->Role) ? $this->Role->EditablePermissions : '1';
        $this->addJsFile('jquery.gardencheckboxgrid.js');

        // Set the model on the form.
        $this->Form->setModel($this->RoleModel);

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('RoleID', $RoleID);

        $LimitToSuffix = !$this->Role || $this->Role->CanSession == '1' ? '' : 'View';

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Get the role data for the requested $RoleID and put it into the form.
            $Permissions = $PermissionModel->getPermissionsEdit($RoleID ? $RoleID : 0, $LimitToSuffix);
            // Remove permissions the user doesn't have access to.
            if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                foreach ($this->RoleModel->RankPermissions as $Permission) {
                    if (Gdn::session()->checkPermission($Permission)) {
                        continue;
                    }

                    list($Px, $Sx) = explode('.', $Permission, 2);
                    unset($Permissions['_'.$Px][$Sx]);
                }
            }

            $this->setData('PermissionData', $Permissions, true);

            $this->Form->setData($this->Role);
        } else {
            $this->removeRankPermissions();

            // If the form has been posted back...
            // 2. Save the data (validation occurs within):
            if ($RoleID = $this->Form->save()) {
                $this->informMessage(t('Your changes have been saved.'));
                $this->RedirectUrl = url('dashboard/role');
                // Reload the permission data.
                $this->setData('PermissionData', $PermissionModel->getPermissionsEdit($RoleID, $LimitToSuffix), true);
            }
        }

        $this->setData('_Types', $this->RoleModel->getDefaultTypes(true));

        $this->render();
    }

    /**
     * Show list of roles.
     *
     * @since 2.0.0
     * @access public
     */
    public function index($RoleID = null) {
        $this->_permission();

        $this->addSideMenu('dashboard/role');
        $this->addJsFile('jquery.tablednd.js');
        $this->addJsFile('jquery-ui.js');
        $this->title(t('Roles & Permissions'));

        if (!$RoleID) {
            $RoleData = $this->RoleModel->getWithRankPermissions()->resultArray();

            // Check to see which roles can be modified.
            foreach ($RoleData as &$Row) {
                $CanModify = true;

                if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                    foreach ($this->RoleModel->RankPermissions as $Permission) {
                        if ($Row[$Permission] && !Gdn::session()->checkPermission($Permission)) {
                            $CanModify = false;
                            break;
                        }
                    }
                }
                $Row['CanModify'] = $CanModify;
            }
        } else {
            $Role = $this->RoleModel->getID($RoleID);
            $RoleData = array($Role);
        }

        $this->setData('Roles', $RoleData);
        $this->render();
    }

    /**
     * Do permission check.
     *
     * @since 2.0.0
     * @access protected
     */
    protected function _permission($RoleID = null) {
        $this->permission(array('Garden.Settings.Manage', 'Garden.Roles.Manage'), false);

        if ($RoleID && !checkPermission('Garden.Settings.Manage')) {
            // Make sure the user can assign this role.
            $Assignable = $this->RoleModel->getAssignable();
            if (!isset($Assignable[$RoleID])) {
                throw permissionException('@'.t("You don't have permission to modify this role."));
            }
        }
        return true;
    }

    /**
     *
     */
    protected function removeRankPermissions() {
        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            return;
        }

        // Remove ranking permissions.
        $Permissions = $this->Form->getFormValue('Permission');
        foreach ($this->RoleModel->RankPermissions as $Permission) {
            if (!Gdn::session()->checkPermission($Permission) && in_array($Permission, $Permissions)) {
                $Index = array_search($Permission, $Permissions);
                unset($Permissions[$Index]);
            }
        }
        $this->Form->setFormValue('Permission', $Permissions);
    }
}
