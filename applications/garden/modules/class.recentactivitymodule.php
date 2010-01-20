<?php if (!defined('APPLICATION')) exit();

/**
 * Garden.Modules
 */

/**
 * Renders the 5 most recent activities for use in a side panel.
 */
class RecentActivityModule extends Module {
   
   protected $_ActivityData;
   public $Form;
   
   public function __construct(&$Sender = '') {
      $this->_ActivityData = FALSE;
      parent::__construct($Sender);
   }
   
   public function GetData($Limit = 5, $DiscussionID = '') {
      $ActivityModel = new Gdn_ActivityModel();
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