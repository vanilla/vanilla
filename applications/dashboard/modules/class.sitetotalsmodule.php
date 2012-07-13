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
      
      $Result = array('User' => 0, 'Discussion' => 0, 'Comment' => 0);
      foreach ($Result as $Name => $Value) {
         $Result[$Name] = $this->GetCount($Name);
      }
      $this->SetData('Totals', $Result);
   }
   
   protected function GetCount($Table) {
      // Try and get the count from the cache.
      $Key = "$Table.CountRows";
      $Count = Gdn::Cache()->Get($Key);
      if ($Count !== Gdn_Cache::CACHEOP_FAILURE)
         return $Count;
      
      // The count wasn't in the cache so grab it from the table.
      $Count = Gdn::SQL()
         ->Select($Table.'ID', 'count', 'CountRows')
         ->From($Table)
         ->Get()->Value('CountRows');
      
      // Save the value to the cache.
      Gdn::Cache()->Store($Key, $Count, array(Gdn_Cache::FEATURE_EXPIRY => 5 * 60 + mt_rand(0, 30)));
      return $Count;
   }
   
   public function ToString() {
      $this->_GetData();
      return parent::ToString();
   }
}