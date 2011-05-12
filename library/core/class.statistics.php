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
 * Handles install-side analytics gathering and sending.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @since 2.0.17
 * @namespace Garden.Core
 */

class Gdn_Statistics extends Gdn_Plugin {
   
   public function __construct() {
      parent::__construct();
   }
   
   /**
    * Automatically configures a ProxyRequest array with basic parameters
    * such as IP, VanillaVersion, RequestTime, Hostname, PHPVersion, ServerType.
    * 
    * @param array $Request Reference to the existing request array
    * @return void
    */
   protected function BasicParameters(&$Request) {
      $Request = array_merge($Request, array(
         'RequestTime'        => gmmktime(),
         'ServerIP'           => Gdn::Request()->GetValue('SERVER_ADDR'),
         'ServerHostname'     => Url('/', TRUE),
         'ServerType'         => Gdn::Request()->GetValue('SERVER_SOFTWARE'),
         'PHPVersion'         => phpversion(),
         'VanillaVersion'     => APPLICATION_VERSION
      ));
   }
   
   /**
    * Gets/Sets the Garden Analytics InstallationID
    * 
    * @staticvar boolean $InstallationID
    * @param string $SetInstallationID
    * @return string Installation ID or NULL
    */
   public static function InstallationID($SetInstallationID = NULL) {
      static $InstallationID = FALSE;
      if (!is_null($SetInstallationID)) {
         SaveToConfig ('Garden.Analytics.InstallationID', $SetInstallationID);
         $InstallationID = $SetInstallationID;
      }
      
      if ($InstallationID === FALSE)
         $InstallationID = C('Garden.Analytics.InstallationID', NULL);
      
      return $InstallationID;
   }
   
   /**
    * Gets/Sets the Garden Analytics Installation Secret
    * 
    * @staticvar boolean $InstallationSecret
    * @param string $SetInstallationSecret
    * @return string Installation Secret or NULL
    */
   public static function InstallationSecret($SetInstallationSecret = NULL) {
      static $InstallationSecret = FALSE;
      if (!is_null($SetInstallationSecret)) {
         SaveToConfig ('Garden.Analytics.InstallationSecret', $SetInstallationSecret);
         $InstallationSecret = $SetInstallationSecret;
      }
      
      if ($InstallationSecret === FALSE)
         $InstallationSecret = C('Garden.Analytics.InstallationSecret', NULL);
      
      return $InstallationSecret;
   }
   
   public function Base_Render_Before($Sender) {
      // If this is a full page request, trigger stats environment check
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
         $this->Check();
   }
   
   public function SettingsController_AnalyticsTick_Create(&$Sender) {
      Gdn::Statistics()->Tick();
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->Render('tick','statistics','dashboard');
   }
   
   /**
    * This method is called each page request and checks the environment. If
    * a stats send is warranted, tells the browser to ping us back.
    *
    * If the site is not registered at the analytics server (does not contain 
    * a guid), have the browser request a register instead and defer stats until
    * next request.
    * 
    * @return void
    */ 
   public function Check() {
      
      // If we're local and not allowed, or just directly disabled, short circuit here
      if (!self::CheckIsEnabled()) return;
      
      // If we're hitting an exception app, short circuit here
      if (!self::CheckIsAllowed()) return;
      
      // If the config file is not writable, show a warning to admin users and return
      $ConfFile = PATH_LOCAL_CONF.DS.'config.php';
      if (!is_writable($ConfFile)) {
         // Admins see a helpful notice
         if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
            $Warning = '<span class="InformSprite Sliders"></span> ';
            $Warning .= T('Your config.php file is not writable.<br/> Find out <a href="http://vanillaforums.org/docs/vanillastatistics">how to fix this &raquo;</a>');
            Gdn::Controller()->InformMessage($Warning, array('CssClass' => 'HasSprite'));
         }
         return;
      }
      
