<?php
/**
 * Manages imports and exports of data.
 *
 * This controller could use a code audit. Don't use it as sample code.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * Manage importing process.
     *
     * @since 2.0.0
     * @access public
     */
    public function go($transientKey = '') {
        $this->checkAccess();
        if (!Gdn::session()->validateTransientKey($transientKey) && !Gdn::request()->isAuthenticatedPostBack()) {
            throw new Gdn_UserException('The CSRF token is invalid.', 403);
        }
        $imp = new ImportModel();
        $imp->loadState();
        $this->setData('Steps', $imp->steps());
        $this->Form = new Gdn_Form();

        if ($imp->CurrentStep < 1) {
            // Check for the import file.
            if ($imp->ImportPath) {
                $imp->CurrentStep = 1;
            } else {
                redirectTo(strtolower($this->Application).'/import');
            }
        }

        if ($imp->CurrentStep >= 1) {
            if ($this->Form->authenticatedPostBack()) {
                $imp->fromPost($this->Form->formValues());
            }
            try {
                $result = $imp->runStep($imp->CurrentStep);
            } catch (Exception $ex) {
                $result = false;
                $this->Form->addError($ex);
                $this->setJson('Error', true);
            }

            if ($result === true) {
                $imp->CurrentStep++;
            } elseif ($result === 'COMPLETE') {
                $this->setJson('Complete', true);
            }

            /*elseif(is_array($Result)) {
				saveToConfig(array(
					'Garden.Import.CurrentStep' => $CurrentStep,
					'Garden.Import.CurrentStepData' => val('Data', $Result)));
				$this->setData('CurrentStepMessage', val('Message', $Result));
			}*/
        }
        $imp->saveState();
        $this->Form->setValidationResults($imp->Validation->results());

        $this->setData('Stats', val('Stats', $imp->Data, []));
        $this->setData('CurrentStep', $imp->CurrentStep);
        $this->setData('CurrentStepMessage', val('CurrentStepMessage', $imp->Data, ''));
        $this->setData('ErrorType', val('ErrorType', $imp));
        if ($this->data('ErrorType')) {
            $this->setJson('Error', true);
        }

        $imp->toPost($post);
        $this->Form->formValues($post);

        $this->addJsFile('import.js');
        $this->render();
    }

    /**
     * Ensure that imports are enabled and that the user has permission.
     */
    private function checkAccess() {
        $this->permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
    }

    /**
     * Main import page.
     *
     * @since 2.0.0
     * @access public
     */
    public function index() {
        $this->checkAccess();
        $timer = new Gdn_Timer();

        // Determine the current step.
        $this->Form = new Gdn_Form();
        $imp = new ImportModel();
        $imp->loadState();

        // Search for the list of acceptable imports.
        $importPaths = [];
        $existingPaths = safeGlob(PATH_UPLOADS.'/export*', ['gz', 'txt']);
        $existingPaths2 = safeGlob(PATH_UPLOADS.'/porter/export*', ['gz']);
        $existingPaths = array_merge($existingPaths, $existingPaths2);
        foreach ($existingPaths as $path) {
            $importPaths[substr($path, strlen(PATH_UPLOADS))] = basename($path);
        }
        // Add the database as a path.
        $importPaths = array_merge(['db:' => t('This Database')], $importPaths);

        if ($imp->CurrentStep < 1) {
            // Check to see if there is a file.
            $importPath = c('Garden.Import.ImportPath');
            $validation = new Gdn_Validation();


            if (Gdn::request()->isAuthenticatedPostBack(true)) {
                $upload = new Gdn_Upload();
                $validation = new Gdn_Validation();
                if (count($importPaths) > 0) {
                    $validation->applyRule('PathSelect', 'Required', t('You must select a file to import.'));
                }

                if (count($importPaths) == 0 || $this->Form->getFormValue('PathSelect') == 'NEW') {
                    $tmpFile = $upload->validateUpload('ImportFile', false);
                } else {
                    $tmpFile = '';
                }

                if ($tmpFile) {
                    $filename = $_FILES['ImportFile']['name'];
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    $targetFolder = PATH_ROOT.DS.'uploads'.DS.'import';
                    if (!file_exists($targetFolder)) {
                        mkdir($targetFolder, 0777, true);
                    }
                    $importPath = $upload->generateTargetName(PATH_ROOT.DS.'uploads'.DS.'import', $extension);
                    $upload->saveAs($tmpFile, $importPath);
                    $imp->ImportPath = $importPath;
                    $this->Form->setFormValue('PathSelect', $importPath);

                    $uploadedFiles = val('UploadedFiles', $imp->Data);
                    $uploadedFiles[$importPath] = basename($filename);
                    $imp->Data['UploadedFiles'] = $uploadedFiles;
                } elseif (($pathSelect = $this->Form->getFormValue('PathSelect'))) {
                    if ($pathSelect === 'NEW') {
                        $validation->addValidationResult('ImportFile', 'ValidateRequired');
                    } else {
                        if ($pathSelect !== 'db:') {
                            $pathSelect = PATH_UPLOADS.$pathSelect;
                        }
                        $imp->ImportPath = $pathSelect;
                    }
                } elseif (!$imp->ImportPath && count($importPaths) == 0) {
                    // There was no file uploaded this request or before.
                    $validation->addValidationResult('ImportFile', $upload->Exception);
                }

                // Validate the overwrite.
                if (true || strcasecmp($this->Form->getFormValue('Overwrite'), 'Overwrite') == 0) {
                    if (!stringBeginsWith($this->Form->getFormValue('PathSelect'), 'Db:', true)) {
                        $validation->applyRule('Email', 'Required');
                    }
                }

                if ($validation->validate($this->Form->formValues())) {
                    $this->Form->setFormValue('Overwrite', 'overwrite');
                    $imp->fromPost($this->Form->formValues());
                    $this->View = 'Info';
                } else {
                    $this->Form->setValidationResults($validation->results());
                }
            } else {
                $this->Form->setFormValue('PathSelect', $imp->ImportPath);
            }
            $imp->saveState();
        } else {
            $this->setData('Steps', $imp->steps());
            $this->View = 'Info';
        }

        if (!stringBeginsWith($imp->ImportPath, 'db:') && !file_exists($imp->ImportPath)) {
            $imp->deleteState();
        }

        try {
            $uploadedFiles = val('UploadedFiles', $imp->Data, []);
            $importPaths = array_merge($importPaths, $uploadedFiles);
            $this->setData('ImportPaths', $importPaths);
            $this->setData('Header', $imp->getImportHeader());
            $this->setData('Stats', val('Stats', $imp->Data, []));
            $this->setData('GenerateSQL', val('GenerateSQL', $imp->Data));
            $this->setData('ImportPath', $imp->ImportPath);
            $this->setData('OriginalFilename', val('OriginalFilename', $imp->Data));
            $this->setData('CurrentStep', $imp->CurrentStep);
            $this->setData('LoadSpeedWarning', $imp->loadTableType(false) == 'LoadTableWithInsert');
        } catch (Gdn_UserException $ex) {
            $this->Form->addError($ex);
            $imp->saveState();
            $this->View = 'Index';
        }
        $this->addJsFile('import.js');
        $this->render();
    }

    /**
     * Restart the import process. Undo any work we've done so far and erase state.
     *
     * @since 2.0.0
     * @access public
     */
    public function restart($transientKey = '') {
        $this->checkAccess();
        if (!Gdn::session()->validateTransientKey($transientKey) && !Gdn::request()->isAuthenticatedPostBack()) {
            throw new Gdn_UserException('The CSRF token is invalid.', 403);
        }

        // Delete the individual table files.
        $imp = new ImportModel();
        try {
            $imp->loadState();
            $imp->deleteFiles();
        } catch (Exception $ex) {
            // Suppress exceptions from bubbling up.
        }
        $imp->deleteState();

        redirectTo(strtolower($this->Application).'/import');
    }
}
