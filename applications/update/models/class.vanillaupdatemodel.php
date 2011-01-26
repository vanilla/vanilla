<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class VanillaUpdateModel {
   
   protected $Tag;
   protected $Tasks;
   protected $Meta;
   protected $LoadHash;
   
   public function __construct() {
      // Start with fresh storage
      $this->Clear(FALSE);
      
      // Build a unqiue key for this user to track their own updates
      $this->Key = 'Update.Local.Model.Key.'.Gdn::Session()->UserID;
      
      // Updater lockfile in a cache key
      $this->UpdateLockKey = 'Update.Local.Lock';
      
      $this->Cache = Gdn_Cache::Initialize(TRUE, 'filecache');
      $this->Load();
   }
   
   public function Action() {
      $Action = $this->GetInternalAction();
      if ($Action === FALSE || $Action === TRUE) return $Action;
      
      $Name = array('update');
      array_push($Name, strtolower(GetValue('Group', $Action)));
      array_push($Name, strtolower(GetValue('Name', $Action)));
      
      return CombinePaths($Name);
   }
   
   public function Active() {
      if ($this->Action() === FALSE) return FALSE;
      return TRUE;
   }
   
   public function AddGroup($GroupName, $GroupLabel) {
      if (!is_array($this->Tasks)) $this->Clear(FALSE);
      $this->Tasks[$GroupName] = array(
         'Name'      => $GroupName,
         'Label'     => $GroupLabel,
         'Tasks'     => array()
      );
      return $this;
   }
   
   public function AddTask($GroupName, $TaskName, $TaskLabel) {
      if (!is_array($this->Tasks))
         $this->Clear(FALSE);
      
      if (array_key_exists($GroupName, $this->Tasks))
         $this->Tasks[$GroupName]['Tasks'][$TaskName] = array(
            'Group'        => $GroupName,
            'Name'         => $TaskName,
            'Label'        => $TaskLabel,
            'Completion'   => 0
         );
         
      return $this;
   }
   
   public function Cancel() {
      $this->Clear();
      $this->Unlock();
   }
   
   public function Clear($Hard = TRUE) {
      if ($Hard)
         $this->Cache->Remove($this->Key);
      
      $this->Tasks      = array();
      $this->Tag        = array();
      $this->Meta       = array();
      $this->LoadHash   = NULL;
   }
   
   protected function Deflate() {
      $Update = array(
         'Tasks'  => $this->Tasks,
         'Tag'    => $this->Tag,
         'Meta'   => $this->Meta
      );
      return Gdn_Format::Serialize($Update);
   }
   
   public function Fresh() {
      $this->Clear();
      $this->Tag();
      
      $this->AddGroup('backup', 'Backup')
         ->AddTask('backup', 'files', 'Backup Files')
         ->AddTask('backup', 'data', 'Backup Data');
      
      $this->AddGroup('download', 'Download')
         ->AddTask('download', 'get', 'Download Files');
      
      $this->AddGroup('verify', 'Verify')
         ->AddTask('verify', 'extract',  'Extract Downloaded Archives')
         ->AddTask('verify', 'clean', 'Clean Archives')
         ->AddTask('verify', 'changes', 'Check for modified core files');
         
      $this->AddGroup('apply', 'Apply')
         ->AddTask('apply', 'files', 'Update Filesystem')
         ->AddTask('apply', 'structure', 'Update Database Structure');
   }
   
   /**
    * FALSE - No update in progress
    * TRUE - Update in progress, but completed
    * Task Array - Destination controller/method
    */
   public function GetInternalAction() {
      if (!sizeof($this->Tasks) || !sizeof($this->Tag)) return FALSE;
      
      // There's an update in progress. We need to know what stage of the process we're at
      foreach ($this->Tasks as $GroupName => $Group) {

         $Tasks = GetValue('Tasks', $Group);
         foreach ($Tasks as $TaskName => $Task) {
            $Completion = GetValue('Completion', $Task);

            // Not this task, its already done yo
            if ($Completion >= 100)
               continue;
            
            // Its this one
            return $Task;
         }
      }
      return TRUE;
   }
   
   public function GetMeta($Key) {
      return (array_key_exists($Key, $this->Meta)) ? $this->Meta[$Key] : FALSE;
   }
   
   public function GetProperty($GroupName, $TaskName, $Property) {
      if (!array_key_exists($GroupName, $this->Tasks)) return FALSE;
      if (!array_key_exists($TaskName, $this->Tasks[$GroupName]['Tasks'])) return FALSE;
      
      return GetValue($this->Tasks[$GroupName]['Tasks'][$TaskName], $Property);
   }
   
   public function GetTag() {
      return (sizeof($this->Tag)) ? $this->Tag : NULL;
   }
   
   public function GetTask($GroupName, $TaskName) {
      if (!array_key_exists($GroupName, $this->Tasks)) return FALSE;
      if (!array_key_exists($TaskName, $this->Tasks[$GroupName]['Tasks'])) return FALSE;
      
      return $this->Tasks[$GroupName]['Tasks'][$TaskName];
   }
   
   public function GetTasks() { 
      return (sizeof($this->Tasks)) ? $this->Tasks : NULL;
   }
   
   protected function Inflate($Encoded) {
      return Gdn_Format::Unserialize($Encoded);
   }
   
   public function Load() {
      $UpdateStr = $this->Cache->Get($this->Key);
      
      if ($UpdateStr === Gdn_Cache::CACHEOP_FAILURE) {
         return FALSE;
      }
      
      $this->LoadHash = md5($UpdateStr);
      
      $Update = $this->Inflate($UpdateStr);
      
      $this->Tasks = GetValue('Tasks', $Update, FALSE);
      $this->Tag = GetValue('Tag', $Update, FALSE);
      $this->Meta = GetValue('Meta', $Update, FALSE);
      
      if ($this->Tasks === FALSE || $this->Tag === FALSE)
         $this->Clear();
      
      return TRUE;
   }
   
   public function Progress($GroupName, $TaskName, $NewProgressPercentage = NULL, $Save = FALSE) {
      if (!is_null($NewProgressPercentage))
         $this->SetProperty($GroupName, $TaskName, 'Completion', $NewProgressPercentage, $Save);
         
      return $this->GetProperty($GroupName, $TaskName, 'Completion');
   }
   
   protected function Save() {
      $Encoded = $this->Deflate();
      $this->Cache->Store($this->Key, $Encoded);
      $this->LoadHash = md5($Encoded);
   }
   
   public function SetMeta($Key, $Data = NULL) {
      if (is_null($Data))
         unset($this->Meta[$Key]);
      else
         $this->Meta[$Key] = $Data;
         
      return $Data;
   }
   
   public function SetProperty($GroupName, $TaskName, $Property, $Value = NULL, $Save = FALSE) {
      if (!array_key_exists($GroupName, $this->Tasks)) return FALSE;
      if (!array_key_exists($TaskName, $this->Tasks[$GroupName]['Tasks'])) return FALSE;
      
      if ($Value == NULL) return GetValue($Property, $this->Tasks[$GroupName]['Tasks'][$TaskName], NULL);
      
      $this->Tasks[$GroupName]['Tasks'][$TaskName][$Property] = $Value;
      
      if ($Save)
         $this->Save();
      
      return TRUE;
   }
   
   public function Tag() {
      $this->Tag = array(
         'Who'    => Gdn::Session()->User->Name,
         'When'   => date('Y-m-d H:i:s'),
         'Where'  => Gdn::Request()->GetValue('REMOTE_ADDR')
      );
   }
   
   // Save in here
   public function __destruct() {
      $Encoded = $this->Deflate();
      $EncodedHash = md5($Encoded);
      
      if ($EncodedHash != $this->LoadHash) {
         $this->Save();
      }
   }
   
}