      // At this point there is nothing preventing stats from working, so queue a tick
      Gdn::Controller()->AddDefinition('AnalyticsTask', 'tick');
   }
   
   /**
    * This is the asynchronous callback
    * 
    * This method is triggerd on every page request via a callback AJAX request
    * so that it may execute asychronously and reduce lag for users. It tracks
    * views, handles registration for new installations, and sends stats every 
    * day as needed.
    * 
    * @return void;
    */
   public function Tick() {
      
      // If we're local and not allowed, or just directly disabled, gtfo
      if (!self::CheckIsEnabled()) return;
      
      // If the config file is not writable, gtfo
      $ConfFile = PATH_LOCAL_CONF.DS.'config.php';
      if (!is_writable($ConfFile))
         return;
      
      $InstallationID = Gdn_Statistics::InstallationID();
      
      // Check if we're registered with the central server already. If not, this request is 
      // hijacked and used to perform that task instead of sending stats or recording a tick.
      if (is_null($InstallationID)) {
         $AttemptedRegistration = C('Garden.Analytics.Registering',FALSE);
         // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
         if ($AttemptedRegistration !== FALSE && (time() - $AttemptedRegistration) < 60) return;
      
         return $this->Register();
      }
      
      // Add a pageview entry
      $TimeSlot = date('Ymd');
      $Px = Gdn::Database()->DatabasePrefix;
      
      try {
         Gdn::Database()->Query("insert into {$Px}AnalyticsLocal (TimeSlot, Views) values (:TimeSlot, 1)
         on duplicate key update Views = Views+1", array(
            ':TimeSlot'    => $TimeSlot
         ));
      } catch(Exception $e) {
      
         // If we just tried to run the structure, and failed, don't blindly try again. 
         // Just disable ourselves quietly.
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

      // If we get here, the installation is registered and we can decide on whether or not to send stats now.
      $LastSentDate = C('Garden.Analytics.LastSentDate', FALSE);
      if ($LastSentDate === FALSE || $LastSentDate < date('Ymd', strtotime('-1 day')))
         return $this->Stats();
   }
   
   protected function DoneRegister($Response, $Raw) {
      $VanillaID = GetValue('VanillaID', $Response, FALSE);
      $Secret = GetValue('Secret', $Response, FALSE);
      if (($Secret && $VanillaID) !== FALSE) {
         Gdn_Statistics::InstallationID($VanillaID);
         Gdn_Statistics::InstallationSecret($Secret);
         RemoveFromConfig('Garden.Analytics.Registering');
      }
   }

   protected function DoneStats($Response, $Raw) {
      $SuccessTimeSlot = GetValue('TimeSlot', $Response, FALSE);
      if ($SuccessTimeSlot !== FALSE)
         SaveToConfig('Garden.Analytics.LastSentDate', $SuccessTimeSlot);
   }
   
   protected function Register() {
      // Set the time we last attempted to perform registration
      SaveToConfig('Garden.Analytics.Registering', time());
      
      // Request registration from remote server
      $Request = array();
      $this->BasicParameters($Request);
      $this->Analytics('Register', $Request, array(
          'Success'     => 'DoneRegister',
          'Failure'     => 'AnalyticsFailed'
      ));
   }
   
   protected function Stats() {
      $Request = array();
      $this->BasicParameters($Request);
      
      $RequestTime = GetValue('RequestTime', $Request);
      $VanillaID = Gdn_Statistics::InstallationID();
      $VanillaSecret = Gdn_Statistics::InstallationSecret();
      // Don't try to send stats if we don't have a proper install
      if (is_null($VanillaID) || is_null($VanillaSecret))
         return;
      
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
         'CountViews'         => $NumViews
      ));
      
      // Send stats to remote server
      $this->Analytics('Stats', $Request, array(
          'Success'     => 'DoneStats',
          'Failure'     => 'AnalyticsFailed'
      ));
   }
   
   protected function Analytics($Method, $RequestParameters, $Callback = FALSE) {
      $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
   
      $ApiMethod = strtolower($Method).'.json';
      $FinalURL = CombinePaths(array(
         $AnalyticsServer,
         'analytics',
         $ApiMethod
      ));
      
      $FinalURL .= '?'.http_build_query($RequestParameters);
      $Response = ProxyRequest($FinalURL, FALSE, TRUE);
      if ($Response !== FALSE) {
         $JsonResponse = json_decode($Response);
         if ($JsonResponse !== FALSE)
            $JsonResponse = GetValue('Analytics', $JsonResponse, FALSE);
         
         // If we received a reply, parse it
         if ($JsonResponse !== FALSE)
            $this->ParseAnalyticsResponse($JsonResponse, $Response, $Callback);
      }
   }
   
   protected function AnalyticsFailed($JsonResponse) {
      // No more analytics for 1 hour if we error out
      $Throttle = C('Garden.Analytics.ThrottleDelay', 3600);
      
      // Only do more requests after this time.
      SaveToConfig('Garden.Analytics.Throttle', time() + $Throttle);
      
      $Reason = GetValue('Reason', $JsonResponse, NULL);
      if (!is_null($Reason))
         Gdn::Controller()->InformMessage("Analytics: {$Reason}");
   }
   
   protected function ParseAnalyticsResponse($JsonResponse, $RawResponse, $Callbacks) {
      if (is_string($Callbacks)) {
         // Assume a string is the Success event handler
         $Callbacks = array(
             'Success'    => $Callbacks
         );
      }
      
      // Assume strings are local methods
      foreach ($Callbacks as $Event => &$CallbackMethod)
         if (is_string($CallbackMethod))
            $CallbackMethod = array($this, $CallbackMethod);
      
      $ResponseCode = GetValue('Status', $JsonResponse, 500);
      switch ($ResponseCode) {
         case 200:
            $CallbackExecute = GetValue('Success', $Callbacks, NULL);
            break;
         
         case 500:
            $CallbackExecute = GetValue('Failure', $Callbacks, NULL);
            break;
      }
      
      if (!is_null($CallbackExecute))
         call_user_func($CallbackExecute, $JsonResponse, $RawResponse);
   }
   
   public static function CheckIsAllowed() {
      
      // If we've recently received an error response, wait until the throttle expires
      if (C('Garden.Analytics.Throttle', 0) > time()) return FALSE;
      
      // These applications are not included in statistics
      $ExceptionApplications = array('dashboard');
      
      // ... unless one of these paths is requested
      $ExceptionPaths = array('profile*','activity*');
      
      $Path = Gdn::Request()->Path();
      foreach ($ExceptionPaths as $ExceptionPath)
         if (fnmatch($ExceptionPath, $Path)) return TRUE;
      
      $ApplicationFolder = Gdn::Controller()->ApplicationFolder;
      if (in_array($ApplicationFolder, $ExceptionApplications)) return FALSE;
      
      return TRUE;
   }
   
   public static function CheckIsLocalhost() {
      $ServerAddress = Gdn::Request()->GetValue('SERVER_ADDR');
      $ServerHostname = Gdn::Request()->GetValue('SERVER_NAME');
      
      // IPv6 Localhost
      if ($ServerAddress == '::1') return TRUE;
      
      // Private subnets
      foreach (array(
         '127.0.0.1/0',
         '10.0.0.0/8',
         '172.16.0.0/12',
         '192.168.0.0/16') as $LocalCIDR) {
         if (self::CIDRCheck($ServerAddress, $LocalCIDR))
            return TRUE;
      }
      
      // Comment local hostnames / hostname suffixes 
      if ($ServerHostname == 'localhost' || substr($ServerHostname,-6) == '.local') return TRUE;
      
      // If we get here, we're likely public
      return FALSE;
   }
   
   public static function CheckIsEnabled() {
      // Forums that are busy installing should not yet be tracked
      if (!C('Garden.Installed', FALSE)) return FALSE;
   
      // Enabled if not explicitly disabled via config
      if (!C('Garden.Analytics.Enabled', TRUE)) return FALSE;
      
      // Don't track things for local sites (unless overridden in config)
      if (self::CheckIsLocalhost() && !C('Garden.Analytics.AllowLocal', FALSE)) return FALSE;
      
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
      list ($net, $mask) = explode("/", $CIDR);
      
      // Allow non-standard /0 syntax
      if ($mask == 0) {
         if (ip2long($IP) == ip2long($net)) return true;
         else return false;
      }
      
      $ip_net = ip2long ($net);
      $ip_mask = ~((1 << (32 - $mask)) - 1);
      
      $ip_ip = ip2long ($IP);
      
      $ip_ip_net = $ip_ip & $ip_mask;
      
      return ($ip_ip_net == $ip_net);
   }
   
}