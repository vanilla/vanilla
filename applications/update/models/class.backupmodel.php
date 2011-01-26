<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class BackupModel {
   
   protected $BackupDir = NULL;
   protected $Backups = NULL;
   
   const MANIFEST_FILE = 'manifest.json';
   const FILES_NAME = 'files.zip';
   const DATA_NAME = 'data.sql.zip';
   
   const MAXFILES = 100;
   
   public function __construct() {

      $this->BackupDir = C('Update.Local.Backups', NULL);
      
      $this->Ignore = array(
         '.',
         '..',
         '.DS_Store',
         '.gitignore',
         '.git',
         'Thumbs.db',
         'cache/Updates',
         'cache/*.ini',
         'uploads/Updates'
      );
      
   }
   
   public function Backups() {
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
            $ManifestFile = CombinePaths(array($BackupPath, BackupModel::MANIFEST_FILE));
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
   public function GetBackup($BackupTaskID) {
      // Load all backups.
      $this->Backups();
      
      // Extract requested backup, or FALSE
      return GetValue($BackupTaskID, $this->Backups, FALSE);
   }
   
   public function CreateBackupTask() {
      // Load all backups.
      $this->Backups();
      
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
      
      $ManifestFile = CombinePaths(array($BackupPath, BackupModel::MANIFEST_FILE));
      file_put_contents($ManifestFile, $EncodedManifest);
      
      $FilesDir = CombinePaths(array($BackupPath, 'files'));
      @mkdir($FilesDir);
      
      $DataDir = CombinePaths(array($BackupPath, 'data'));
      @mkdir($DataDir);
      
      // Add to current list
      $this->Backups[$BackupTaskID] = $BasicManifest;
      
      return $BackupTaskID;
   }
   
   protected function EncodeManifest($ManifestData) {
      return json_encode($ManifestData);
   }
   
   protected function DecodeManifest($ManifestData) {
      return json_decode($ManifestData);
   }
   
   public function BackupData($BackupTaskID, &$UpdateModel = NULL) {
      $Response = array('Status' => TRUE);
      
      // For now, we dont backup database
      $UpdateModel->SetMeta('backup/message', T("Database Backup Skipped (coming soon)"));
      $UpdateModel->Progress('backup', 'data', 100, TRUE);
      
      return $Response;
   }
   
   public function BackupFiles($BackupTaskID, &$UpdateModel = NULL) {
      
      $Response = array('Status' => TRUE);
      
      // Path to the root of this particular backup folder
      $BackupPath = CombinePaths(array($this->BackupDir,$BackupTaskID));
      
      // Add the files/"files name" suffix
      $ArchiveName = CombinePaths(array($BackupPath,'files',BackupModel::FILES_NAME));
      
      $Response['Archive'] = $ArchiveName;
      
      $Archive = NULL;
      try {
         $UpdateModel->SetProperty('backup', 'files', 'name', $ArchiveName, TRUE);
         
         $this->OpenArchive($ArchiveName, $Archive, $UpdateModel);
         
         /*
          * Now add all files
          *
          * Recursive method loops over all files and folders, adding them to the 
          * backup archive one at a time and updating the progress as it goes.
          */
         $UpdateModel->SetProperty('backup','files','count',0);
         
         $this->Gather(PATH_ROOT, $Archive, $UpdateModel);
         $this->CloseArchive($ArchiveName, $Archive, $UpdateModel);
         
         // Done
         $UpdateModel->SetMeta('backup/message', T("Backup Complete"));
         $UpdateModel->Progress('backup', 'files', 100, TRUE);

      } catch (Exception $e) {
         die($e->getMessage());
         $Response['Message'] = $e->getMessage();
         $Response['Status'] = FALSE;
      }
            
      return $Response;
   }
   
   protected function OpenArchive($ArchiveName, &$Archive, &$UpdateModel) {
      $Archive = new ZipModel(); 
      $ArchiveStatus = $Archive->open($ArchiveName, ZipArchive::CREATE);
      if ($ArchiveStatus !== TRUE)
         throw new Exception("IO Error: {$ArchiveName}");
   }
   
   protected function CloseArchive($ArchiveName, &$Archive, &$UpdateModel) {
      $UpdateModel->SetMeta('backup/message', T("Archiving..."));
      $UpdateModel->Progress('backup', 'files', -1, TRUE);
      
      $Archive->close();
   }
   
   protected function Gather($Path, &$Archive, &$UpdateModel) {
      
      // Set progress to "undefined task length" and message to "Indexing files"
      $UpdateModel->SetMeta('backup/message', T("Indexing files..."));
      $UpdateModel->Progress('backup', 'files', -1, TRUE);
      
      $FolderFiles = scandir($Path);
      
      // Silently ignore opendir errors. 
      if (!is_array($FolderFiles)) return;
      
      echo "Processing {$Path}\n";
      foreach ($FolderFiles as $File) {
         $RealPath = CombinePaths(array($Path, $File));
         $LocalPath = trim(str_replace(PATH_ROOT, '', $RealPath),'/');
         if ($this->Ignore($File, $LocalPath)) continue;
         
         echo " > file: {$File}\n";
         
         if (is_dir($RealPath)) {
            $this->Gather($RealPath, $Archive, $UpdateModel);
         } else {
            $this->AddFileToArchive($RealPath, $LocalPath, $Archive);
         }
      }
      
   }
   
   public function AddFileToArchive($Filename, $LocalFilename, &$Archive) {

      $Archive->addFile($Filename, $LocalFilename);
      
      // Possible updates here, for progress

   }
   
   public function Ignore($Filename, $Filepath, $IgnoredFiles = NULL) {
      if (is_null($IgnoredFiles)) $IgnoredFiles = $this->Ignore;
      if (!is_array($IgnoredFiles)) $IgnoredFiles = array($IgnoredFiles);
      if (in_array($Filename, $IgnoredFiles)) return TRUE;
      foreach ($IgnoredFiles as $Ignore)
         if (fnmatch($Ignore, $Filename)) return TRUE;
         
      return FALSE;
   }
   
}