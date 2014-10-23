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
 * Import Controller
 * @package Dashboard
 */
 
/**
 * Manages imports and exports of data.
 * This controller could use a code audit. Don't use it as sample code.
 *
 * @since 2.0.0
 * @package Dashboard
 */
class ImportController extends DashboardController {
   public function Initialize() {
      parent::Initialize();
      Gdn_Theme::Section('Dashboard');
   }
   
   /**
    * Export core Vanilla and Conversations tables.
    *
    * @since 2.0.0
    * @access public
    */
   public function Export() {
      $this->Permission('Garden.Export'); // This permission doesn't exist, so only users with Admin == '1' will succeed.

      set_time_limit(60*2);
      $Ex = new ExportModel();
      $Ex->PDO(Gdn::Database()->Connection());
      $Ex->Prefix = Gdn::Database()->DatabasePrefix;

      /// 2. Do the export. ///
      $Ex->UseCompression = TRUE;
      $Ex->BeginExport(PATH_ROOT.DS.'uploads'.DS.'export '.date('Y-m-d His').'.txt.gz', 'Vanilla 2.0');

      $Ex->ExportTable('User', 'select * from :_User'); // ":_" will be replace by database prefix
      $Ex->ExportTable('Role', 'select * from :_Role');
      $Ex->ExportTable('UserRole', 'select * from :_UserRole');

      $Ex->ExportTable('Category', 'select * from :_Category');
      $Ex->ExportTable('Discussion', 'select * from :_Discussion');
      $Ex->ExportTable('Comment', 'select * from :_Comment');

      $Ex->ExportTable('Conversation', 'select * from :_Conversation');
      $Ex->ExportTable('UserConversation', 'select * from :_UserConversation');
      $Ex->ExportTable('ConversationMessage', 'select * from :_ConversationMessage');

      $Ex->EndExport();
   }
   
   /**
    * Manage importing process.
    *
    * @since 2.0.0
    * @access public
    */
   public function Go() {
      $this->Permission('Garden.Settings.Manage');

      $Imp = new ImportModel();
      $Imp->LoadState();
      $this->SetData('Steps', $Imp->Steps());
      $this->Form = new Gdn_Form();

         if($Imp->CurrentStep < 1) {
            // Check for the import file.
            if($Imp->ImportPath)
               $Imp->CurrentStep = 1;
            else
               Redirect(strtolower($this->Application).'/import');
         }

         if($Imp->CurrentStep >= 1) {
            if($this->Form->AuthenticatedPostBack())
               $Imp->FromPost($this->Form->FormValues());
            try {
               $Result = $Imp->RunStep($Imp->CurrentStep);
            } catch(Exception $Ex) {
               $Result = FALSE;
               $this->Form->AddError($Ex);
               $this->SetJson('Error', TRUE);
            }

            if($Result === TRUE) {
               $Imp->CurrentStep++;
            } elseif($Result === 'COMPLETE') {
               $this->SetJson('Complete', TRUE);
            }

            /*elseif(is_array($Result)) {
				SaveToConfig(array(
					'Garden.Import.CurrentStep' => $CurrentStep,
					'Garden.Import.CurrentStepData' => ArrayValue('Data', $Result)));
				$this->SetData('CurrentStepMessage', ArrayValue('Message', $Result));
			}*/
         }
         $Imp->SaveState();
         $this->Form->SetValidationResults($Imp->Validation->Results());
         
         $this->SetData('Stats', GetValue('Stats', $Imp->Data, array()));
         $this->SetData('CurrentStep', $Imp->CurrentStep);
         $this->SetData('CurrentStepMessage', GetValue('CurrentStepMessage', $Imp->Data, ''));
         $this->SetData('ErrorType', GetValue('ErrorType', $Imp));
         if ($this->Data('ErrorType'))
            $this->SetJson('Error', TRUE);

         $Imp->ToPost($Post);
         $this->Form->FormValues($Post);

      $this->AddJsFile('import.js');
      $this->Render();
   }
   
