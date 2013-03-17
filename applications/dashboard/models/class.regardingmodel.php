<?php if (!defined('APPLICATION')) exit();

/**
 * Regarding Model
 * 
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
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