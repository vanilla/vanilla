<?php
/**
 * Manages imports and exports of data.
 *
 * This controller could use a code audit. Don't use it as sample code.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /import endpoint.
 */
class ImportController extends DashboardController {

    /**
     * Runs before every call to this controller.
     */
    public function initialize() {
        parent::initialize();
        Gdn_Theme::section('Dashboard');
    }

    /**
     * Export core Vanilla and Conversations tables.
     *
     * @since 2.0.0
     * @access public
     */
    public function export() {
        $this->permission('Garden.Export'); // This permission doesn't exist, so only users with Admin == '1' will succeed.

        set_time_limit(60 * 2);
        $Ex = new ExportModel();
        $Ex->pdo(Gdn::database()->connection());
        $Ex->Prefix = Gdn::database()->DatabasePrefix;

        /// 2. Do the export. ///
        $Ex->UseCompression = true;
        $Ex->beginExport(PATH_ROOT.DS.'uploads'.DS.'export '.date('Y-m-d His').'.txt.gz', 'Vanilla 2.0');

        $Ex->exportTable('User', 'select * from :_User'); // ":_" will be replace by database prefix
        $Ex->exportTable('Role', 'select * from :_Role');
        $Ex->exportTable('UserRole', 'select * from :_UserRole');

        $Ex->exportTable('Category', 'select * from :_Category');
        $Ex->exportTable('Discussion', 'select * from :_Discussion');
        $Ex->exportTable('Comment', 'select * from :_Comment');

        $Ex->exportTable('Conversation', 'select * from :_Conversation');
        $Ex->exportTable('UserConversation', 'select * from :_UserConversation');
        $Ex->exportTable('ConversationMessage', 'select * from :_ConversationMessage');

        $Ex->endExport();
    }

    /**
     * Manage importing process.
     *
     * @since 2.0.0
     * @access public
     */
    public function go() {
        $this->permission('Garden.Settings.Manage');

        $Imp = new ImportModel();
        $Imp->loadState();
        $this->setData('Steps', $Imp->steps());
        $this->Form = new Gdn_Form();

        if ($Imp->CurrentStep < 1) {
            // Check for the import file.
            if ($Imp->ImportPath) {
                $Imp->CurrentStep = 1;
            } else {
                redirect(strtolower($this->Application).'/import');
            }
        }

        if ($Imp->CurrentStep >= 1) {
            if ($this->Form->authenticatedPostBack()) {
                $Imp->fromPost($this->Form->formValues());
            }
            try {
                $Result = $Imp->runStep($Imp->CurrentStep);
            } catch (Exception $Ex) {
                $Result = false;
                $this->Form->addError($Ex);
                $this->setJson('Error', true);
            }

            if ($Result === true) {
                $Imp->CurrentStep++;
            } elseif ($Result === 'COMPLETE') {
                $this->setJson('Complete', true);
            }

            /*elseif(is_array($Result)) {
				saveToConfig(array(
					'Garden.Import.CurrentStep' => $CurrentStep,
					'Garden.Import.CurrentStepData' => arrayValue('Data', $Result)));
				$this->setData('CurrentStepMessage', arrayValue('Message', $Result));
			}*/
        }
        $Imp->saveState();
        $this->Form->setValidationResults($Imp->Validation->results());

        $this->setData('Stats', val('Stats', $Imp->Data, array()));
        $this->setData('CurrentStep', $Imp->CurrentStep);
        $this->setData('CurrentStepMessage', val('CurrentStepMessage', $Imp->Data, ''));
        $this->setData('ErrorType', val('ErrorType', $Imp));
        if ($this->data('ErrorType')) {
            $this->setJson('Error', true);
        }

        $Imp->toPost($Post);
        $this->Form->formValues($Post);

        $this->addJsFile('import.js');
        $this->render();
    }

