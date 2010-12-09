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

   protected $Tasks;
   
   public function __construct(&$Sender = FALSE) {
      parent::__construct($Sender);
      $this->Tasks = array();
   }
   
   public function GetData($UpdateModel) {
      $this->Tasks = array();
      $Tasks = $UpdateModel->GetTasks();
      
      $ActiveTask = $UpdateModel->GetInternalAction();
      $this->ActiveTask = $ActiveTask;
      
      if (!is_array($Tasks)) return;
      $ActiveGroup = strtolower(GetValue('Group',$ActiveTask,NULL));
      foreach ($Tasks as $TaskName => $Task) {
         $this->Tasks[$TaskName] = array_merge($Task,array(
            'Active'       => (($ActiveGroup == $TaskName) ? TRUE : FALSE)
         ));
         
         $TotalProgressPoints = 0;
         $CompletedPoints = 0;
         foreach (GetValue('Tasks',$Task) as $ChildTask) {
            $TotalProgressPoints += 100;
            $CompletedPoints += GetValue('Completion', $ChildTask, 0);
         }
         
         $this->Tasks[$TaskName]['Completion'] = round(($CompletedPoints / $TotalProgressPoints) * 100,0);
      }
   }

   public function AssetTarget() {
      return 'Panel';
   }
   
   public function ToString() {
      if (!sizeof($this->Tasks)) return '';
      return parent::ToString();
   }
   
}