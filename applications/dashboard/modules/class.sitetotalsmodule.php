<?php if (!defined('APPLICATION')) exit();

class SiteTotalsModule extends Gdn_Module {
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'dashboard';
   }
   
   public function AssetTarget() {
      return 'Panel';
   }
   
   protected function _GetData() {
      $Px = Gdn::Database()->DatabasePrefix;
      $Sql = "show table status where Name in ('{$Px}User', '{$Px}Discussion', '{$Px}Comment')";
      
      $Data = Gdn::Database()->Query($Sql, NULL, array())->ResultArray();
      $Result = array('User' => 0, 'Discussion' => 0, 'Comment' => 0);
      foreach ($Data as $Row) {
         $Name = StringBeginsWith($Row['Name'], $Px, TRUE, TRUE);
         $Result[$Name] = $Row['Rows'];
      }
      $this->SetData('Totals', $Result);
   }
   
   public function ToString() {
      $this->_GetData();
      return parent::ToString();
   }
}