    /**
     * Main import page.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
        $Timer = new Gdn_Timer();

        // Determine the current step.
        $this->Form = new Gdn_Form();
        $Imp = new ImportModel();
        $Imp->loadState();

        // Search for the list of acceptable imports.
        $ImportPaths = array();
        $ExistingPaths = SafeGlob(PATH_UPLOADS.'/export*', array('gz', 'txt'));
        $ExistingPaths2 = SafeGlob(PATH_UPLOADS.'/porter/export*', array('gz'));
        $ExistingPaths = array_merge($ExistingPaths, $ExistingPaths2);
        foreach ($ExistingPaths as $Path) {
            $ImportPaths[$Path] = basename($Path);
        }
        // Add the database as a path.
        $ImportPaths = array_merge(array('db:' => t('This Database')), $ImportPaths);

        if ($Imp->CurrentStep < 1) {
            // Check to see if there is a file.
            $ImportPath = c('Garden.Import.ImportPath');
            $Validation = new Gdn_Validation();


            if (strcasecmp(Gdn::request()->requestMethod(), 'post') == 0) {
                $Upload = new Gdn_Upload();
                $Validation = new Gdn_Validation();
                if (count($ImportPaths) > 0) {
                    $Validation->applyRule('PathSelect', 'Required', t('You must select a file to import.'));
                }

                if (count($ImportPaths) == 0 || $this->Form->getFormValue('PathSelect') == 'NEW') {
                    $TmpFile = $Upload->ValidateUpload('ImportFile', false);
                } else {
                    $TmpFile = '';
                }

                if ($TmpFile) {
                    $Filename = $_FILES['ImportFile']['name'];
                    $Extension = pathinfo($Filename, PATHINFO_EXTENSION);
                    $TargetFolder = PATH_ROOT.DS.'uploads'.DS.'import';
                    if (!file_exists($TargetFolder)) {
                        mkdir($TargetFolder, 0777, true);
                    }
                    $ImportPath = $Upload->GenerateTargetName(PATH_ROOT.DS.'uploads'.DS.'import', $Extension);
                    $Upload->SaveAs($TmpFile, $ImportPath);
                    $Imp->ImportPath = $ImportPath;
                    $this->Form->setFormValue('PathSelect', $ImportPath);

                    $UploadedFiles = val('UploadedFiles', $Imp->Data);
                    $UploadedFiles[$ImportPath] = basename($Filename);
                    $Imp->Data['UploadedFiles'] = $UploadedFiles;
                } elseif (($PathSelect = $this->Form->getFormValue('PathSelect'))) {
                    if ($PathSelect == 'NEW') {
                        $Validation->addValidationResult('ImportFile', 'ValidateRequired');
                    } else {
                        $Imp->ImportPath = $PathSelect;
                    }
                } elseif (!$Imp->ImportPath && count($ImportPaths) == 0) {
                    // There was no file uploaded this request or before.
                    $Validation->addValidationResult('ImportFile', $Upload->Exception);
                }

                // Validate the overwrite.
                if (true || strcasecmp($this->Form->getFormValue('Overwrite'), 'Overwrite') == 0) {
                    if (!stringBeginsWith($this->Form->getFormValue('PathSelect'), 'Db:', true)) {
                        $Validation->applyRule('Email', 'Required');
                    }
                }

                if ($Validation->validate($this->Form->formValues())) {
                    $this->Form->setFormValue('Overwrite', 'overwrite');
                    $Imp->fromPost($this->Form->formValues());
                    $this->View = 'Info';
                } else {
                    $this->Form->setValidationResults($Validation->results());
                }
            } else {
                $this->Form->setFormValue('PathSelect', $Imp->ImportPath);
            }
            $Imp->saveState();
        } else {
            $this->setData('Steps', $Imp->steps());
            $this->View = 'Info';
        }

        if (!stringBeginsWith($Imp->ImportPath, 'db:') && !file_exists($Imp->ImportPath)) {
            $Imp->deleteState();
        }

        try {
            $UploadedFiles = val('UploadedFiles', $Imp->Data, array());
            $ImportPaths = array_merge($ImportPaths, $UploadedFiles);
            $this->setData('ImportPaths', $ImportPaths);
            $this->setData('Header', $Imp->getImportHeader());
            $this->setData('Stats', val('Stats', $Imp->Data, array()));
            $this->setData('GenerateSQL', val('GenerateSQL', $Imp->Data));
            $this->setData('ImportPath', $Imp->ImportPath);
            $this->setData('OriginalFilename', val('OriginalFilename', $Imp->Data));
            $this->setData('CurrentStep', $Imp->CurrentStep);
            $this->setData('LoadSpeedWarning', $Imp->loadTableType(false) == 'LoadTableWithInsert');
        } catch (Gdn_UserException $Ex) {
            $this->Form->addError($Ex);
            $Imp->saveState();
            $this->View = 'Index';
        }
        $this->render();
    }

    /**
     * Restart the import process. Undo any work we've done so far and erase state.
     *
     * @since 2.0.0
     * @access public
     */
    public function restart() {
        $this->permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.

        // Delete the individual table files.
        $Imp = new ImportModel();
        try {
            $Imp->loadState();
            $Imp->deleteFiles();
        } catch (Exception $Ex) {
        }
        $Imp->deleteState();

        redirect(strtolower($this->Application).'/import');
    }
}
