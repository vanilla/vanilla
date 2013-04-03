<?php if (!defined('APPLICATION')) exit();

/**
 * Analytics system
 * 
 * Handles install-side analytics gathering and sending.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0.17
 */
class Gdn_Statistics extends Gdn_Plugin {

   protected $AnalyticsServer;
   public static $Increments = array('h' => 'hours', 'd' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years');
   
   protected $TickExtra;

   public function __construct() {
      parent::__construct();
      
      $AnalyticsServer = C('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
      $AnalyticsServer = str_replace(array('http://', 'https://'), '', $AnalyticsServer);
      $this->AnalyticsServer = $AnalyticsServer;
      
      $this->TickExtra = array();
   }

   public function Analytics($Method, $RequestParameters, $Callback = FALSE, $ParseResponse = TRUE) {
      $FullMethod = explode('/',$Method);
      if (sizeof($FullMethod) < 2)
         array_unshift($FullMethod, "analytics");
      
      list($ApiController, $ApiMethod) = $FullMethod;
      $ApiController = strtolower($ApiController);
      $ApiMethod = StringEndsWith(strtolower($ApiMethod), '.json', TRUE, TRUE).'.json';
      
      $FinalURL = 'http://'.CombinePaths(array(
         $this->AnalyticsServer,
         $ApiController,
         $ApiMethod
      ));
      
      // Allow hooking of analytics events
      $this->EventArguments['AnalyticsMethod'] = &$Method;
      $this->EventArguments['AnalyticsArgs'] = &$RequestParameters;
      $this->EventArguments['AnalyticsUrl'] = &$FinalURL;
      $this->FireEvent('SendAnalytics');
      
      // Sign request
      $this->Sign($RequestParameters, TRUE);
      $RequestMethod = GetValue('RequestMethod', $RequestParameters, 'GET');
      unset($RequestParameters['RequestMethod']);
      
      try {
         $ProxyRequest = new ProxyRequest(FALSE, array(
            'Method'    => $RequestMethod,
            'Timeout'   => 10,
            'Cookies'   => FALSE
         ));
         $Response = $ProxyRequest->Request(array(
            'Url'       => $FinalURL
         ), $RequestParameters);
      } catch (Exception $e) {
         $Response = FALSE;
      }
      
      if ($Response !== FALSE) {
         $JsonResponse = json_decode($Response, TRUE);         
         
         if ($JsonResponse !== FALSE) {
            if ($ParseResponse) {
               $AnalyticsJsonResponse = (array)GetValue('Analytics', $JsonResponse, FALSE);
               // If we received a reply, parse it
               if ($AnalyticsJsonResponse !== FALSE) {
                  $this->ParseAnalyticsResponse($AnalyticsJsonResponse, $Response, $Callback);
                  return $AnalyticsJsonResponse;
               }
            } else {
               return $JsonResponse;
            }
         }
         
         return $Response;
      }
      
      return FALSE;
   }
   
   public function Api($Method, $Parameters) {
      $ApiResponse = $this->Analytics($Method, $Parameters, FALSE, FALSE);
      return $ApiResponse;
   }
   
   protected function AnalyticsFailed($JsonResponse) {
      self::Throttled(TRUE);

      $Reason = GetValue('Reason', $JsonResponse, NULL);
      if (!is_null($Reason))
         Gdn::Controller()->InformMessage("Analytics: {$Reason}");
   }

   public function Base_Render_Before($Sender) {
      // If this is a full page request, trigger stats environment check
      if ($Sender->DeliveryType() == DELIVERY_TYPE_ALL) {
         $this->Check();
      }
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
         'ServerHostname' => Url('/', TRUE),
         'ServerType' => Gdn::Request()->GetValue('SERVER_SOFTWARE'),
         'PHPVersion' => phpversion(),
         'VanillaVersion' => APPLICATION_VERSION
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
      // If we're hitting an exception app, short circuit here
      if (!self::CheckIsAllowed()) {
         return;
      }
      Gdn::Controller()->AddDefinition('AnalyticsTask', 'tick');
      
      if (self::CheckIsEnabled()) {
         // At this point there is nothing preventing stats from working, so queue a tick.
         Gdn::Controller()->AddDefinition('TickExtra', $this->GetEncodedTickExtra());
      }
   }
   
   public function AddExtra($Name, $Value) {
      $this->TickExtra[$Name] = $Value;
   }
   
   public function GetEncodedTickExtra() {
      if (!sizeof($this->TickExtra))
         return NULL;
      
      return @json_encode($this->TickExtra);
   }

   public static function CheckIsAllowed() {
      // These applications are not included in statistics
      $ExceptionApplications = array('dashboard');

      // ... unless one of these paths is requested
      $ExceptionPaths = array('profile*', 'activity*');

      $Path = Gdn::Request()->Path();
      foreach ($ExceptionPaths as $ExceptionPath)
         if (fnmatch($ExceptionPath, $Path))
            return TRUE;

      $ApplicationFolder = Gdn::Controller()->ApplicationFolder;
      if (in_array($ApplicationFolder, $ExceptionApplications))
         return FALSE;
      
      // If we've recently received an error response, wait until the throttle expires
      if (self::Throttled()) {
         return FALSE;
      }

      return TRUE;
   }

   public static function CheckIsLocalhost() {
      $ServerAddress = Gdn::Request()->IpAddress();
      $ServerHostname = Gdn::Request()->GetValue('SERVER_NAME');

      // IPv6 Localhost
      if ($ServerAddress == '::1')
         return TRUE;

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
      if ($ServerHostname == 'localhost' || substr($ServerHostname, -6) == '.local')
         return TRUE;

      // If we get here, we're likely public
      return FALSE;
   }

   public static function CheckIsEnabled() {
      // Forums that are busy installing should not yet be tracked
      if (!C('Garden.Installed', FALSE))
         return FALSE;

      // Enabled if not explicitly disabled via config
      if (!C('Garden.Analytics.Enabled', TRUE))
         return FALSE;

      // Don't track things for local sites (unless overridden in config)
      if (self::CheckIsLocalhost() && !C('Garden.Analytics.AllowLocal', FALSE))
         return 0;

      return TRUE;
   }

   // credit: claudiu(at)cnixs.com via php.net/manual/en/ref.network.php
   public static function CIDRCheck($IP, $CIDR) {
      list ($net, $mask) = explode("/", $CIDR);

      // Allow non-standard /0 syntax
      if ($mask == 0) {
         if (ip2long($IP) == ip2long($net))
            return true;
         else
            return false;
      }

      $ip_net = ip2long($net);
      $ip_mask = ~((1 << (32 - $mask)) - 1);

      $ip_ip = ip2long($IP);

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

   public static function FirstDate() {
      $FirstDate = Gdn::SQL()
                      ->Select('DateInserted', 'min')
                      ->From('User')
                      ->Where('DateInserted >', '0000-00-00')
                      ->Get()->Value('DateInserted');
      return $FirstDate;
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
             'Success' => $Callbacks
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
         'Success' => 'DoneRegister',
         'Failure' => 'AnalyticsFailed'
      ));
   }

   /**
    * 
    * @param Gdn_Controller $Sender
    */
   public function SettingsController_AnalyticsTick_Create($Sender) {
      $Sender->DeliveryMethod(DELIVERY_METHOD_JSON);
      $Sender->DeliveryType(DELIVERY_TYPE_DATA);
      
      Gdn::Statistics()->Tick();
      $this->FireEvent("AnalyticsTick");
      $Sender->DeliveryType(DELIVERY_TYPE_VIEW);
      $Sender->Render('tick', 'statistics', 'dashboard');
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
      
      if ($VanillaID != Gdn::InstallationID()) return FALSE;

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
      if ($StatsTimeSlot >= date('Ymd'))
         return;

      $DetectActiveInterval = 0;
      $MaxIterations = 10;
      $TimeSlotLimit = date('Ymd');
      do {

         $TimeSlot = date('Ymd', $StatsDate);

         // We're caught up to today. Stop looping.
         if ($TimeSlot >= $TimeSlotLimit)
            break;

         $DayStart = date('Y-m-d 00:00:00', $StatsDate);
         $DayEnd = date('Y-m-d 23:59:59', $StatsDate);

         // Get relevant stats
         $NumComments = Gdn::SQL()
                         ->Select('DateInserted', 'COUNT', 'Hits')
                         ->From('Comment')
                         ->Where('DateInserted>=', $DayStart)
                         ->Where('DateInserted<', $DayEnd)
                         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumComments = GetValue('Hits', $NumComments, NULL);

         $NumDiscussions = Gdn::SQL()
                         ->Select('DateInserted', 'COUNT', 'Hits')
                         ->From('Discussion')
                         ->Where('DateInserted>=', $DayStart)
                         ->Where('DateInserted<', $DayEnd)
                         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumDiscussions = GetValue('Hits', $NumDiscussions, NULL);

         $NumUsers = Gdn::SQL()
                         ->Select('DateInserted', 'COUNT', 'Hits')
                         ->From('User')
                         ->Where('DateInserted>=', $DayStart)
                         ->Where('DateInserted<', $DayEnd)
                         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         $NumUsers = GetValue('Hits', $NumUsers, NULL);

         $NumViewsData = Gdn::SQL()
                         ->Select('Views, EmbedViews')
                         ->From('AnalyticsLocal')
                         ->Where('TimeSlot', $TimeSlot)
                         ->Get()->FirstRow(DATASET_TYPE_ARRAY);
         
         $NumViews = GetValue('Views', $NumViewsData, NULL);
         $NumEmbedViews = GetValue('EmbedViews', $NumViewsData, NULL);

         $DetectActiveInterval = array_sum(array(
            $NumComments,
            $NumDiscussions,
            $NumUsers,
            $NumViews,
            $NumEmbedViews
         ));

         $StatsDate = strtotime('+1 day', $StatsDate);
         $MaxIterations--;
      } while ($DetectActiveInterval == 0 && $MaxIterations);

      if ($DetectActiveInterval == 0) {
         // We've looped $MaxIterations times or up until yesterday and couldn't find any stats. Remember our place and return.
         self::LastSentDate($TimeSlot);
         return;
      }

      // Assemble Stats
      $Request = array_merge($Request, array(
         'VanillaID' => $VanillaID,
         'TimeSlot' => $TimeSlot,
         'CountComments' => $NumComments,
         'CountDiscussions' => $NumDiscussions,
         'CountUsers' => $NumUsers,
         'CountViews' => $NumViews,
         'CountEmbedViews' => $NumEmbedViews
      ));

      // Send stats to remote server
      $this->Analytics('Stats', $Request, array(
         'Success' => 'DoneStats',
         'Failure' => 'AnalyticsFailed'
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
      // Fire an event for plugins to track their own stats.
      // TODO: Make this analyze the path and throw a specific event (this event will change in future versions).
      $this->EventArguments['Path'] = Gdn::Request()->Post('Path');
      $this->FireEvent('Tick');
      
      // Store the view, using denormalization if enabled
      $ViewType = 'normal';
      if (preg_match('`discussion/embed`', Gdn::Request()->Post('ResolvedPath', '')))
         $ViewType = 'embed';
      
      $this->AddView($ViewType);
      
      if (!self::CheckIsEnabled())
         return;
      
      if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
         if (Gdn::Get('Garden.Analytics.Notify', FALSE) !== FALSE) {
            $CallMessage = Sprite('Bandaid', 'InformSprite');
            $CallMessage .= sprintf(T("There's a problem with Vanilla Analytics that needs your attention.<br/> Handle it <a href=\"%s\">here &raquo;</a>"), Url('dashboard/statistics'));
            Gdn::Controller()->InformMessage($CallMessage, array('CssClass' => 'HasSprite'));
         }
      }
      
      $InstallationID = Gdn::InstallationID();
      
      // Check if we're registered with the central server already. If not, this request is 
      // hijacked and used to perform that task instead of sending stats or recording a tick.
      if (is_null($InstallationID)) {
         // If the config file is not writable, gtfo
         $ConfFile = PATH_CONF . '/config.php';
         if (!is_writable($ConfFile)) {
            // Admins see a helpful notice
            if (Gdn::Session()->CheckPermission('Garden.Settings.Manage')) {
               $Warning = Sprite('Sliders', 'InformSprite');
               $Warning .= T('Your config.php file is not writable.<br/> Find out <a href="http://vanillaforums.org/docs/vanillastatistics">how to fix this &raquo;</a>');
               Gdn::Controller()->InformMessage($Warning, array('CssClass' => 'HasSprite'));
            }
            return;
         }
         
         $AttemptedRegistration = Gdn::Get('Garden.Analytics.Registering', FALSE);
         // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
         if ($AttemptedRegistration !== FALSE && (time() - $AttemptedRegistration) < 60)
            return;

         return $this->Register();
      }
      
      // If we get here, the installation is registered and we can decide on whether or not to send stats now.
      $LastSentDate = self::LastSentDate();
      if (empty($LastSentDate) || $LastSentDate < date('Ymd', strtotime('-1 day')))
         return $this->Stats();
   }

   /**
    * Increments overall pageview view count
    *
    * @since 2.1a
    * @access public
    */
   public function AddView($ViewType = 'normal') {
      // Add a pageview entry.
      $TimeSlot = date('Ymd');
      $Px = Gdn::Database()->DatabasePrefix;
      
      $Views = 1;
      $EmbedViews = 0;
      
      try {
         if (C('Garden.Analytics.Views.Denormalize', FALSE) && Gdn::Cache()->ActiveEnabled()) {
            $CacheKey = "QueryCache.Analytics.CountViews";
            
            // Increment. If not success, create key.
            $Incremented = Gdn::Cache()->Increment($CacheKey);
            if ($Incremented === Gdn_Cache::CACHEOP_FAILURE)
               Gdn::Cache()->Store($CacheKey, 1);
            
            // Get current cache value
            $Views = Gdn::Cache()->Get($CacheKey);
            
            if ($ViewType == 'embed') {
               $EmbedCacheKey = "QueryCache.Analytics.CountEmbedViews";

               // Increment. If not success, create key.
               $EmbedIncremented = Gdn::Cache()->Increment($EmbedCacheKey);
               if ($EmbedIncremented === Gdn_Cache::CACHEOP_FAILURE)
                  Gdn::Cache()->Store($EmbedCacheKey, 1);

               // Get current cache value
               $EmbedViews = Gdn::Cache()->Get($EmbedCacheKey);
            }
            
            // Every X views, writeback to AnalyticsLocal
            $DenormalizeWriteback = C('Garden.Analytics.Views.DenormalizeWriteback', 10);
            if (($Views % $DenormalizeWriteback) == 0) {
               Gdn::Controller()->SetData('WritebackViews', $Views);
               Gdn::Controller()->SetData('WritebackEmbed', $EmbedViews);
                  
               Gdn::Database()->Query("insert into {$Px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$Views}, {$EmbedViews})
               on duplicate key update 
                  Views = COALESCE(Views, 0)+{$Views}, 
                  EmbedViews = COALESCE(EmbedViews, 0)+{$EmbedViews}", 
               array(
                  ':TimeSlot' => $TimeSlot
               ));
               
               // ... and get rid of those views from the keys
               
               if ($Views)
                  Gdn::Cache()->Decrement($CacheKey, $Views);
               
               if ($EmbedViews)
                  Gdn::Cache()->Decrement($EmbedCacheKey, $EmbedViews);
            }
         } else {
            $ExtraViews = 1;
            $ExtraEmbedViews = ($ViewType == 'embed') ? 1 : 0;
            
            Gdn::Database()->Query("insert into {$Px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$ExtraViews}, {$ExtraEmbedViews})
               on duplicate key update 
                  Views = COALESCE(Views, 0)+{$ExtraViews}, 
                  EmbedViews = COALESCE(EmbedViews, 0)+{$ExtraEmbedViews}", 
            array(
               ':TimeSlot' => $TimeSlot
            ));
         }
      } catch (Exception $Ex) {
         if (Gdn::Session()->CheckPermission('Garden.Settings.Manage'))
            throw $Ex;
      }
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
         $Result = gmdate('Ym', $Timestamp) . '00';
      elseif ($SlotType == 'y')
         $Result = gmdate('Y', $Timestamp) . '0000';
      elseif ($SlotType == 'a')
         $Result = '00000000';

      return $Result;
   }

   public static function TimeSlotAdd($SlotType, $Number, $Timestamp = FALSE) {
      $Timestamp = self::TimeSlotStamp($SlotType, $Timestamp);
      $Result = strtotime(sprintf('%+d %s', $Number, self::$Increments[$SlotType]), $Timestamp);
      return $Result;
   }

   public static function TimeSlotBounds($SlotType = 'd', $Timestamp = FALSE) {
      $From = self::TimeSlotStamp($SlotType, $Timestamp);
      $To = strtotime('+1 ' . self::$Increments[$SlotType], $From);
      return array($From, $To);
   }

   public static function TimeSlotStamp($SlotType = 'd', $Timestamp = FALSE) {
      $Result = self::TimeFromTimeSlot(self::TimeSlot($SlotType, $Timestamp));
      return $Result;
   }

   public static function TimeFromTimeSlot($TimeSlot) {
      if ($TimeSlot == '00000000')
         return 0;

      $Year = substr($TimeSlot, 0, 4);
      $Month = substr($TimeSlot, 4, 2);
      $Day = (int) substr($TimeSlot, 6, 2);
      if ($Day == 0)
         $Day = 1;
      $DateRaw = mktime(0, 0, 0, $Month, $Day, $Year);

      if ($DateRaw === FALSE)
         throw new Exception("Invalid timeslot '{$TimeSlot}', unable to convert to epoch");

      return $DateRaw;
   }

   public static function TimeFromExtendedTimeSlot($TimeSlot, $Resolution = 'auto') {
      if ($TimeSlot == '00000000')
         return 0;

      list($Year, $Month, $Day, $Hour, $Minute) = array(1, 1, 1, 0, 0);
      if ($Resolution == 'auto')
         $TimeslotLength = strlen($TimeSlot);
      else
         $TimeslotLength = $Resolution;
      
      if ($TimeslotLength >= 4)
         $Year = substr($TimeSlot, 0, 4);
      
      if ($TimeslotLength >= 6)
         $Month = substr($TimeSlot, 4, 2);
      
      if ($TimeslotLength >= 8)
         $Day = (int) substr($TimeSlot, 6, 2);
      if ($Day == 0) $Day = 1;
      
      if ($TimeslotLength >= 10)
         $Hour = (int) substr($TimeSlot, 8, 2);
      
      if ($TimeslotLength >= 12)
         $Minute = (int) substr($TimeSlot, 10, 2);
      
      $DateRaw = mktime($Hour, $Minute, 0, $Month, $Day, $Year);

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
         'VanillaID' => $VanillaID
      ));

      $Response = $this->Analytics('Verify', $Request, FALSE);
      $Status = GetValue('Status', $Response, 404);

      if ($Status == 200)
         return TRUE;
      return FALSE;
   }

   /**
    * Signature check
    * 
    * This method checks the supplied signature of a request against a hash of
    * the request arguments augmented with the local secret from the config file.
    * 
    * ****
    * THIS METHOD USES ALL SUPPLIED ARGUMENTS IN ITS SIGNATURE HASH ALGORITHM
    * ****
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