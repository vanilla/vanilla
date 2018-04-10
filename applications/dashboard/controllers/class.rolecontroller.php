<?php
/**
 * RBAC (Role Based Access Control) system.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /role endpoint.
 */
class RoleController extends DashboardController {

    /** @var bool Should categories be hidden when editing a role? */
    private $hideCategoryPermissions;

    /** @var Gdn_Form */
    public $Form;

    /** @var array Models to automatically instantiate. */
    public $Uses = ['Database', 'Form', 'RoleModel'];

    /** @var RoleModel */
    public $RoleModel;

    /**
     * RoleController constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->hideCategoryPermissions = c('Vanilla.HideRoleCategoryPermissions', false);
    }

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
    public function delete($roleID = false) {
        if (!$this->_permission($roleID)) {
            return;
        }

        $this->title(t('Delete Role'));
        $this->setHighlightRoute('dashboard/role');

        $role = $this->RoleModel->getByRoleID($roleID);
        if ($role->Deletable == '0') {
            $this->Form->addError('You cannot delete this role.');
        }

        // Make sure the form knows which item we are deleting.
        $this->Form->addHidden('RoleID', $roleID);

        // Figure out how many users will be affected by this deletion
        $this->AffectedUsers = $this->RoleModel->getUserCount($roleID);

        // Figure out how many users will be orphaned by this deletion
        $this->OrphanedUsers = $this->RoleModel->getUserCount($roleID, true);

        // Get a list of roles other than this one that can act as a replacement
        $this->ReplacementRoles = $this->RoleModel->getByNotRoleID($roleID);

        if ($this->Form->authenticatedPostBack()) {
            // Make sure that a replacement role has been selected if there were going to be orphaned users
            if ($this->OrphanedUsers > 0) {
                $validation = new Gdn_Validation();
                $validation->applyRule('ReplacementRoleID', 'Required', 'You must choose a replacement role for orphaned users.');
                $validation->validate($this->Form->formValues());
                $this->Form->setValidationResults($validation->results());
            }
            if ($this->Form->errorCount() == 0) {
                // Go ahead and delete the Role
                $this->RoleModel->deleteAndReplace($roleID, $this->Form->getValue('ReplacementRoleID'));
                $this->setRedirectTo('dashboard/role');
                $this->informMessage(t('Deleting role...'));
            }
        }
        $this->render();
    }

    /**
     * Edit a role.
     *
     * @param int|bool $RoleID
     * @since 2.0.0
     * @access public
     */
    public function edit($roleID = false) {
        if (!$this->_permission($roleID)) {
            return;
        }

        if ($this->title() == '') {
            $this->title(t('Edit Role'));
        }

        $this->setHighlightRoute('dashboard/role');
        $permissionModel = Gdn::permissionModel();
        $this->Role = $this->RoleModel->getByRoleID($roleID);
        // $this->EditablePermissions = is_object($this->Role) ? $this->Role->EditablePermissions : '1';
        $this->addJsFile('jquery.gardencheckboxgrid.js');

        // Set the model on the form.
        $this->Form->setModel($this->RoleModel);

        // Make sure the form knows which item we are editing.
        $this->Form->addHidden('RoleID', $roleID);

        $limitToSuffix = !$this->Role || $this->Role->CanSession == '1' ? '' : 'View';

        // If seeing the form for the first time...
        if ($this->Form->authenticatedPostBack() === false) {
            // Get the role data for the requested $RoleID and put it into the form.
            $permissions = $permissionModel->getPermissionsEdit(
                $roleID ? $roleID : 0,
                $limitToSuffix,
                $this->hideCategoryPermissions === false
            );

            // Remove permissions the user doesn't have access to.
            if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                foreach ($this->RoleModel->RankPermissions as $permission) {
                    if (Gdn::session()->checkPermission($permission)) {
                        continue;
                    }

                    list($px, $sx) = explode('.', $permission, 2);
                    unset($permissions['_'.$px][$sx]);
                }
            }

            $this->setData('PermissionData', $permissions, true);

            $this->Form->setData($this->Role);
        } else {
            $this->removeRankPermissions();

            // Make sure the role's checkbox has a false value so that the role model can handle a sparse update of
            // column from other places.
            if (!$this->Form->getFormValue('PersonalInfo')) {
                $this->Form->setFormValue('PersonalInfo', false);
            }

            if ($this->hideCategoryPermissions) {
                $this->Form->setFormValue('IgnoreCategoryPermissions', true);
            }

            // If the form has been posted back...
            // 2. Save the data (validation occurs within):
            if ($roleID = $this->Form->save()) {
                if ($this->deliveryType() === DELIVERY_TYPE_DATA) {
                    $this->index($roleID);
                    return;
                }

                $permissionData = $permissionModel->getPermissionsEdit(
                    $roleID,
                    $limitToSuffix,
                    $this->hideCategoryPermissions === false
                );

                $this->informMessage(t('Your changes have been saved.'));
                $this->setRedirectTo('dashboard/role');
            } else {
                $overrides = $this->Form->getFormValue('Permission');
                $permissionData = $permissionModel->getPermissionsEdit(
                    $roleID,
                    $limitToSuffix,
                    $this->hideCategoryPermissions === false,
                    $overrides
                );
            }
            // Reload the permission data.
            $this->setData('PermissionData', $permissionData, true);
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
    public function index($roleID = null) {
        $this->_permission();

        $this->setHighlightRoute('dashboard/role');
        $this->addJsFile('jquery.tablednd.js');
        $this->title(t('Roles & Permissions'));

        if (!$roleID) {
            $roles = $this->RoleModel->getWithRankPermissions()->resultArray();

            // Check to see which roles can be modified.
            foreach ($roles as &$row) {
                $canModify = true;

                if (!Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                    foreach ($this->RoleModel->RankPermissions as $permission) {
                        if ($row[$permission] && !Gdn::session()->checkPermission($permission)) {
                            $canModify = false;
                            break;
                        }
                    }
                }
                $row['CanModify'] = $canModify;
            }
            $this->setData('Roles', $roles);
        } elseif ($this->deliveryType() === DELIVERY_TYPE_DATA) {
            // This is an API request. Get the role in a nicer format.
            $role = $this->RoleModel->getID($roleID, DATASET_TYPE_ARRAY);

            // Get the global permissions.
            $permissions = Gdn::permissionModel()->getGlobalPermissions($roleID);
            unset($permissions['PermissionID']);

            // Get the per-category permissions.
            $permissions['Category'] = $this->RoleModel->getCategoryPermissions($roleID);

            $role['Permissions'] = $permissions;

            $this->setData('Role', $role);
            saveToConfig('Api.Clean', false, false);
        } else {
            $Role = $this->RoleModel->getID($roleID);
            $this->setData('Roles', [$Role]);
        }

        // Grab the total users for each role.
        if (is_array($this->data('Roles'))) {
            $pastThreshold = Gdn::userModel()->pastUserMegaThreshold();
            $thresholdTypeExceptions = RoleModel::getDefaultTypes();
            unset($thresholdTypeExceptions[RoleModel::TYPE_MEMBER]);

            $roles = $this->data('Roles');
            foreach ($roles as &$role) {
                if ($pastThreshold && !in_array($role['Type'], $thresholdTypeExceptions)) {
                    $countUsers = t('View');
                } else {
                    $countUsers = $this->RoleModel->getUserCount($role['RoleID']);
                }

                $role['CountUsers'] = $countUsers;
            }
            $this->setData('Roles', $roles);
        }

        $this->render();
    }

    /**
     * Do permission check.
     *
     * @since 2.0.0
     * @access protected
     */
    protected function _permission($roleID = null) {
        $this->permission(['Garden.Settings.Manage', 'Garden.Roles.Manage'], false);

        if ($roleID && !checkPermission('Garden.Settings.Manage')) {
            // Make sure the user can assign this role.
            $assignable = $this->RoleModel->getAssignable();
            if (!isset($assignable[$roleID])) {
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
        $permissions = $this->Form->getFormValue('Permission');
        foreach ($this->RoleModel->RankPermissions as $permission) {
            if (!Gdn::session()->checkPermission($permission) && in_array($permission, $permissions)) {
                $index = array_search($permission, $permissions);
                unset($permissions[$index]);
            }
        }
        $this->Form->setFormValue('Permission', $permissions);
    }
}
