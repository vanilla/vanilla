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
   
   protected $_ActivityData;
   public $Form;
   
   public function __construct(&$Sender = '') {
      $this->_ActivityData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 5, $DiscussionID = '') {
      $ActivityModel = new ActivityModel();
      $this->_ActivityData = $ActivityModel->Get('', 0, $Limit);
   }

   public function AssetTarget() {
      return 'Panel';
   }

   public function ToString() {
      if ($this->_ActivityData !== FALSE && $this->_ActivityData->NumRows() > 0)
         return parent::ToString();

      return '';
   }
}