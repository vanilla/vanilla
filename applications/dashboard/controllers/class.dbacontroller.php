<?php
/**
 * Contains useful functions for cleaning up the database.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
        $this->addCssFile('style.css');
        $this->addJsFile('dba.js');
    }

    /**
     * Recalculate counters.
     *
     * @param bool $table
     * @param bool $column
     * @param bool $from
     * @param bool $to
     * @param bool $max
     * @throws Exception
     * @throws Gdn_UserException
     */
    public function counts($table = false, $column = false, $from = false, $to = false, $max = false) {
        increaseMaxExecutionTime(300);
        $this->permission('Garden.Settings.Manage');

        if ($table && $column && strcasecmp($this->Request->requestMethod(), Gdn_Request::INPUT_POST) == 0) {
            if (!validateRequired($table)) {
                throw new Gdn_UserException("Table is required.");
            }
            if (!validateRequired($column)) {
                throw new Gdn_UserException("Column is required.");
            }

            $result = $this->Model->counts($table, $column, $from, $to);
            $this->setData('Result', $result);
        } else {
            $this->setData('Jobs', []);
            $this->fireEvent('CountJobs');
        }

        $this->setData('Title', t('Recalculate Counts'));
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
            $result = $this->Model->fixPermissions();
            $this->setData('Result', $result);
        }

        $this->setData('Title', "Fix missing permission records after import");
        $this->_setJob($this->data('Title'));
        $this->render('Job');
    }

    /**
     * Fix the category tree after an import that only gives a sort & parent.
     */
    public function rebuildCategoryTree() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            $categoryModel = new CategoryModel();
            $categoryModel->rebuildTree();
            $this->setData('Result', ['Complete' => true]);
        }

        $this->setData('Title', "Fix category tree from an import.");
        $this->_setJob($this->data('Title'));
        $this->render('Job');
    }

    /**
     *
     *
     * @param $table
     * @param $column
     */
    public function fixUrlCodes($table, $column) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            if (!$this->Model->isValidDatabaseIdentifier($table)) {
                throw new Exception('Invalid table.');
            }
            if (!$this->Model->isValidDatabaseIdentifier($column)) {
                throw new Exception('Invalid column.');
            }

            $result = $this->Model->fixUrlCodes($table, $column);
            $this->setData('Result', $result);
        }

        $this->setData('Title', 'Fix URL Codes');
        $this->_setJob("Fix url codes for $table.$column");
        $this->render('Job');
    }

    /**
     * Scan a table for invalid InsertUserID values and update with SystemUserID
     *
     * @param bool|string $table The name of the table to fix InsertUserID in.
     */
    public function fixInsertUserID($table = false) {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack() && $table) {
            if (!$this->Model->isValidDatabaseIdentifier($table)) {
                throw new Exception('Invalid table.');
            }

            $this->Model->fixInsertUserID($table);

            $this->setData(
                'Result',
                ['Complete' => true]
            );
        } else {
            $tables = [
                'Fix comments' => 'Comment',
                'Fix discussions' => 'Discussion'
            ];
            $jobs = [];

            foreach ($tables as $currentLabel => $currentTable) {
                $jobs[$currentLabel] = "/dba/fixinsertuserid.json?".http_build_query(['table' => $currentTable]);
            }
            unset($currentLabel, $currentTable);

            $this->setData('Jobs', $jobs);
        }

        $this->setData('Title', t('Fix Invalid InsertUserID'));
        $this->render('Job');
    }

    /**
     * Look for users with an invalid role and apply the role specified to those users.
     */
    public function fixUserRole() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            if (validateRequired($this->Form->getFormValue('DefaultUserRole'))) {
                $this->Model->fixUserRole($this->Form->getFormValue('DefaultUserRole'));
                $this->setData('CompletedFix', true);
            }
        }

        $this->render();
    }

    /**
     * Reset all role permissions based on role type.
     */
    public function resetPermissions() {
        $this->permission('Garden.Settings.Manage');

        if ($this->Request->isAuthenticatedPostBack()) {
            PermissionModel::resetAllRoles();
            $this->setData('Result', ['Complete' => true]);
        }

        $this->setData('Title', 'Reset all role permissions');
        $this->_setJob($this->data('Title'));
        $this->render('Job');
    }

    /**
     * Set a job.
     *
     * @param string $name
     */
    protected function _setJob($name) {
        $args = array_change_key_case($this->ReflectArgs);
        $url = "/dba/{$this->RequestMethod}.json?".http_build_query($args);
        $this->Data['Jobs'][$name] = $url;
    }
}
