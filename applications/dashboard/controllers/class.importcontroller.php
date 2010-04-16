<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class ImportController extends GardenController {
	public $Uses = array('Form', 'ImportModel', 'ExportModel');
	
	public function Export() {
		return;
	
		set_time_limit(60*2);
		$Ex = $this->ExportModel;
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
		
		$Ex->EndExport();
	}
	
	public function Index() {
		$this->Permission('Garden.Import'); // This permission doesn't exist, so only users with Admin == '1' will succeed.
		$Timer = new Gdn_Timer();
		
		$Session = Gdn::Session();
      if (!$Session->IsValid())
         $this->Form->AddError('You must be authenticated in order to use this form.');
			
		// Determine the current step.
		$CurrentStep = Gdn::Config('Garden.Import.CurrentStep', 0);
		
		if($CurrentStep < 1) {
			// Check to see if there is a file.
			$ImportPath = Gdn::Config('Garden.Import.ImportPath');
			if($ImportPath && file_exists($ImportPath)) {
				$CurrentStep = 1;
			} elseif ($this->Form->AuthenticatedPostBack() === TRUE) {
				$Upload = new Gdn_Upload();
				$TmpFile = $Upload->ValidateUpload('ImportFile');
				if($TmpFile) {
					$Extension = pathinfo($_FILES['ImportFile']['name'], PATHINFO_EXTENSION);
					$TargetFolder = PATH_ROOT . DS . 'uploads' . DS . 'import';
					if(!file_exists($TargetFolder)) {
						mkdir($TargetFolder, 0777, TRUE);
					}
					$ImportPath = $Upload->GenerateTargetName(PATH_ROOT . DS . 'uploads' . DS . 'import', $Extension);
					$Upload->SaveAs($TmpFile, $ImportPath);
					$CurrentStep = 1;
					SaveToConfig(array(
						'Garden.Import.CurrentStep' => $CurrentStep,
						'Garden.Import.ImportPath' => $ImportPath));
				} else {
					$this->Form->AddError('%s is required.', 'Import File');
				}
			}
		}
		
		$Imp = new Gdn_ImportModel();
		$this->SetData('Steps', $Imp->Steps);
		
		if($CurrentStep >= 1) {
			$this->View = 'Steps';
			
			$Imp->ImportPath = Gdn::Config('Garden.Import.ImportPath');
			$Result = $Imp->RunStep($CurrentStep);
			
			if($Result === TRUE) {
				$CurrentStep++;
				SaveToConfig('Garden.Import.CurrentStep', $CurrentStep);
			} elseif($Result === 'COMPLETE') {
				
			} elseif(is_array($Result)) {
				SaveToConfig(array(
					'Garden.Import.CurrentStep', $CurrentStep,
					'Garden.Import.CurrentStepData', ArrayValue('Data', $Result)));
				$this->SetData('CurrentStepMessage', ArrayValue('Message', $Result));
			}
		}
		
		$this->SetData('CurrentStep', $CurrentStep);
		
		$this->Render();
		
		//set_time_limit(60*5);
		//$Imp = new Gdn_ImportModel();
		//
		//$Path = PATH_ROOT.DS.'uploads'.DS.'export 2010-02-16 200246.txt.gz'; // big db
		////$Path = PATH_ROOT.DS.'uploads'.DS.'export 2010-02-16 134222.txt.gz'; // small db
		//echo '<pre>';
		//ob_end_flush();
		//
	}
}

class Gdn_Timer {
	public $StartTime;
	public $FinishTime;
	public $SplitTime;
	
	public function Start($Message = 'Started') {
		$this->StarTime = microtime(TRUE);
		$this->SplitTime = $this->StarTime;
		$this->FinishTime = NULL;
		
		$this->Write($Message, $this->StarTime);
	}
	
	public function Finish($Message = 'Finished') {
		$this->FinishTime = microtime(TRUE);
		
		$this->Write($Message, $this->FinishTime, $this->StarTime);
	}
	
	public function Split($Message = 'Split') {
		$PrevSplit = $this->SplitTime;
		$this->SplitTime = microtime(TRUE);
		$this->Write($Message, $this->SplitTime, $PrevSplit);
	}
	
	public function Write($Message, $Time = NULL, $PrevTime = NULL) {
		echo $Message;
		if(!is_null($Time)) {
			echo ': ', date('Y-m-d H:i:s', $Time);
			if(!is_null($PrevTime)) {
				$Span = $Time - $PrevTime;
				$m = floor($Span / 60);
				$s = $Span - $m * 60;
				echo sprintf(' (%d:%05.2f)', $m, $s);
			}
		}
		echo "\n";
	}
}