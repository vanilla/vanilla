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
 * Handles install-side statistics gathering and sending.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_Statistics extends Gdn_Pluggable {
   
   public function __construct() {

   }
   
   protected function BasicParameters(&$Request) {
      $Request = array_merge($Request, array(
         'RequestTime'        => time(),
         'ServerIP'           => GetValue('SERVER_ADDR', $_SERVER),
         'ServerHostname'     => GetValue('SERVER_NAME', $_SERVER),
         'ServerType'         => GetValue('SERVER_SOFTWARE', $_SERVER),
         'PHPVersion'         => phpversion(),
         'VanillaVersion'     => APPLICATION_VERSION
      ));
   }
   
   /**
    * Hook on each page request to allow stats sending
    *
    * This method is called each page request and checks the environment. If
    * a stats send is warranted, sets data on the sender and sends stats to
    * analytics server.
    *
    * If the site is not registered at the analytics server (does not contain 
    * a guid), register instead and defer stats till next request.
    */ 
   public function Check(&$Sender) {

      // First, check if we're registered with the central server already. If not, 
      // this request is hijacked and used to perform that task.
      $VanillaID = C('Vanilla.InstallationID',NULL);
      $Sender->AddDefinition('AnalyticsTask', 'none');
      
      if (is_null($VanillaID)) {
         $Sender->AddDefinition('AnalyticsTask', 'register');
         return;
      }
      
      // If we get here, the installation is registered and we can decide on whether or not to send stats now.
      $LastSentDate = C('Vanilla.Analytics.LastSentDate', FALSE);
      if ($LastSentDate !== FALSE && $LastSentDate < date('Ymd')) {
         $Sender->AddDefinition('AnalyticsTask','stats');
         return;
      }
      
   }
   
   protected function DoneRegister(&$Sender) {
   
   }

   protected function DoneStats(&$Sender) {
   
   }
   
   public function Register(&$Sender) {
      $AttemptedRegistration = C('Vanilla.Registering',FALSE);
      // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
      if ($AttemptedRegistration !== FALSE && (time() - $AttemptedRegistration) < 60) return;
      
      // Set the time we last attempted to perform registration
      //SaveToConfig('Vanilla.Registering', time());
      
      $Request = array();
      $this->BasicParameters($Request);
      
      $this->SendPing('register', $Request, 'DoneRegister');
   }
   
   public function Stats(&$Sender) {
      $Request = array();
      $this->BasicParameters($Request);
      
      $RequestTime = $Sender->Data('RequestTime');
      $VanillaSecret = C('Vanilla.InstallationSecret', NULL);
      $SecurityHash = sha1(implode('-',array(
         $VanillaSecret,
         $RequestTime
      )));
      
      // Get relevant stats
      $Request = array_merge($Request, array(
         'VanillaID'          => $VanillaID,
         'SecurityHash'       => $SecurityHash,
         'CountUsers'         => 0,
         'CountDiscussions'   => 0,
         'CountComments'      => 0,
         'CountViews'         => 0,
         'ServerIP'           => GetValue('SERVER_ADDR', $_SERVER)
      ));
      
      $this->SendPing('stats', $Request, 'DoneStats');
   }
   
   public function SendPing($Method, $RequestParameters, $CompletionCallback) {
      $AnalyticsServer = 'http://analytics.vanillaforums.com/vanillastats/analytics';
      
      // @TODO: testing, remove before final release
      $AnalyticsServer = 'http://dev.vanilla.tim/vanillastats/analytics';
   
      $ApiMethod = $Method.'.json';
      $FinalURL = CombinePaths(array(
         $AnalyticsServer,
         $ApiMethod
      ));
      ProxyRequest($FinalURL);
      
   }
   
}