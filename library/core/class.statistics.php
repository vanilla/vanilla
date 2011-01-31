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
      $ForumAddress = Url('/',TRUE);
      $Request = array_merge($Request, array(
         'RequestTime'        => gmmktime(),
         'ServerIP'           => Gdn::Request()->GetValue('SERVER_ADDR'),
         'ServerHostname'     => $ForumAddress,
         'ServerType'         => Gdn::Request()->GetValue('SERVER_SOFTWARE'),
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
      
      if (!self::IsEnabled()) return;
      
      // Add a pageview entry
      $TimeSlot = date('Ymd');
      $Px = Gdn::Database()->DatabasePrefix;
      
      try {
         Gdn::Database()->Query("insert into {$Px}AnalyticsLocal (TimeSlot, Views) values (:TimeSlot, 1)
         on duplicate key update Views = Views+1", array(
            ':TimeSlot'    => $TimeSlot
         ));
      } catch(Exception $e) {
      
         // If we just tried to run the structure, and failed, don't blindly try again. Just go to sleep.
         if (C('Garden.Analytics.AutoStructure', FALSE)) {
            SaveToConfig('Garden.Analytics.Enabled', FALSE);
            RemoveFromConfig('Garden.Analytics.AutoStructure');
            return;
         }
         
         // If we get here, insert failed. Try proxyconnect to the utility structure
         SaveToConfig('Garden.Analytics.AutoStructure', TRUE);
         ProxyRequest(Url('utility/update', TRUE));
      }
      
      // If we get here and this is true, we successfully ran the auto structure. Remove config flag.
      if (C('Garden.Analytics.AutoStructure', FALSE))
         RemoveFromConfig('Garden.Analytics.AutoStructure');
            
      // Check if we're registered with the central server already. If not, 
      // this request is hijacked and used to perform that task instead of sending stats
      $VanillaID = C('Garden.InstallationID',NULL);
      $Sender->AddDefinition('AnalyticsTask', 'none');
      
      if (is_null($VanillaID)) {
         $Conf = PATH_LOCAL_CONF.DS.'config.php';
         if (!is_writable($Conf))
            return;
            
         $AttemptedRegistration = C('Garden.Registering',FALSE);
         // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
         if ($AttemptedRegistration !== FALSE && (time() - $AttemptedRegistration) < 60) return;
      
         $Sender->AddDefinition('AnalyticsTask', 'register');
         return;
      }
      
      // If we get here, the installation is registered and we can decide on whether or not to send stats now.
      $LastSentDate = C('Garden.Analytics.LastSentDate', FALSE);
      if ($LastSentDate === FALSE || $LastSentDate < date('Ymd', strtotime('-1 day'))) {
         $Sender->AddDefinition('AnalyticsTask','stats');
         return;
      }
   }
   
   protected function DoneRegister($Response, $Raw) {
      if (GetValue('Status', $Response, 'false') == 'success') {
         $VanillaID = GetValue('VanillaID', $Response, FALSE);
         $Secret = GetValue('Secret', $Response, FALSE);
         if (($Secret && $VanillaID) !== FALSE) {
            SaveToConfig('Garden.InstallationID', $VanillaID);
            SaveToConfig('Garden.InstallationSecret', $Secret);
            RemoveFromConfig('Garden.Registering');
         }
      }
   }

   protected function DoneStats($Response, $Raw) {
      $SuccessTimeSlot = GetValue('TimeSlot', $Response, FALSE);
      if ($SuccessTimeSlot !== FALSE)
         SaveToConfig('Garden.Analytics.LastSentDate', $SuccessTimeSlot);
   }
   
   public function Register(&$Sender) {
      if (!self::IsEnabled()) return;
      
      // Set the time we last attempted to perform registration
      SaveToConfig('Garden.Registering', time());
      
      $Request = array();
      $this->BasicParameters($Request);
      
      $this->SendPing('register', $Request, 'DoneRegister');
   }
   
   public function Stats(&$Sender) {
      if (!self::IsEnabled()) return;
      
      $Request = array();
      $this->BasicParameters($Request);
      
      $RequestTime = GetValue('RequestTime', $Request);
      $VanillaID = C('Garden.InstallationID', FALSE);
      $VanillaSecret = C('Garden.InstallationSecret', FALSE);
      if (($VanillaID && $VanillaSecret) === FALSE) return;
      
      $SecurityHash = sha1(implode('-',array(
         $VanillaSecret,
         $RequestTime
      )));
      
      // Always look at stats for the day following the previous successful send.
      $LastSentDate = C('Garden.Analytics.LastSentDate', FALSE);
      if ($LastSentDate === FALSE)
         $StatsDate = strtotime('yesterday');
      else
         $StatsDate = strtotime('+1 day', self::TimeFromTimeSlot($LastSentDate));
      
      $StatsTimeSlot = date('Ymd', $StatsDate);
      if ($StatsTimeSlot >= date('Ymd')) return;
      
      $DetectActiveInterval = 0;
      $MaxIterations = 10; $TimeSlotLimit = date('Ymd');
      do {
      
         $TimeSlot = date('Ymd',$StatsDate);
         
         // We're caught up to today. Stop looping.
         if ($TimeSlot >= $TimeSlotLimit) break;
         
         $DayStart = date('Y-m-d 00:00:00', $StatsDate);
         $DayEnd = date('Y-m-d 23:59:59', $StatsDate);
         
         // Get relevant stats
         $NumComments = Gdn::SQL()
            ->Select('DateInserted','COUNT','Hits')
            ->From('Comment')
            ->Where('DateInserted>=',$DayStart)
            ->Where('DateInserted<',$DayEnd)
            ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumComments = GetValue('Hits', $NumComments, NULL);
            
         $NumDiscussions = Gdn::SQL()
            ->Select('DateInserted','COUNT','Hits')
            ->From('Discussion')
            ->Where('DateInserted>=',$DayStart)
            ->Where('DateInserted<',$DayEnd)
            ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumDiscussions = GetValue('Hits', $NumDiscussions, NULL);
            
         $NumUsers = Gdn::SQL()
            ->Select('DateInserted','COUNT','Hits')
            ->From('User')
            ->Where('DateInserted>=',$DayStart)
            ->Where('DateInserted<',$DayEnd)
            ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumUsers = GetValue('Hits', $NumUsers, NULL);
         
         $NumViews = Gdn::SQL()
            ->Select('Views')
            ->From('AnalyticsLocal')
            ->Where('TimeSlot',$TimeSlot)
            ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumViews = GetValue('Views', $NumViews, NULL);
         
         $DetectActiveInterval = array_sum(array(
            $NumComments,
            $NumDiscussions,
            $NumUsers,
            $NumViews
         ));
      
         $StatsDate = strtotime('+1 day', $StatsDate);
         $MaxIterations--;
      } while($DetectActiveInterval == 0 && $MaxIterations);
      
      if ($DetectActiveInterval == 0) {
         // We've looped $MaxIterations times or up until yesterday and couldn't find any stats. Remember our place and return.
         SaveToConfig('Garden.Analytics.LastSentDate', $TimeSlot);
         return;
      }
      
      // Assemble Stats
      $Request = array_merge($Request, array(
         'VanillaID'          => $VanillaID,
         'SecurityHash'       => $SecurityHash,
         'TimeSlot'           => $TimeSlot,
         'CountComments'      => $NumComments,
         'CountDiscussions'   => $NumDiscussions,
         'CountUsers'         => $NumUsers,
         'CountViews'         => $NumViews,
         'ServerIP'           => Gdn::Request()->GetValue('SERVER_ADDR')
      ));
      
      $this->SendPing('stats', $Request, 'DoneStats');
   }
   
   public function SendPing($Method, $RequestParameters, $CompletionCallback = FALSE) {
      $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
   
      $ApiMethod = $Method.'.json';
      $FinalURL = CombinePaths(array(
         $AnalyticsServer,
         'vanillastats/analytics',
         $ApiMethod
      ));
      $FinalURL .= '?'.http_build_query($RequestParameters);
      
      $Response = ProxyRequest($FinalURL, FALSE, TRUE);
      if ($Response !== FALSE) {
         $JsonResponse = json_decode($Response);
         if ($JsonResponse !== FALSE)
            $JsonResponse = GetValue('Analytics', $JsonResponse, FALSE);
             
         if ($CompletionCallback !== FALSE && $JsonResponse !== FALSE) {
            call_user_func(array($this, $CompletionCallback), $JsonResponse, $Response);
         }
      }
   }
   
   public static function IsLocalhost() {
      $ServerAddress = Gdn::Request()->GetValue('SERVER_ADDR');
      $ServerHostname = Gdn::Request()->GetValue('SERVER_NAME');
      
      if ($ServerAddress == '::1') return TRUE;
      
      foreach (array(
         '10.0.0.0/8',
         '127.0.0.1/0',
         '172.16.0.0/12',
         '192.168.0.0/16') as $LocalIP) {
         if (self::CIDRCheck($ServerAddress, $LocalIP))
            return TRUE;
      }
      if ($ServerHostname == 'localhost' || substr($ServerHostname,-6) == '.local') return TRUE;
      return FALSE;
   }
   
   public static function IsEnabled() {
      if (!C('Garden.Installed', FALSE)) return FALSE;
   
      // Enabled if not explicitly disabled via config
      if (!C('Garden.Analytics.Enabled', TRUE)) return FALSE;
      
      // Don't track things for local sites (unless overridden in config)
      if (self::IsLocalhost() && !C('Garden.Analytics.AllowLocal', FALSE)) return FALSE;
      
      return TRUE;
   }
   
   public static function TimeFromTimeSlot($TimeSlot) {
      $Year = substr($TimeSlot,0,4);
      $Month = substr($TimeSlot,4,2);
      $Day = (int)substr($TimeSlot,6,2);
      if ($Day == 0) $Day = 1;
      $DateRaw = mktime(0, 0, 1, $Month, $Day, $Year);
      
      if ($DateRaw === FALSE)
         throw new Exception("Invalid timeslot '{$TimeSlot}', unable to convert to epoch");
      
      return $DateRaw;
   }
   
   // credit: claudiu(at)cnixs.com via php.net/manual/en/ref.network.php
   public static function CIDRCheck($IP, $CIDR) {
      list ($net, $mask) = split ("/", $CIDR);
      
      $ip_net = ip2long ($net);
      $ip_mask = ~((1 << (32 - $mask)) - 1);
      
      $ip_ip = ip2long ($IP);
      
      $ip_ip_net = $ip_ip & $ip_mask;
      
      return ($ip_ip_net == $ip_net);
   }
   
}