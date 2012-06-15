<?php if (!defined('APPLICATION')) exit();

class TraceModule extends Gdn_Module {
   
   public function __construct() {
      parent::__construct();
      $this->_ApplicationFolder = 'dashboard';
   }
   
   public function AssetTarget() {
      return 'Content';
   }
   
   public function ToString() {
      try {
         $Traces = Trace();
         if (!$Traces)
            return '';

         $this->SetData('Traces', $Traces);
      
         return $this->FetchView();
      } catch (Exception $Ex) { return $Ex->getMessage(); }
   }
}