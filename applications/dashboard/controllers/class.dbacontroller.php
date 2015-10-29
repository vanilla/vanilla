<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.1
 */

/**
 * Handles the admin utility /dba endpoint.
 */
class DbaController extends DashboardController {

    /** @var Gdn_Form */
    public $Form = null;

    /** @var DBAModel */
    public $Model = null;

    /**
     * Runs before every call to this controller.
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
        $this->Model = new DBAModel();
        $this->Form = new Gdn_Form();
        $this->Form->InputPrefix = '';

        $this->addJsFile('dba.js');
    }

    /**
     * Recalculate counters.
     *
     * @param bool $Table
     * @param bool $Column
     * @param bool $From
     * @param bool $To
     * @param bool $Max
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function counts($Table = false, $Column = false, $From = false, $To = false, $Max = false) {
        set_time_limit(300);
        $this->permission('Garden.Settings.Manage');

        if ($Table && $Column && strcasecmp($this->Request->requestMethod(), Gdn_Request::INPUT_POST) == 0) {
            if (!ValidateRequired($Table)) {
                throw new Gdn_UserException("Table is required.");
            }
            if (!ValidateRequired($Column)) {
                throw new Gdn_UserException("Column is required.");
            }

            $Result = $this->Model->counts($Table, $Column, $From, $To);
            $this->setData('Result', $Result);
        } else {
            $this->setData('Jobs', array());
            $this->fireEvent('CountJobs');
        }

        $this->setData('Title', t('Recalculate Counts'));
        $this->addSideMenu();
        $this->render('Job');
    }

    /**
     * Set Member-like permissions on all roles with missing permissions.
     *
     * Useful for after an import that didn't include permissions
     * but did include a whole lotta roles you don't want to edit manually.
     */
    public function fixPermissions() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            $Result = $this->Model->fixPermissions();
            $this->setData('Result', $Result);
        }

        $this->setData('Title', "Fix missing permission records after import");
        $this->_setJob($this->data('Title'));
        $this->addSideMenu();
        $this->render('Job');
    }

    /**
     * Fix the category tree after an import that only gives a sort & parent.
     */
    public function rebuildCategoryTree() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            $CategoryModel = new CategoryModel();
            $CategoryModel->rebuildTree();
            $this->setData('Result', array('Complete' => true));
        }

        $this->setData('Title', "Fix category tree from an import.");
        $this->_setJob($this->data('Title'));
        $this->addSideMenu();
        $this->render('Job');
    }

    /**
     *
     *
     * @param $Table
     * @param $Column
     */
    public function fixUrlCodes($Table, $Column) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            $Result = $this->Model->fixUrlCodes($Table, $Column);
            $this->setData('Result', $Result);
        }

        $this->setData('Title', "Fix url codes for $Table.$Column");
        $this->_setJob($this->data('Title'));
        $this->addSideMenu();
        $this->render('Job');
    }

    /**
     * Scan a table for invalid InsertUserID values and update with SystemUserID
     *
     * @param bool|string $Table The name of the table to fix InsertUserID in.
     */
    public function fixInsertUserID($Table = false) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack() && $Table) {
            $this->Model->fixInsertUserID($Table);

            $this->setData(
                'Result',
                array('Complete' => true)
            );
        } else {
            $Tables = array(
                'Fix comments' => 'Comment',
                'Fix discussions' => 'Discussion'
            );
            $Jobs = array();

            foreach ($Tables as $CurrentLabel => $CurrentTable) {
                $Jobs[$CurrentLabel] = "/dba/fixinsertuserid.json?".http_build_query(array('table' => $CurrentTable));
            }
            unset($CurrentLabel, $CurrentTable);

            $this->setData('Jobs', $Jobs);
        }

        $this->setData('Title', t('Fix Invalid InsertUserID'));
        $this->addSideMenu();
        $this->render('Job');
    }

    /**
     * Look for users with an invalid role and apply the role specified to those users.
     */
    public function fixUserRole() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            if (ValidateRequired($this->Form->getFormValue('DefaultUserRole'))) {
                $this->Model->fixUserRole($this->Form->getFormValue('DefaultUserRole'));
                $this->setData('CompletedFix', true);
            }
        }

        $this->addSideMenu();
        $this->render();
    }

    /**
     * Reset all role permissions based on role type.
     */
    public function resetPermissions() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            PermissionModel::resetAllRoles();
            $this->setData('Result', array('Complete' => true));
        }

        $this->setData('Title', 'Reset all role permissions');
        $this->_setJob($this->data('Title'));
        $this->addSideMenu();
        $this->render('Job');
    }

    protected function _setJob($Name) {
        $Args = array_change_key_case($this->ReflectArgs);
        $Url = "/dba/{$this->RequestMethod}.json?".http_build_query($Args);
        $this->Data['Jobs'][$Name] = $Url;
    }
}
