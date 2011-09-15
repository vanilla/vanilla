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
   
   public function Analytics($Method, $RequestParameters, $Callback = FALSE) {
      
      $AnalyticsServer = C('Garden.Analytics.Remote','http://analytics.vanillaforums.com');
   
      $FullMethod = explode('/',$Method);
      if (sizeof($FullMethod) < 2)
         array_unshift($FullMethod, "analytics");
      
      list($ApiController, $ApiMethod) = $FullMethod;
      $ApiController = strtolower($ApiController);
      $ApiMethod = StringEndsWith(strtolower($ApiMethod), '.json', TRUE, TRUE).'.json';
      
      $FinalURL = CombinePaths(array(
         $AnalyticsServer,
         $ApiController,
         $ApiMethod
      ));
      
      // Sign request
      $this->Sign($RequestParameters, TRUE);
      
      $FinalURL .= '?'.http_build_query($RequestParameters);
      try {
         $Response = ProxyRequest($FinalURL, 10, TRUE);
      } catch (Exception $e) {
         $Response = FALSE;
      }
      if ($Response !== FALSE) {
         $JsonResponse = json_decode($Response);
         if ($JsonResponse !== FALSE)
            $JsonResponse = (array)GetValue('Analytics', $JsonResponse, FALSE);
         
         // If we received a reply, parse it
         if ($JsonResponse !== FALSE) {
            $this->ParseAnalyticsResponse($JsonResponse, $Response, $Callback);
            return $JsonResponse;
         }
      }
      
      return FALSE;
   }
   
   protected function AnalyticsFailed($JsonResponse) {
      self::Throttled(TRUE);
      
      $Reason = GetValue('Reason', $JsonResponse, NULL);
      if (!is_null($Reason))
         Gdn::Controller()->InformMessage("Analytics: {$Reason}");
   }
   
   public function Base_Render_Before($Sender) {
      // If this is a full page request, trigger stats environment check
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL)
         $this->Check();
   }
   
   /**
    * Automatically configures a ProxyRequest array with basic parameters
    * such as IP, VanillaVersion, RequestTime, Hostname, PHPVersion, ServerType.
    * 
    * @param array $Request Reference to the existing request array
    * @return void
    */
   public function BasicParameters(&$Request) {
      $Request = array_merge($Request, array(
         'ServerHostname'     => Url('/', TRUE),
         'ServerType'         => Gdn::Request()->GetValue('SERVER_SOFTWARE'),
         'PHPVersion'         => phpversion(),
         'VanillaVersion'     => APPLICATION_VERSION
      ));
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
      if (!self::CheckIsEnabled()) {
         return;
      }
      
      // If we're hitting an exception app, short circuit here
      if (!self::CheckIsAllowed()) {
         return;
      }
      
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
      
      // At this point there is nothing preventing stats from working, so queue a tick.
      Gdn::Controller()->AddDefinition('AnalyticsTask', 'tick');
      
   }
   

   
   public static function CheckIsAllowed() {
      
      // If we've recently received an error response, wait until the throttle expires
      if (self::Throttled()) {
         return FALSE;
      }
      
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
      $ServerAddress = Gdn::Request()->IpAddress();
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
   
   protected function DoneRegister($Response, $Raw) {
      $VanillaID = GetValue('VanillaID', $Response, FALSE);
      $Secret = GetValue('Secret', $Response, FALSE);
      if (($Secret && $VanillaID) !== FALSE) {
         Gdn::InstallationID($VanillaID);
         Gdn::InstallationSecret($Secret);
         Gdn::Set('Garden.Analytics.Registering', NULL);
         Gdn::Set('Garden.Analytics.LastSentDate', NULL);
      }
   }

   protected function DoneStats($Response, $Raw) {
      $SuccessTimeSlot = GetValue('TimeSlot', $Response, FALSE);
      if ($SuccessTimeSlot !== FALSE)
         self::LastSentDate($SuccessTimeSlot);
   }

   public static function LastSentDate($SetLastSentDate = NULL) {
      static $LastSentDate = NULL;
      
      // Set
      if (!is_null($SetLastSentDate)) {
         $LastSentDate = $SetLastSentDate;
         Gdn::Set('Garden.Analytics.LastSentDate', $LastSentDate);
      }
      
      // Lazy Load
      if ($LastSentDate === NULL)
         $LastSentDate = Gdn::Get('Garden.Analytics.LastSentDate', FALSE);
      
      return $LastSentDate;
   }
   
   protected function ParseAnalyticsResponse($JsonResponse, $RawResponse, $Callbacks) {
      
      // Verify signature of reply
      $Verified = $this->VerifySignature($JsonResponse);
      if ($Verified === FALSE)
         return;
      
      // Only allow commands when verification was explicitly successful
      if ($Verified === TRUE) {
         // Perform response commands
         foreach ($JsonResponse as $CommandName => $CommandValue) {
            switch ($CommandName) {
               case 'DoDeregister':
                  if ($Verified) {
                     // De-register yourself
                     Gdn::InstallationID(FALSE);
                     Gdn::InstallationSecret(FALSE);
                  }
                  break;

               case 'DoDisable':
                  if ($Verified) {
                     // Turn yourself off
                     SaveToConfig('Garden.Analytics.Enabled', FALSE);
                  }
                  break;

               case 'DoCall':
                  // Call the admin's attention to the statistics
                  Gdn::Set('Garden.Analytics.Notify', $CommandValue);
                  break;

               default:
                  // Nothing
                  break;
            }
         }
      }
      
      if (is_string($Callbacks)) {
         // Assume a string is the Success event handler
         $Callbacks = array(
             'Success'    => $Callbacks
         );
      }
      
      if (!is_array($Callbacks))
         $Callbacks = array();
      
      // Assume strings are local methods
      foreach ($Callbacks as $Event => &$CallbackMethod)
         if (is_string($CallbackMethod))
            $CallbackMethod = array($this, $CallbackMethod);
      
      $ResponseCode = GetValue('Status', $JsonResponse, 500);
      $CallbackExecute = NULL;
      switch ($ResponseCode) {
         case FALSE:
         case 500:
            if (array_key_exists('Failure', $Callbacks))
               $CallbackExecute = $Callbacks['Failure'];
            break;
            
         case TRUE:
         case 200:
            self::Throttled(FALSE);
            if (array_key_exists('Success', $Callbacks))
               $CallbackExecute = $Callbacks['Success'];
            break;
      }
      
      if (!is_null($CallbackExecute))
         call_user_func($CallbackExecute, $JsonResponse, $RawResponse);
   }
   
   public function Register() {
      
      // Set the time we last attempted to perform registration
      Gdn::Set('Garden.Analytics.Registering', time());
      
      // Request registration from remote server
      $Request = array();
      $this->BasicParameters($Request);
      $this->Analytics('Register', $Request, array(
          'Success'     => 'DoneRegister',
          'Failure'     => 'AnalyticsFailed'
      ));
   }
   
   public function SettingsController_AnalyticsTick_Create(&$Sender) {
      Gdn::Statistics()->Tick();
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->Render('tick','statistics','dashboard');
   }
   
   /**
    * Sign a request or response
    * 
    * Uses the known site secret to sign the given request or response. The 
    * request/response is passed in by reference so that it can be augmented 
    * with the signature.
    * 
    * @param array $Request The request array to be signed
    * @param boolean $Modify Optional whether or not to modify the request in place (default false)
    */
   public function Sign(&$Request, $Modify = FALSE) {
      
      // Fail if no ID is present
      $VanillaID = GetValue('VanillaID', $Request, FALSE);
      if (empty($VanillaID))
         return FALSE;
      
      // We're going to work on a copy for now
      $SignatureArray = $Request;
      
      // Build the request time
      $RequestTime = Gdn_Statistics::Time();
      // Get the secret key
      $RequestSecret = Gdn::InstallationSecret();
      
      // Remove the hash from the request data before checking or building the signature
      unset($SignatureArray['SecurityHash']);
      
      // Add the real secret and request time
      $SignatureArray['Secret'] = $RequestSecret;
      $SignatureArray['RequestTime'] = $RequestTime;
      
      $SignData = array_intersect_key($SignatureArray, array_fill_keys(array(
          'VanillaID',
          'Secret',
          'RequestTime',
          'TimeSlot'
      ), NULL));
      
      // ksort the array to preserve a known order
      $SignData = array_change_key_case($SignData, CASE_LOWER);
      ksort($SignData);
      
      // Calculate the hash
      $RealHash = sha1(http_build_query($SignData));
      
      if ($Modify) {
         $Request['RequestTime'] = $RequestTime;
         $Request['SecurityHash'] = $RealHash;
         ksort($Request);
      }
      
      return $RealHash;
   }
   
   protected function Stats() {
      $Request = array();
      $this->BasicParameters($Request);
      
      $VanillaID = Gdn::InstallationID();
      $VanillaSecret = Gdn::InstallationSecret();
      // Don't try to send stats if we don't have a proper install
      if (is_null($VanillaID) || is_null($VanillaSecret))
         return;
      
      // Always look at stats for the day following the previous successful send.
      $LastSentDate = self::LastSentDate();
      if ($LastSentDate === FALSE) // Never sent
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
         self::LastSentDate($TimeSlot);
         return;
      }
      
      // Assemble Stats
      $Request = array_merge($Request, array(
         'VanillaID'          => $VanillaID,
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
   
   public static function Throttled($SetThrottled = NULL) {
      static $Throttled = NULL;
      
      // Set
      if (!is_null($SetThrottled)) {
         if ($SetThrottled) {
            $ThrottleDelay = C('Garden.Analytics.ThrottleDelay', 3600);
            $ThrottleValue = time() + $ThrottleDelay;
         } else {
            $ThrottleValue = NULL;
         }
         $Throttled = (!is_null($ThrottleValue)) ? $ThrottleValue : 0;
         Gdn::Set('Garden.Analytics.Throttle', $ThrottleValue);
      }
      
      // Lazy Load
      if ($Throttled === NULL)
         $Throttled = Gdn::Get('Garden.Analytics.Throttle', 0);
      
      return ($Throttled > time());
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
      
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         if (Gdn::Get('Garden.Analytics.Notify', FALSE) !== FALSE) {
            $CallMessage = '<span class="InformSprite Bandaid"></span> ';
            $CallMessage .= sprintf(T("There's a problem with Vanilla Analytics that needs your attention.<br/> Handle it <a href=\"%s\">here &raquo;</a>"),Url('dashboard/statistics'));
            Gdn::Controller()->InformMessage($CallMessage,array('CssClass' => 'HasSprite'));
         }
      }
      
      // If the config file is not writable, gtfo
      $ConfFile = PATH_LOCAL_CONF.DS.'config.php';
      if (!is_writable($ConfFile))
         return;
      
      $InstallationID = Gdn::InstallationID();
      
      // Check if we're registered with the central server already. If not, this request is 
      // hijacked and used to perform that task instead of sending stats or recording a tick.
      if (is_null($InstallationID)) {
         $AttemptedRegistration = Gdn::Get('Garden.Analytics.Registering', FALSE);
         // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
         if ($AttemptedRegistration !== FALSE && (time() - $AttemptedRegistration) < 60) return;
      
         return $this->Register();
      }
      
      // Add a pageview entry.
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
         if (Gdn::Get('Garden.Analytics.AutoStructure', FALSE)) {
            SaveToConfig('Garden.Analytics.Enabled', FALSE);
            Gdn::Set('Garden.Analytics.AutoStructure', NULL);
            return;
         }
         
         // If we get here, insert failed. Try proxyconnect to the utility structure
         Gdn::Set('Garden.Analytics.AutoStructure', TRUE);
         ProxyRequest(Url('utility/update', TRUE), 0, FALSE);
      }
      
      // If we get here and this is true, we successfully ran the auto structure. Remove config flag.
      if (Gdn::Get('Garden.Analytics.AutoStructure', FALSE))
         Gdn::Set('Garden.Analytics.AutoStructure', NULL);
      
      // Fire an event for plugins to track their own stats.
      // TODO: Make this analyze the path and throw a specific event (this event will change in future versions).
      $this->EventArguments['Path'] = Gdn::Request()->Post('Path');
      $this->FireEvent('Tick');

      // If we get here, the installation is registered and we can decide on whether or not to send stats now.
      $LastSentDate = self::LastSentDate();
      if (empty($LastSentDate) || $LastSentDate < date('Ymd', strtotime('-1 day')))
         return $this->Stats();
   }
   
   public static function Time() {
      return time();
   }
   
   public static function TimeSlot($SlotType = 'd', $Timestamp = FALSE) {
      if (!$Timestamp)
         $Timestamp = self::Time();
      if ($SlotType == 'd')
         $Result = gmdate('Ymd', $Timestamp);
      elseif ($SlotType == 'w') {
         $Sub = gmdate('N', $Timestamp) - 1;
         $Timestamp = strtotime("-$Sub days", $Timestamp);
         $Result = gmdate('Ymd', $Timestamp);
      } elseif ($SlotType == 'm')
         $Result = gmdate('Ym', $Timestamp).'00';
      elseif ($SlotType == 'y')
         $Result = gmdate('Y', $Timestamp).'0000';
      
      return $Result;
   }
   
   public static function TimeSlotStamp($SlotType = 'd', $Timestamp = FALSE) {
      $Result = self::TimeFromTimeSlot(self::TimeSlot('d', $Timestamp));
      return $Result;
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
   
   public function ValidateCredentials() {
      $Request = array();
      $this->BasicParameters($Request);
      
      $VanillaID = Gdn::InstallationID();
      $VanillaSecret = Gdn::InstallationSecret();
      // Don't try to send stats if we don't have a proper install
      if (is_null($VanillaID) || is_null($VanillaSecret))
         return FALSE;
      
      $Request = array_merge($Request, array(
         'VanillaID'          => $VanillaID
      ));
      
      $Response = $this->Analytics('Verify', $Request, FALSE);
      $Status = GetValue('Status', $Response, 404);
      
      if ($Status == 200) return TRUE;
      return FALSE;
   }
   
   /**
    * Signature check
    * 
    * This method checks the supplied signature of a request against a hash of
    * the request arguments augmented with the local secret from the config file.
    * 
    *****
    * THIS METHOD USES ALL SUPPLIED ARGUMENTS IN ITS SIGNATURE HASH ALGORITHM
    *****
    * 
    * @param type $Request Array of request parameters
    * @return boolean Status of verification check, or null if no VanillaID
    */
   protected function VerifySignature($Request) {
      
      // If this response has no ID, return NULL (could not verify)
      $VanillaID = GetValue('VanillaID', $Request, NULL);
      if (is_null($VanillaID))
         return NULL;
      
      // Response is bogus - wrong InstallationID
      if (!is_null(Gdn::InstallationID()) && $VanillaID != Gdn::InstallationID())
         return FALSE;
      
      // If we don't have a secret, we cannot verify anyway
      $VanillaSecret = Gdn::InstallationSecret();
      if (is_null($VanillaSecret))
         return NULL;
      
      // Calculate clock desync
      $CurrentGmTime = Gdn_Statistics::Time();
      $RequestTime = GetValue('RequestTime', $Request, 0);
      $TimeDiff = abs($CurrentGmTime - $RequestTime);
      $AllowedTimeDiff = C('Garden.Analytics.RequestTimeout', 1440);
      
      // Allow 24* minutes of clock desync, otherwise signature is invalid
      if ($TimeDiff > $AllowedTimeDiff)
         return FALSE;
      
      $SecurityHash = GetValue('SecurityHash', $Request);
      
      // Remove the existing SecuritHash before calculating the signature
      unset($Request['SecurityHash']);
      // Add the real secret
      $Request['Secret'] = $VanillaSecret;
      
      $SignData = array_intersect_key($Request, array_fill_keys(array(
          'VanillaID',
          'Secret',
          'RequestTime',
          'TimeSlot'
      ), NULL));
      
      // ksort the array to preserve a known order
      $SignData = array_change_key_case($SignData, CASE_LOWER);
      ksort($SignData);
      
      // Calculate the hash
      $RealHash = sha1(http_build_query($SignData));
      
      if ($RealHash == $SecurityHash)
         return TRUE;
      
      return FALSE;
   }
   
}