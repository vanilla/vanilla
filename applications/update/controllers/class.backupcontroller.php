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
   
   protected $BackupDir = NULL;
   protected $Backups = NULL;
   protected $BackupTask = NULL;
   
   const MANIFEST_FILE = 'manifest.json';
   
   public function Initialize() {
      parent::Initialize();
      
      $this->BackupDir = C('Update.Local.Backups', NULL);
      
      // Load all backups.
      $this->Backups();
      
      // Check if we're in an update. If so, extract the backup task. If we don't yet have one, create one.
      if ($this->UpdateModule->Active()) {
         $CurrentBackupID = $this->UpdateModule->GetMeta('BackupTask', FALSE);
         
         // Don't have a backup yet... make one
         if ($CurrentBackupID === FALSE) {
            $CurrentBackupID = $this->GetBackupTask();
            
            $this->UpdateModule->SetMeta('BackupTask', $CurrentBackupID);
         }
         
         $this->BackupTask = $CurrentBackupID;
      }
      
      // If someone passed in a backupID
   }
   
   protected function Backups() {
      if (is_null($this->Backups)) {
         if (is_null($this->BackupDir)) throw new Exception("Could not determine backup folder. Please set 'Update.Local.Backups'.");
         if (!file_exists($this->BackupDir) || !is_dir($this->BackupDir)) {
            @mkdir($this->BackupDir);
            if (!file_exists($this->BackupDir))
               throw new Exception("Could not create backup folder at '{$this->BackupDir}'.");
         }
         
         $BackupFolders = scandir($this->BackupDir);
         
         // Not gunna multi-tier because YOU SHOULDNT HAVE MORE THAN 65,000 BACKUPS OMG
         foreach ($BackupFolders as $BackupFolder) {
            if ($BackupFolder == '.' || $BackupFolder == '..') continue;
            
            $BackupPath = CombinePaths(array($this->BackupDir, $BackupFolder));
            $ManifestFile = CombinePaths(array($BackupPath, BackupController::MANIFEST_FILE));
            if (!file_exists($ManifestFile)) continue;
            
            $Manifest = $this->ParseBackup($ManifestFile, $BackupPath);
            if ($Manifest)
               $this->Backups[$Manifest['BackupID']] = $Manifest;
            
         }
      }
   }
   
   protected function ParseBackup($ManifestFile, $BackupPath) {
      $ManifestData = $this->DecodeManifest($ManifestFile);
      if ($ManifestData === FALSE) return FALSE;
      
      return $ManifestData;
   }
   
   /**
    * Retrieve current backup task or create a new one.
    *
    * 
    */
   protected function GetBackupTask() {
      if (is_null($this->BackupTask))
         $this->BackupTask = $this->CreateBackupTask();
      
      return $this->BackupTask;
   }
   
   protected function CreateBackupTask() {
      $BackupTaskID = md5(implode('/',array(
         'User'      => Gdn::Session()->User->Name,
         'Time'      => date('Y-m-d H:i:s'),
         'Version'   => APPLICATION_VERSION
      )));
      
      $BackupPath = CombinePaths(array($this->BackupDir,$BackupTaskID));
      @mkdir($BackupPath);
      if (!is_dir($BackupPath)) throw new Exception("Could not create restore point at '{$BackupPath}'");
      
      $BasicManifest = array(
         'backup' => array(
            'user'      => Gdn::Session()->User->Name,
            'userid'    => Gdn::Session()->UserID,
            'date'      => date('Y-m-d H:i:s'),
            'version'   => APPLICATION_VERSION,
            'id'        => $BackupTaskID
         ),
         'files'  => array(
            'name'      => NULL,
            'hash'      => NULL
         ),
         'data'   => array(
            'name'      => NULL,
            'hash'      => NULL
         )
      );
      
      $EncodedManifest = $this->EncodeManifest($BasicManifest);
      
      $ManifestFile = CombinePaths(array($BackupPath, BackupController::MANIFEST_FILE));
      file_put_contents($ManifestFile, $EncodedManifest);
      
      $FilesDir = CombinePaths(array($BackupPath, 'files'));
      @mkdir($FilesDir);
      
      $DataDir = CombinePaths(array($BackupPath, 'data'));
      @mkdir($DataDir);
      
      return $BackupTaskID;
   }
   
   protected function EncodeManifest($ManifestData) {
      return json_encode($ManifestData);
   }
   
   protected function DecodeManifest($ManifestData) {
      return json_decode($ManifestData);
   }
   
   protected function GetActiveStep() {
      if (is_null($this->BackupTask)) return FALSE;
      
      $Backup = $this->GetBackup($this->BackupTask);
      if (is_null($Backup)) return FALSE;
      
      if (is_null(GetValue('files',$Backup))) return 'files';
      if (is_null(GetValue('data',$Backup))) return 'data';
   }

   public function Index() {
      $this->AddSideMenu('update/backup');
      
      switch ($this->GetActiveStep()) {
         
      }
      
      $this->Render();
   }
   
   public function Files() {
      $this->GetBackupTask();
      
      $this->View = 'files';
      $this->Render();
   }
   
   public function Data() {
      $this->GetBackupTask();
      
      $this->View = 'data';
      $this->Render();
   }
   
}