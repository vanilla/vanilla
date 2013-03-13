<?php if (!defined('APPLICATION')) exit();

/**
 * Routing system
 * 
 * Allows paths within the application to redirect, either internally or via
 * http, to other locations.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Router extends Gdn_Pluggable {

   public $Routes;
   public $ReservedRoutes;
   public $RouteTypes;

   public function __construct() {
      parent::__construct();
      $this->RouteTypes = array(
         'Internal'     => 'Internal',
         'Temporary'    => 'Temporary (302)',
         'Permanent'    => 'Permanent (301)',
         'NotAuthorized' => 'Not Authorized (401)',
         'NotFound'     => 'Not Found (404)',
         'Test'         => 'Test'
      );
      $this->ReservedRoutes = array('DefaultController', 'DefaultForumRoot', 'Default404', 'DefaultPermission', 'UpdateMode');
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
         'TypeLocale'         => T($this->RouteTypes[$this->Routes[$Route]['Type']]),
         'FinalDestination'   => $this->Routes[$Route]['Destination']
      ));

   }
   
   public function GetDestination($Request) {
      $Route = $this->MatchRoute($Request);
      
      if ($Route !== FALSE)
         return isset($Route['FinalDestination']) ? $Route['FinalDestination'] : $Route['Destination'];
      
      return FALSE;
   }
   
   /**
    * Update or add a route to the config table
    * 
    * @param string $Route
    * @param string $Destination
    * @param string $Type
    * @param bool $Save Optional. Save this to the config or just in memory?
    */
   public function SetRoute($Route, $Destination, $Type, $Save = TRUE) {
      $Key = $this->_EncodeRouteKey($Route);
      SaveToConfig('Routes.'.$Key, array($Destination, $Type), $Save);
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
         if (preg_match('#^'.$Route.'#', $Request)) {
            // Route matched!
            $Final = $this->GetRoute($Route);
            $Final['FinalDestination'] = $Final['Destination'];
            
            // Do we have a back-reference?
            if (strpos($Final['Destination'], '$') !== FALSE && strpos($Final['Route'], '(') !== FALSE) {
               $Final['FinalDestination'] = preg_replace('#^'.$Final['Route'].'#', $Final['Destination'], $Request);
            }
               
            return $Final;
         }
      }
      
      return FALSE; // No route matched
   }

   public function ReverseRoute($Url) {
      $Root = rtrim(Gdn::Request()->Domain().'/'.Gdn::Request()->WebRoot(), '/');

      if (StringBeginsWith($Url, $Root)) {
         $Url = StringBeginsWith($Url, $Root, TRUE, TRUE);
         $WithDomain = TRUE;
      } else {
         $WithDomain = FALSE;
      }

      $Url = '/'.ltrim($Url, '/');

      foreach ($this->Routes as $Route => $RouteData) {
         if ($RouteData['Type'] != 'Internal' || ($RouteData['Reserved'] && $RouteData['Route'] != 'DefaultController'))
            continue;

         $Destination = '/'.ltrim($RouteData['Destination'], '/');
         if ($Destination == $Url) {
            $Route = '/'.ltrim($RouteData['Route'], '/');
            
            if ($Route == '/DefaultController')
               $Route = '/';

            if ($WithDomain)
               return $Root.$Route;
            else
               return $Route;
         }
      }
      if ($WithDomain)
         return $Root.$Url;
      else
         return $Url;
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
      $this->EventArguments['Routes'] = &$Routes;
      $this->FireEvent("BeforeLoadRoutes");
      foreach ($Routes as $Key => $Destination) {
         $Route = $this->_DecodeRouteKey($Key);
         $RouteData = $this->_ParseRoute($Destination);
         
         $this->Routes[$Route] = array_merge(array(
            'Route'        => $Route,
            'Key'          => $Key,
            'Reserved'     => in_array($Route,$this->ReservedRoutes)
         ), $RouteData);
      }
      $this->FireEvent("AfterLoadRoutes");
   }
   
   private function _ParseRoute($Destination) {
   
      // If Destination is a serialized array
      if (is_string($Destination) && ($Decoded = @unserialize($Destination)) !== FALSE)
         $Destination = $Decoded;
   
      // If Destination is a short array
      if (is_array($Destination) && sizeof($Destination) == 1)
         $Destination = $Destination[0];
   
      // If Destination is a simple string...
      if (!is_array($Destination))
         $Destination = $this->_FormatRoute($Destination, 'Internal');
      
      // If Destination is an array with no named keys...
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
      return str_replace('/','_',in_array($Key,$this->ReservedRoutes) ? $Key : base64_encode($Key));
   }
   
   protected function _DecodeRouteKey($Key) {
      return in_array($Key,$this->ReservedRoutes) ? $Key : base64_decode(str_replace('_','/',$Key));
   }

}

?>