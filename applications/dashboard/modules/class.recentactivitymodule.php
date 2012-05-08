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
 * Renders the 5 most recent activities for use in a side panel.
 */
class RecentActivityModule extends Gdn_Module {
   public $ActivityData = FALSE;
   public $ActivityModuleTitle = '';
   public $Limit = 5;
   
   public function GetData($Limit = FALSE) {
      if (!$Limit)
         $Limit = $this->Limit;
      
      $ActivityModel = new ActivityModel();
      $Data = $ActivityModel->GetWhere(array('NotifyUserID' => ActivityModel::NOTIFY_PUBLIC), 0, $Limit);
      $this->ActivityData = $Data;
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if (!Gdn::Session()->CheckPermission('Garden.Activity.View'))
         return '';
      
      if (StringIsNullOrEmpty($this->ActivityModuleTitle))
         $this->ActivityModuleTitle = T('Recent Activity');
      
      if (!$this->ActivityData)
         $this->GetData();
         
      $Data = $this->ActivityData;
      if (is_object($Data) && $Data->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}