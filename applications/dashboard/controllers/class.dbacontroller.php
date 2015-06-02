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
    public $Form = NULL;

    /** @var DBAModel */
    public $Model = NULL;

    /**
     * Runs before every call to this controller.
     */
    public function Initialize() {
        parent::Initialize();
        Gdn_Theme::Section('Dashboard');
        $this->Model = new DBAModel();
        $this->Form = new Gdn_Form();
        $this->Form->InputPrefix = '';

        $this->AddJsFile('dba.js');
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
    public function Counts($Table = FALSE, $Column = FALSE, $From = FALSE, $To = FALSE, $Max = FALSE) {
        set_time_limit(300);
        $this->Permission('Garden.Settings.Manage');

        if ($Table && $Column && strcasecmp($this->Request->RequestMethod(), Gdn_Request::INPUT_POST) == 0) {
            if (!ValidateRequired($Table))
                throw new Gdn_UserException("Table is required.");
            if (!ValidateRequired($Column))
                throw new Gdn_UserException("Column is required.");

            $Result = $this->Model->Counts($Table, $Column, $From, $To);
            $this->SetData('Result', $Result);
        } else {
            $this->SetData('Jobs', array());
            $this->FireEvent('CountJobs');
        }

        $this->SetData('Title', T('Recalculate Counts'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    /**
     * Set Member-like permissions on all roles with missing permissions.
     *
     * Useful for after an import that didn't include permissions
     * but did include a whole lotta roles you don't want to edit manually.
     */
    public function FixPermissions() {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack()) {
            $Result = $this->Model->FixPermissions();
            $this->SetData('Result', $Result);
        }

        $this->SetData('Title', "Fix missing permission records after import");
        $this->_SetJob($this->Data('Title'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    /**
     * Fix the category tree after an import that only gives a sort & parent.
     */
    public function RebuildCategoryTree() {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack()) {
            $CategoryModel = new CategoryModel();
            $CategoryModel->RebuildTree();
            $this->SetData('Result', array('Complete' => TRUE));
        }

        $this->SetData('Title', "Fix category tree from an import.");
        $this->_SetJob($this->Data('Title'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    public function FixUrlCodes($Table, $Column) {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack()) {
            $Result = $this->Model->FixUrlCodes($Table, $Column);
            $this->SetData('Result', $Result);
        }

        $this->SetData('Title', "Fix url codes for $Table.$Column");
        $this->_SetJob($this->Data('Title'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    public function HtmlEntityDecode($Table, $Column) {
        $this->Permission('Garden.Settings.Manage');

//      die($this->Request->RequestMethod());
        if (strcasecmp($this->Request->RequestMethod(), Gdn_Request::INPUT_POST) == 0) {
            $Result = $this->Model->HtmlEntityDecode($Table, $Column);
            $this->SetData('Result', $Result);
        }

        $this->SetData('Title', "Decode Html Entities for $Table.$Column");
        $this->_SetJob($this->Data('Title'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    /**
     * Scan a table for invalid InsertUserID values and update with SystemUserID
     *
     * @param bool|string $Table The name of the table to fix InsertUserID in.
     */
    public function FixInsertUserID($Table = false) {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack() && $Table) {
            $this->Model->FixInsertUserID($Table);

            $this->SetData(
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

            $this->SetData('Jobs', $Jobs);
        }

        $this->SetData('Title', T('Fix Invalid InsertUserID'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    /**
     * Look for users with an invalid role and apply the role specified to those users.
     */
    public function FixUserRole() {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack()) {
            if (ValidateRequired($this->Form->GetFormValue('DefaultUserRole'))) {
                $this->Model->FixUserRole($this->Form->GetFormValue('DefaultUserRole'));
                $this->SetData('CompletedFix', true);
            }
        }

        $this->AddSideMenu();
        $this->Render();
    }

    /**
     * Reset all role permissions based on role type.
     */
    public function resetPermissions() {
        $this->Permission('Garden.Settings.Manage');

        if ($this->Request->IsAuthenticatedPostBack()) {
            PermissionModel::ResetAllRoles();
            $this->SetData('Result', array('Complete' => true));
        }

        $this->SetData('Title', 'Reset all role permissions');
        $this->_SetJob($this->Data('Title'));
        $this->AddSideMenu();
        $this->Render('Job');
    }

    protected function _SetJob($Name) {
        $Args = array_change_key_case($this->ReflectArgs);
        $Url = "/dba/{$this->RequestMethod}.json?".http_build_query($Args);
        $this->Data['Jobs'][$Name] = $Url;
    }
}