   /**
    * Main import page.
    *
    * @since 2.0.0
    * @access public
    */
   public function Index() {
      $this->Permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
      $Timer = new Gdn_Timer();

      // Determine the current step.
      $this->Form = new Gdn_Form();
      $Imp = new ImportModel();
      $Imp->LoadState();

      // Search for the list of acceptable imports.
      $ImportPaths = array();
      $ExistingPaths = SafeGlob(PATH_UPLOADS.'/export*', array('gz', 'txt'));
      $ExistingPaths2 = SafeGlob(PATH_UPLOADS.'/porter/export*', array('gz'));
      $ExistingPaths = array_merge($ExistingPaths, $ExistingPaths2);
      foreach ($ExistingPaths as $Path)
         $ImportPaths[$Path] = basename($Path);
      // Add the database as a path.
      $ImportPaths = array_merge(array('db:' => T('This Database')), $ImportPaths);

      if($Imp->CurrentStep < 1) {
         // Check to see if there is a file.
         $ImportPath = C('Garden.Import.ImportPath');
         $Validation = new Gdn_Validation();


         if (strcasecmp(Gdn::Request()->RequestMethod(), 'post') == 0) {
            $Upload = new Gdn_Upload();
            $Validation = new Gdn_Validation();
            if (count($ImportPaths) > 0)
               $Validation->ApplyRule('PathSelect', 'Required', T('You must select a file to import.'));

            if (count($ImportPaths) == 0 || $this->Form->GetFormValue('PathSelect') == 'NEW')
               $TmpFile = $Upload->ValidateUpload('ImportFile', FALSE);
            else
               $TmpFile = '';

            if ($TmpFile) {
               $Filename = $_FILES['ImportFile']['name'];
               $Extension = pathinfo($Filename, PATHINFO_EXTENSION);
               $TargetFolder = PATH_ROOT . DS . 'uploads' . DS . 'import';
               if(!file_exists($TargetFolder)) {
                  mkdir($TargetFolder, 0777, TRUE);
               }
               $ImportPath = $Upload->GenerateTargetName(PATH_ROOT . DS . 'uploads' . DS . 'import', $Extension);
               $Upload->SaveAs($TmpFile, $ImportPath);
               $Imp->ImportPath = $ImportPath;
               $this->Form->SetFormValue('PathSelect', $ImportPath);

               $UploadedFiles = GetValue('UploadedFiles', $Imp->Data);
               $UploadedFiles[$ImportPath] = basename($Filename);
               $Imp->Data['UploadedFiles'] = $UploadedFiles;
            } elseif (($PathSelect = $this->Form->GetFormValue('PathSelect'))) {
               if ($PathSelect == 'NEW')
                  $Validation->AddValidationResult('ImportFile', 'ValidateRequired');
               else
                  $Imp->ImportPath = $PathSelect;
            } elseif (!$Imp->ImportPath && count($ImportPaths) == 0) {
               // There was no file uploaded this request or before.
               $Validation->AddValidationResult('ImportFile', $Upload->Exception);
            }

            // Validate the overwrite.
            if(TRUE || strcasecmp($this->Form->GetFormValue('Overwrite'), 'Overwrite') == 0) {
               if (!StringBeginsWith($this->Form->GetFormValue('PathSelect'), 'Db:', TRUE)) {
                  $Validation->ApplyRule('Email', 'Required');
                  if (!$this->Form->GetFormValue('UseCurrentPassword'))
                     $Validation->ApplyRule('Password', 'Required');
               }
            }

            if ($Validation->Validate($this->Form->FormValues())) {
               $this->Form->SetFormValue('Overwrite', 'overwrite');
               $Imp->FromPost($this->Form->FormValues());
               $this->View = 'Info';
            } else {
               $this->Form->SetValidationResults($Validation->Results());
            }
         } else {
            $this->Form->SetFormValue('PathSelect', $Imp->ImportPath);
         }
         $Imp->SaveState();
      } else {
         $this->SetData('Steps', $Imp->Steps());
         $this->View = 'Info';
      }

      if (!StringBeginsWith($Imp->ImportPath, 'db:') && !file_exists($Imp->ImportPath))
         $Imp->DeleteState();

      try {
         $UploadedFiles = GetValue('UploadedFiles', $Imp->Data, array());
         $ImportPaths = array_merge($ImportPaths, $UploadedFiles);
         $this->SetData('ImportPaths', $ImportPaths);
         $this->SetData('Header', $Imp->GetImportHeader());
         $this->SetData('Stats', GetValue('Stats', $Imp->Data, array()));
         $this->SetData('GenerateSQL', GetValue('GenerateSQL', $Imp->Data));
         $this->SetData('ImportPath', $Imp->ImportPath);
         $this->SetData('OriginalFilename', GetValue('OriginalFilename', $Imp->Data));
         $this->SetData('CurrentStep', $Imp->CurrentStep);
         $this->SetData('LoadSpeedWarning', $Imp->LoadTableType(FALSE) == 'LoadTableWithInsert');
      } catch(Gdn_UserException $Ex) {
         $this->Form->AddError($Ex);
         $Imp->SaveState();
         $this->View = 'Index';
      }
      $this->Render();
   }
   
   /**
    * Restart the import process. Undo any work we've done so far and erase state.
    *
    * @since 2.0.0
    * @access public
    */
   public function Restart() {
      $this->Permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.

      // Delete the individual table files.
      $Imp = new ImportModel();
      try {
         $Imp->LoadState();
         $Imp->DeleteFiles();
      } catch(Exception $Ex) {
      }
      $Imp->DeleteState();

      Redirect(strtolower($this->Application).'/import');
   }
}
