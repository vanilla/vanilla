<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ImportController extends DashboardController {
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
	
	public function Go() {
		$this->Permission('Garden.Import');
		
		$Imp = new ImportModel();
		$Imp->LoadState();
		$this->SetData('Steps', $Imp->Steps());
		
		if($Imp->CurrentStep < 1) {
			// Check for the import file.
			if($Imp->ImportPath)
				$Imp->CurrentStep = 1;
			else
				Redirect(strtolower($this->Application).'/import');
		}
		
		if($Imp->CurrentStep >= 1) {
			$Result = $Imp->RunStep($Imp->CurrentStep);
			
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
		$this->Form = new Gdn_Form();
		$this->Form->SetValidationResults($Imp->Validation->Results());
		$this->SetData('CurrentStep', $Imp->CurrentStep);
		$this->SetData('CurrentStepMessage', GetValue('CurrentStepMessage', $Imp->Data, ''));
	
		$this->AddJsFile('import.js');
		$this->Render();
	}
	
	public function Index() {
		$this->Permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
		$Timer = new Gdn_Timer();
			
		// Determine the current step.
		$this->Form = new Gdn_Form();
		$Imp = new ImportModel();
		$Imp->LoadState();
		
		if($Imp->CurrentStep < 1) {
			// Check to see if there is a file.
			$ImportPath = Gdn::Config('Garden.Import.ImportPath');
			$Validation = new Gdn_Validation();

			
			if (strcasecmp(Gdn::Request()->RequestMethod(), 'post') == 0) {
				$Upload = new Gdn_Upload();
				$Validation = new Gdn_Validation();
				
				$TmpFile = $Upload->ValidateUpload('ImportFile', FALSE);
				if($TmpFile) {
					$Filename = $_FILES['ImportFile']['name'];
					$Extension = pathinfo($Filename, PATHINFO_EXTENSION);
					$TargetFolder = PATH_ROOT . DS . 'uploads' . DS . 'import';
					if(!file_exists($TargetFolder)) {
						mkdir($TargetFolder, 0777, TRUE);
					}
					$ImportPath = $Upload->GenerateTargetName(PATH_ROOT . DS . 'uploads' . DS . 'import', $Extension);
					$Upload->SaveAs($TmpFile, $ImportPath);
					$Imp->ImportPath = $ImportPath;
					$Imp->Data['OriginalFilename'] = basename($Filename);
					
				} elseif(!$Imp->ImportPath) {
					// There was no file uploaded this request or before.
					$Validation->AddValidationResult('ImportFile', $Upload->Exception);
				}
				// Validate the overwrite.
				if(strcasecmp($this->Form->GetFormValue('Overwrite'), 'Overwrite') == 0) {
					$Validation->ApplyRule('Email', 'Required');
					$Validation->ApplyRule('Password', 'Required');
				}
				
				if($Validation->Validate($this->Form->FormValues())) {
					$Imp->Overwrite(
						$this->Form->GetFormValue('Overwrite', 'Overwrite'),
						$this->Form->GetFormValue('Email'),
						$this->Form->GetFormValue('Password'));
					$this->View = 'Info';
				} else {
					$this->Form->SetValidationResults($Validation->Results());
				}
			} else {
				// Search for an existing file that was uploaded by the web admin.
				$ImportPaths = SafeGlob(PATH_ROOT.DS.'uploads'.DS.'export *');
				if($ImportPaths) {
					$ImportPath = $ImportPaths[0];
					if(in_array(pathinfo($ImportPath, PATHINFO_EXTENSION), array('gz', 'txt'))) {
						$Imp->ImportPath = $ImportPath;
						$Imp->Data['OriginalFilename'] = basename($ImportPath);
					}
				}
			}
			$Imp->SaveState();
		} else {
			$this->View = 'Info';
		}
		
		$this->SetData('Header', $Imp->GetImportHeader());
		$this->SetData('ImportPath', $Imp->ImportPath);
		$this->SetData('OriginalFilename', GetValue('OriginalFilename', $Imp->Data));
		$this->Render();
	}
	
	public function Restart() {
		$this->Permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
		
		// Delete the individual table files.
		$Imp = new ImportModel();
		$Imp->LoadState();
		$Imp->DeleteFiles();
		$Imp->DeleteState();
		
		Redirect(strtolower($this->Application).'/import');
	}
}