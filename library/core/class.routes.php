<?php

class Gdn_Routes {

   public $Routes;
   public $ReservedRoutes;
   public $RouteTypes;

   public function __construct() {
      $this->RouteTypes = array(
         'Internal'     => 'Internal',
         'Temporary'    => 'Temporary (302)',
         'Permanent'    => 'Permanent (301)',
         'NotFound'     => 'Not Found (404)'
      );
      $this->ReservedRoutes = array('DefaultController', 'Default404', 'DefaultPermission');
      $this->_LoadRoutes();
   }
   
   public function GetRoute($Route) {
      if (is_numeric($Route) && $Route !== FALSE) {
         $Keys = array_keys($this->Routes);
         $Route = ArrayValue($Route, $Keys);
      }
      
      $Decoded = $this->_DecodeRouteKey($Route);
      if ($Decoded !== FALSE && array_key_exists($Decoded, $this->Routes))
         $Route = $Decoded;
      
      if ($Route === FALSE || !array_key_exists($Route, $this->Routes))
         return FALSE;
      
      //return $this->Routes[$Route];

      return array_merge($this->Routes[$Route],array(
         'TypeLocale'   => T($this->RouteTypes[$this->Routes[$Route]['Type']])
      ));

   }
   
   /**
    * Update or add a route to the config table
    * 
    * @return 
    */
   public function SetRoute($Route, $Destination, $Type) {
      $Key = $this->_EncodeRouteKey($Route);
      SaveToConfig('Routes.'.$Key.'.0', $Destination);
      die("Setting [{$Route}] -> $Destination (Step 2)\n");
      SaveToConfig('Routes.'.$Key.'.1', $Type);
      
      $this->_LoadRoutes();
   }
   
   public function DeleteRoute($Route) {
      $Route = $this->GetRoute($Route);
      
      // Is a valid route?
      if ($Route !== FALSE) {
         if (!in_array($Route['Route'],$this->ReservedRoutes))
         {
            RemoveFromConfig('Routes.'.$Route['Key']);
            $this->_LoadRoutes();
         }
      }
   }
   
   public function MatchRoute($Request) {
   
      // Check for a literal match
      if ($this->GetRoute($Request))
         return $this->GetRoute($Request);
         
      foreach ($this->Routes as $Route => $RouteData)
      {
         // Check for wild-cards
         $Route = str_replace(
            array(':alphanum', ':num'),
            array('([0-9a-zA-Z-_]+)', '([0-9]+)'),
            $Route
         );
         
         // Check for a match
         if (preg_match('#^'.$Route.'$#', $Request)) {
            // Route matched!
            
            return $this->GetRoute($Route);
         }
      }
      
      return FALSE; // No route matched
   }
   
   public function GetRouteTypes() {
      $RT = array();
      foreach ($this->RouteTypes as $RouteType => $RouteTypeText) {
         $RT[$RouteType] = T($RouteTypeText);
      }
      return $RT;
   }
   
   private function _LoadRoutes() {
      $Routes = Gdn::Config('Routes', array());
      foreach ($Routes as $Key => $Destination) {
         $Route = $this->_DecodeRouteKey($Key);
         
         $RouteData = $this->_ParseRoute($Destination);
         $this->Routes[$Route] = array_merge(array(
            'Route'        => $Route,
            'Key'          => $Key,
            'Reserved'     => in_array($Route,$this->ReservedRoutes)
         ), $RouteData);
      }
   }
   
   private function _ParseRoute($Destination) {
      // If Destination is a simple string...
      if (!is_array($Destination))
         $Destination = $this->_FormatRoute($Destination, 'Internal');
      
      // If Destination is an array with no keys...
      if (!array_key_exists('Destination', $Destination))
         $Destination = $this->_FormatRoute($Destination[0], $Destination[1]);
            
      return $Destination;
   }
   
   private function _FormatRoute($Destination, $RouteType) {
      return array(
         'Destination'        => $Destination,
         'Type'               => $RouteType
      );
   }
   
   protected function _EncodeRouteKey($Key) {
      return in_array($Key,$this->ReservedRoutes) ? $Key : base64_encode($Key);
   }
   
   protected function _DecodeRouteKey($Key) {
      return in_array($Key,$this->ReservedRoutes) ? $Key : base64_decode($Key);
   }

}

?>