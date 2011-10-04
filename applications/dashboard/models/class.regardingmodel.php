<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class RegardingModel extends Gdn_Model {
   
   /**
    * Class constructor. Defines the related database table name.
    */
   public function __construct() {
      parent::__construct('Regarding');
   }
   
   public function GetID($RegardingID) {
      $Regarding = $this->GetWhere(array('RegardingID' => $RegardingID))->FirstRow();
      return $Regarding;
   }
   
   public function Get($ForeignType, $ForeignID) {
      return $this->GetWhere(array(
         'ForeignType'  => $ForeignType,
         'ForeignID'    => $ForeignID
      ))->FirstRow(DATASET_TYPE_ARRAY);
   }
   
   public function GetRelated($Type, $ForeignType, $ForeignID) {
      return $this->GetWhere(array(
         'Type'         => $Type,
         'ForeignType'  => $ForeignType,
         'ForeignID'    => $ForeignID
      ))->FirstRow(DATASET_TYPE_ARRAY);
   }
   
   public function GetAll($ForeignType, $ForeignIDs = array()) {
      if (count($ForeignIDs) == 0) {
         return new Gdn_DataSet(array());
      }
      
      return Gdn::SQL()->Select('*')
         ->From('Regarding')
         ->Where('ForeignType', $ForeignType)
         ->WhereIn('ForeignID', $ForeignIDs)
         ->Get();
   }
   
}