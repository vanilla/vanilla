<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class UpdateModule extends Gdn_Module {

   protected $TaskList;
   protected $Tag;
   protected $Meta;
   
   public function __construct(&$Sender = FALSE) {
      parent::__construct($Sender);
      $this->TaskList = array();
      $this->Tag = array();
      $this->Meta = array();
   }
   
   public function AddTask($TaskName, $TaskLabel, $Completion = NULL) {
      if (!is_array($this->TaskList)) 
         $this->Reset();
      
      $ParentName = NULL;
      if (stristr($TaskName, '/'))
         list($ParentName, $TaskName) = explode('/',$TaskName);
      
      $Completion = (is_null($Completion)) ? 0 : $Completion;
      if (is_null($ParentName)) {
         $this->TaskList[$TaskName] = array('Name' => $TaskName, 'Label' => $TaskLabel, 'Children' => array(), 'Completion' => $Completion);
         return TRUE;
      } else {
         if (!array_key_exists($ParentName, $this->TaskList)) return FALSE;
         
         $this->TaskList[$ParentName]['Children'][$TaskName] = array('Name' => $TaskName, 'Parent' => $ParentName, 'Label' => $TaskLabel, 'Completion' => $Completion);
         return TRUE;
      }
   }
   
   public function Action() {
      $Action = $this->GetInternalAction();
      if ($Action === FALSE || $Action === TRUE) return $Action;
      
      $Name = array();
      array_push($Name, ucfirst(GetValue('Group', $Action)));
      array_push($Name, ucfirst(GetValue('Name', $Action)));
      
      return CombinePaths($Name);
   }
   
   public function Active() {
      if ($this->Action() === FALSE) return FALSE;
      return TRUE;
   }

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function Cancel() {
      $UpdateKey = C('Update.Local.TaskListKey');
      Gdn::Cache()->Remove($UpdateKey);
      
      $this->TaskList   = array();
      $this->Tag        = array();
      $this->Meta       = array();
   }
   
   public function Fresh() {
      $this->Reset();
      $this->Tag();
      $this->AddTask('backup','Set Restore Point', 0);
      $this->AddTask('backup/files','Backup Files', 0);
      $this->AddTask('backup/data','Backup Data', 0);
      $this->AddTask('download','Download Updates', 0);
      $this->AddTask('verify','Verify Download', 0);
      $this->AddTask('verify/extract','Extract Downloaded Archives', 0);
      $this->AddTask('verify/signatures','Check Signatures', 0);
      $this->AddTask('verify/clean','Clean Archives', 0);
      $this->AddTask('verify/registerchanged','Register Changed Files', 0);
      $this->Save();
   }
   
   /**
    * FALSE - No update in progress
    * String - Destination controller/method
    */
   protected function GetInternalAction() {
      if (!sizeof($this->TaskList) || !sizeof($this->Tag)) return FALSE;
      
      // There's an update in progress. We need to know what stage of the process we're at
      foreach ($this->TaskList as $TaskName => $Task) {
         $MainCompletion = GetValue('Completion',$Task);
         
         // Not this task
         if ($MainCompletion == TRUE || $MainCompletion == 100)
            continue;
         
         $Children = GetValue('Children', $Task);
         if (!sizeof($Children)) {
            // Not complete, and no children ... ITS YOU!
            return $Task;
         }
         
         $ChildNumber = 0;
         foreach ($Children as $ChildTaskName => $ChildTask) {
            $ChildNumber++;
            
            $ChildCompletion = GetValue('Completion', $ChildTask);

            // Not this task
            if ($ChildCompletion == TRUE || $ChildCompletion == 100)
               continue;
            
            // Parent represents first task of children
            if ($ChildNumber == 1) return $Task;
            
            return $ChildTask;
         }
      }
   }
   
   public function GetTag() {
      return (sizeof($this->Tag)) ? $this->Tag : NULL;
   }
   
   public function GetTasks() { 
      return (sizeof($this->TaskList)) ? $this->TaskList : NULL;
   }
   
   public function Load() {
      $UpdateKey = C('Update.Local.TaskListKey');
      /*if (!Gdn::Cache()->Exists($UpdateKey))
         return FALSE;*/
         
      $UpdateStr = Gdn::Cache()->Get($UpdateKey);
      if ($UpdateStr === Gdn_Cache::CACHEOP_FAILURE) {
         return FALSE;
      }
      
      $Update = Gdn_Format::unserialize($UpdateStr);
      
      $this->TaskList = GetValue('Tasks', $Update, FALSE);
      $this->Tag = GetValue('Tag', $Update, FALSE);
      $this->Meta = GetValue('Meta', $Update, FALSE);
      
      if ($this->TaskList === FALSE || $this->Tag === FALSE)
         $this->Reset();
      
      return TRUE;
   }
   
   public function Reset() {
      $this->Cancel();
   }
   
   public function Save() {
      $UpdateKey = C('Update.Local.TaskListKey');
      $Update = array(
         'Tasks'  => $this->TaskList,
         'Tag'    => $this->Tag,
         'Meta'   => $this->Meta
      );
      
      Gdn::Cache()->Store($UpdateKey, Gdn_Format::serialize($Update));
   }
   
   public function SetMeta($Key, $Data = NULL) {
      if (is_null($Data))
         unset($this->Meta[$Key]);
      else
         $this->Meta[$Key] = $Data;
         
      $this->Save();
      return $Data;
   }
   
   public function GetMeta($Key) {
      return (array_key_exists($Key, $this->Meta)) ? $this->Meta[$Key] : FALSE;
   }
   
   public function Tag($TagInfo) {
      $this->Tag = array(
         'Who'    => Gdn::Session()->User->Name,
         'When'   => date('Y-m-d H:i:s'),
         'Where'  => Gdn::Request()->GetValue('REMOTE_ADDR')
      );
   }

   public function ToString() {
      if (is_null($this->Tag) || !sizeof($this->Tag)) return '';
      
      if (StringIsNullOrEmpty($this->UpdateModuleTitle))
         $this->UpdateModuleTitle = T('Update Progress');
         
      return parent::ToString();
   }
   
   public function UpdateCompletion($TaskName, $Completion = NULL, $Save = TRUE) {
      if (!is_array($this->TaskList)) 
         $this->Reset();
      
      $ParentName = NULL;
      if (stristr($TaskName, '/'))
         list($ParentName, $TaskName) = explode('/',$TaskName);
      
      $Completion = (is_null($Completion)) ? 0 : $Completion;
      if (is_null($ParentName)) {
         if (!array_key_exists($TaskName, $this->TaskList)) return FALSE;
         
         $this->TaskList[$TaskName]['Completion'] = $Completion;
      } else {
         if (!array_key_exists($ParentName, $this->TaskList)) return FALSE;
         if (!array_key_exists($TaskName, $this->TaskList[$ParentName]['Children'])) return FALSE;
         
         $this->TaskList[$ParentName]['Children'][$TaskName]['Completion'] = $Completion;
      }
      
      if ($Save) 
         $this->Save();
   }
   
}