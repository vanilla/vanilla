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
 * Update Controller
 */
class BackupController extends UpdateController {
   
   protected $BackupTask = NULL;
   
   public function Initialize() {
      parent::Initialize();
      
      // Init backup model
      $this->BackupModel = new BackupModel();
            
      // Check if we're in an update. If so, extract the backup task. If we don't yet have one, create one.
      if ($this->Update->Active()) {
         $CurrentBackupID = $this->Update->GetMeta('BackupTask', FALSE);
         
         // Don't have a backup yet... make one
         if ($CurrentBackupID === FALSE) {
            $CurrentBackupID = $this->GetBackupTask();
            
            $this->Update->SetMeta('BackupTask', $CurrentBackupID);
         }
         
         $this->BackupTask = $CurrentBackupID;
      }
   }
   
   /**
    * Retrieve current backup task or create a new one.
    *
    * 
    */
   protected function GetBackupTask() {
      if (is_null($this->BackupTask))
         $this->BackupTask = $this->BackupModel->CreateBackupTask();
      
      return $this->BackupTask;
   }
   
   protected function GetActiveStep() {
      if (is_null($this->BackupTask)) return FALSE;
      
      $Backup = $this->GetBackup($this->BackupTask);
      if (is_null($Backup)) return FALSE;
      
      if (is_null(GetValue('files',$Backup))) return 'files';
      if (is_null(GetValue('data',$Backup))) return 'data';
   }

   public function Index() {
      
      switch ($this->GetActiveStep()) {
         
      }
      
      $this->Render();
   }
   
   public function Files() {
      $this->GetBackupTask();
      $RenderController = 'backup';
      
      $RequestType = $this->RequestType();
      switch ($RequestType) {
         case 'ui':
            $this->UpdaterTitle = T('Backing up files...');
            $this->UpdaterTasks = array(
               'update/backup/files'   => $this->UpdaterTitle
            );
            $RenderView = 'files';
            break;
            
         case 'check':
         case 'perform':

            $RenderView = 'blank';
            $RenderController = 'update';

            if ($RequestType == 'perform') {
               // Don't interrupt if another process is already doing this.
               if ($this->Update->Progress('backup','files')) exit();
               $this->BackupModel->BackupFiles($this->GetBackupTask(), $this->Update);
            }
            
            if ($RequestType == 'check') {
               $ThisAction = $this->Update->GetTask('backup','files');
               $this->SetData('Completion', GetValue('Completion',$ThisAction,NULL));
               $this->SetData('Message', $this->Update->GetMeta('backup/message'));
               $this->Update->SetMeta('backup/message');
               $this->SetData('Menu', $this->UpdateModule->ToString());
            }
            
            break;
      }
      $this->Render($RenderView,$RenderController);
   }
   
   public function Data() {
      $this->GetBackupTask();
      $RenderController = 'backup';
      
      $RequestType = $this->RequestType();
      switch ($RequestType) {
         case 'ui':
            $this->UpdaterTitle = T('Backing up data...');
            $this->UpdaterTasks = array(
               'update/backup/data'   => $this->UpdaterTitle
            );
            $RenderView = 'data';
            break;
            
         case 'check':
         case 'perform':

            $RenderView = 'blank';
            $RenderController = 'update';

            if ($RequestType == 'perform') {
               // Don't interrupt if another process is already doing this.
               if ($this->Update->Progress('backup','data')) exit();
               $this->BackupModel->BackupData($this->GetBackupTask(), $this->Update);
            }
            
            if ($RequestType == 'check') {
               $ThisAction = $this->Update->GetTask('backup','data');
               $this->SetData('Completion', GetValue('Completion',$ThisAction,NULL));
               $this->SetData('Message', $this->Update->GetMeta('backup/message'));
               $this->Update->SetMeta('backup/message');
               $this->SetData('Menu', $this->UpdateModule->ToString());
            }
            
            break;
      }
      $this->Render($RenderView,$RenderController);
   }
   
}