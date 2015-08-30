<?php
/**
 * Analytics system.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.17
 */

/**
 * Handles install-side analytics gathering and sending.
 */
class Gdn_Statistics extends Gdn_Plugin {

    /** @var mixed  */
    protected $AnalyticsServer;

    /** @var array  */
    public static $Increments = array('h' => 'hours', 'd' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years');

    /** @var array  */
    protected $TickExtra;

    /**
     *
     */
    public function __construct() {
        parent::__construct();

        $AnalyticsServer = C('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
        $AnalyticsServer = str_replace(array('http://', 'https://'), '', $AnalyticsServer);
        $this->AnalyticsServer = $AnalyticsServer;

        $this->TickExtra = array();
    }

    /**
     *
     *
     * @param $Method
     * @param $RequestParameters
     * @param bool $Callback
     * @param bool $ParseResponse
     * @return array|bool|mixed|type
     * @throws Exception
     */
    public function analytics($Method, $RequestParameters, $Callback = false, $ParseResponse = true) {
        $FullMethod = explode('/', $Method);
        if (sizeof($FullMethod) < 2) {
            array_unshift($FullMethod, "analytics");
        }

        list($ApiController, $ApiMethod) = $FullMethod;
        $ApiController = strtolower($ApiController);
        $ApiMethod = stringEndsWith(strtolower($ApiMethod), '.json', true, true).'.json';

        $FinalURL = 'http://'.combinePaths(array(
                $this->AnalyticsServer,
                $ApiController,
                $ApiMethod
            ));

        $RequestHeaders = array();

        // Allow hooking of analytics events
        $this->EventArguments['AnalyticsMethod'] = &$Method;
        $this->EventArguments['AnalyticsArgs'] = &$RequestParameters;
        $this->EventArguments['AnalyticsUrl'] = &$FinalURL;
        $this->EventArguments['AnalyticsHeaders'] = &$RequestHeaders;
        $this->fireEvent('SendAnalytics');

        // Sign request
        $this->sign($RequestParameters, true);
        $RequestMethod = val('RequestMethod', $RequestParameters, 'GET');
        unset($RequestParameters['RequestMethod']);

        try {
            $ProxyRequest = new ProxyRequest(false, array(
                'Method' => $RequestMethod,
                'Timeout' => 10,
                'Cookies' => false
            ));
            $Response = $ProxyRequest->request(array(
                'Url' => $FinalURL,
                'Log' => false
            ), $RequestParameters, null, $RequestHeaders);
        } catch (Exception $e) {
            $Response = false;
        }

        if ($Response !== false) {
            $JsonResponse = json_decode($Response, true);

            if ($JsonResponse !== false) {
                if ($ParseResponse) {
                    $AnalyticsJsonResponse = (array)val('Analytics', $JsonResponse, false);
                    // If we received a reply, parse it
                    if ($AnalyticsJsonResponse !== false) {
                        $this->parseAnalyticsResponse($AnalyticsJsonResponse, $Response, $Callback);
                        return $AnalyticsJsonResponse;
                    }
                } else {
                    return $JsonResponse;
                }
            }

            return $Response;
        }

        return false;
    }

    /**
     *
     *
     * @param $Method
     * @param $Parameters
     * @return array|bool|mixed|type
     */
    public function api($Method, $Parameters) {
        $ApiResponse = $this->analytics($Method, $Parameters, false, false);
        return $ApiResponse;
    }

    /**
     *
     *
     * @param $JsonResponse
     */
    protected function analyticsFailed($JsonResponse) {
        self::throttled(true);

        $Reason = val('Reason', $JsonResponse, null);
        if (!is_null($Reason)) {
            Gdn::controller()->informMessage("Analytics: {$Reason}");
        }
    }

    /**
     *
     *
     * @param $Sender
     */
    public function base_render_before($Sender) {
        // If this is a full page request, trigger stats environment check
        if ($Sender->deliveryType() == DELIVERY_TYPE_ALL) {
            $this->check();
        }
    }

    /**
     * Automatically configures a ProxyRequest array with basic parameters
     * such as IP, VanillaVersion, RequestTime, Hostname, PHPVersion, ServerType.
     *
     * @param array $Request Reference to the existing request array
     * @return void
     */
    public function basicParameters(&$Request) {
        $Request = array_merge($Request, array(
            'ServerHostname' => Url('/', true),
            'ServerType' => Gdn::request()->getValue('SERVER_SOFTWARE'),
            'PHPVersion' => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
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
    public function check() {
        // If we're hitting an exception app, short circuit here
        if (!self::checkIsEnabled()) {
            return;
        }
        Gdn::controller()->addDefinition('AnalyticsTask', 'tick');

        if (self::checkIsAllowed()) {
            // At this point there is nothing preventing stats from working, so queue a tick.
            Gdn::controller()->addDefinition('TickExtra', $this->getEncodedTickExtra());
        }
    }

    /**
     *
     *
     * @param $Name
     * @param $Value
     */
    public function addExtra($Name, $Value) {
        $this->TickExtra[$Name] = $Value;
    }

    /**
     *
     *
     * @return null|string
     */
    public function getEncodedTickExtra() {
        if (!sizeof($this->TickExtra)) {
            return null;
        }

        return @json_encode($this->TickExtra);
    }

    /**
     *
     *
     * @return bool
     */
    public static function checkIsAllowed() {
        // These applications are not included in statistics
        $ExceptionApplications = array('dashboard');

        // ... unless one of these paths is requested
        $ExceptionPaths = array('profile*', 'activity*');

        $Path = Gdn::request()->path();
        foreach ($ExceptionPaths as $ExceptionPath) {
            if (fnmatch($ExceptionPath, $Path)) {
                return true;
            }
        }

        $ApplicationFolder = Gdn::controller()->ApplicationFolder;
        if (in_array($ApplicationFolder, $ExceptionApplications)) {
            return false;
        }

        // If we've recently received an error response, wait until the throttle expires
        if (self::throttled()) {
            return false;
        }

        return true;
    }

    /**
     *
     *
     * @return bool
     */
    public static function checkIsLocalhost() {
        $ServerAddress = Gdn::request()->ipAddress();
        $ServerHostname = Gdn::request()->getValue('SERVER_NAME');

        // IPv6 Localhost
        if ($ServerAddress == '::1') {
            return true;
        }

        // Private subnets
        foreach (array(
                     '127.0.0.1/0',
                     '10.0.0.0/8',
                     '172.16.0.0/12',
                     '192.168.0.0/16') as $LocalCIDR) {
            if (self::cidrCheck($ServerAddress, $LocalCIDR)) {
                return true;
            }
        }

        // Comment local hostnames / hostname suffixes
        if ($ServerHostname == 'localhost' || substr($ServerHostname, -6) == '.local') {
            return true;
        }

        // If we get here, we're likely public
        return false;
    }

    /**
     *
     *
     * @return bool|int
     */
    public static function checkIsEnabled() {
        // Forums that are busy installing should not yet be tracked
        if (!C('Garden.Installed', false)) {
            return false;
        }

        // Enabled if not explicitly disabled via config
        if (!C('Garden.Analytics.Enabled', true)) {
            return false;
        }

        // Don't track things for local sites (unless overridden in config)
        if (self::checkIsLocalhost() && !C('Garden.Analytics.AllowLocal', false)) {
            return 0;
        }

        return true;
    }

    /**
     *
     *
     * credit: claudiu(at)cnixs.com via php.net/manual/en/ref.network.php
     *
     * @param $IP
     * @param $CIDR
     * @return bool
     */
    public static function cidrCheck($IP, $CIDR) {
        list ($net, $mask) = explode("/", $CIDR);

        // Allow non-standard /0 syntax
        if ($mask == 0) {
            if (ip2long($IP) == ip2long($net)) {
                return true;
            } else {
                return false;
            }
        }

        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);

        $ip_ip = ip2long($IP);

        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    /**
     *
     *
     * @param $Response
     * @param $Raw
     */
    protected function doneRegister($Response, $Raw) {
        $VanillaID = val('VanillaID', $Response, false);
        $Secret = val('Secret', $Response, false);
        if (($Secret && $VanillaID) !== false) {
            Gdn::InstallationID($VanillaID);
            Gdn::InstallationSecret($Secret);
            Gdn::Set('Garden.Analytics.Registering', null);
            Gdn::Set('Garden.Analytics.LastSentDate', null);
        }
    }

    /**
     *
     *
     * @param $Response
     * @param $Raw
     */
    protected function doneStats($Response, $Raw) {
        $SuccessTimeSlot = val('TimeSlot', $Response, false);
        if ($SuccessTimeSlot !== false) {
            self::lastSentDate($SuccessTimeSlot);
        }
    }

    /**
     *
     *
     * @return mixed
     */
    public static function firstDate() {
        $FirstDate = Gdn::sql()
            ->select('DateInserted', 'min')
            ->from('User')
            ->where('DateInserted >', '0000-00-00')
            ->get()->value('DateInserted');
        return $FirstDate;
    }

    /**
     *
     *
     * @param null $SetLastSentDate
     * @return mixed|null
     */
    public static function lastSentDate($SetLastSentDate = null) {
        static $LastSentDate = null;

        // Set
        if (!is_null($SetLastSentDate)) {
            $LastSentDate = $SetLastSentDate;
            Gdn::set('Garden.Analytics.LastSentDate', $LastSentDate);
        }

        // Lazy Load
        if ($LastSentDate === null) {
            $LastSentDate = Gdn::get('Garden.Analytics.LastSentDate', false);
        }

        return $LastSentDate;
    }

    /**
     *
     *
     * @param $JsonResponse
     * @param $RawResponse
     * @param $Callbacks
     */
    protected function parseAnalyticsResponse($JsonResponse, $RawResponse, $Callbacks) {
        // Verify signature of reply
        $Verified = $this->verifySignature($JsonResponse);
        if ($Verified === false) {
            return;
        }

        // Only allow commands when verification was explicitly successful
        if ($Verified === true) {
            // Perform response commands
            foreach ($JsonResponse as $CommandName => $CommandValue) {
                switch ($CommandName) {
                    case 'DoDeregister':
                        if ($Verified) {
                            // De-register yourself
                            Gdn::installationID(false);
                            Gdn::installationSecret(false);
                        }
                        break;

                    case 'DoDisable':
                        if ($Verified) {
                            // Turn yourself off
                            SaveToConfig('Garden.Analytics.Enabled', false);
                        }
                        break;

                    case 'DoCall':
                        // Call the admin's attention to the statistics
                        Gdn::set('Garden.Analytics.Notify', $CommandValue);
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

        if (!is_array($Callbacks)) {
            $Callbacks = array();
        }

        // Assume strings are local methods
        foreach ($Callbacks as $Event => &$CallbackMethod) {
            if (is_string($CallbackMethod)) {
                $CallbackMethod = array($this, $CallbackMethod);
            }
        }

        $ResponseCode = val('Status', $JsonResponse, 500);
        $CallbackExecute = null;
        switch ($ResponseCode) {
            case false:
            case 500:
                if (array_key_exists('Failure', $Callbacks)) {
                    $CallbackExecute = $Callbacks['Failure'];
                }
                break;

            case true:
            case 200:
                self::throttled(false);
                if (array_key_exists('Success', $Callbacks)) {
                    $CallbackExecute = $Callbacks['Success'];
                }
                break;
        }

        if (!is_null($CallbackExecute)) {
            call_user_func($CallbackExecute, $JsonResponse, $RawResponse);
        }
    }

    /**
     *
     */
    public function register() {
        // Set the time we last attempted to perform registration
        Gdn::set('Garden.Analytics.Registering', time());

        // Request registration from remote server
        $Request = array();
        $this->basicParameters($Request);
        $this->analytics('Register', $Request, array(
            'Success' => 'DoneRegister',
            'Failure' => 'AnalyticsFailed'
        ));
    }

    /**
     *
     * @param Gdn_Controller $Sender
     */
    public function settingsController_analyticsTick_create($Sender) {
        $Sender->deliveryMethod(DELIVERY_METHOD_JSON);
        $Sender->deliveryType(DELIVERY_TYPE_DATA);

        Gdn::statistics()->tick();
        $this->fireEvent("AnalyticsTick");
        $Sender->render();
    }

    /**
     * Sign a request or response.
     *
     * Uses the known site secret to sign the given request or response. The
     * request/response is passed in by reference so that it can be augmented with the signature.
     *
     * @param array $Request The request array to be signed
     * @param boolean $Modify Optional whether or not to modify the request in place (default false)
     */
    public function sign(&$Request, $Modify = false) {
        // Fail if no ID is present
        $VanillaID = GetValue('VanillaID', $Request, false);
        if (empty($VanillaID)) {
            return false;
        }

        if ($VanillaID != Gdn::installationID()) {
            return false;
        }

        // We're going to work on a copy for now
        $SignatureArray = $Request;

        // Build the request time
        $RequestTime = Gdn_Statistics::time();
        // Get the secret key
        $RequestSecret = Gdn::installationSecret();

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
        ), null));

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

    /**
     *
     *
     * @throws Exception
     */
    protected function stats() {
        $StartTime = time();
        $Request = array();
        $this->basicParameters($Request);

        $VanillaID = Gdn::installationID();
        $VanillaSecret = Gdn::installationSecret();
        // Don't try to send stats if we don't have a proper install
        if (is_null($VanillaID) || is_null($VanillaSecret)) {
            return;
        }

        // Always look at stats for the day following the previous successful send.
        $LastSentDate = self::lastSentDate();
        if ($LastSentDate === false) { // Never sent
            $StatsDate = strtotime('yesterday');
        } else {
            $StatsDate = strtotime('+1 day', self::timeFromTimeSlot($LastSentDate));
        }

        $StatsTimeSlot = date('Ymd', $StatsDate);
        if ($StatsTimeSlot >= date('Ymd')) {
            return;
        }

        $DetectActiveInterval = 0;
        $MaxIterations = 10;
        $TimeSlotLimit = date('Ymd');
        do {
            $TimeSlot = date('Ymd', $StatsDate);

            // We're caught up to today. Stop looping.
            if ($TimeSlot >= $TimeSlotLimit) {
                break;
            }

            $DayStart = date('Y-m-d 00:00:00', $StatsDate);
            $DayEnd = date('Y-m-d 23:59:59', $StatsDate);

            // Get relevant stats
            $NumComments = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('Comment')
                ->where('DateInserted>=', $DayStart)
                ->where('DateInserted<', $DayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $NumComments = val('Hits', $NumComments, null);

            $NumDiscussions = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('Discussion')
                ->where('DateInserted>=', $DayStart)
                ->where('DateInserted<', $DayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $NumDiscussions = val('Hits', $NumDiscussions, null);

            // Count the number of commenters that ONLY commented.
            $NumCommenters = Gdn::sql()
                ->select('distinct c.InsertUserID', 'COUNT', 'Hits')
                ->from('Comment c')
                ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
                ->where('c.InsertUserID<>', 'd.InsertUserID', false, false)
                ->where('c.DateInserted>=', $DayStart)
                ->where('c.DateInserted<', $DayEnd)
                ->get()->value('Hits', null);

            // Count the number of users that have started a discussion.
            $NumDiscussioners = Gdn::sql()
                ->select('distinct InsertUserID', 'COUNT', 'Hits')
                ->from('Discussion d')
                ->where('DateInserted>=', $DayStart)
                ->where('DateInserted<', $DayEnd)
                ->get()->value('Hits', null);
            if ($NumDiscussioners === null && $NumCommenters === null) {
                $NumContributors = null;
            } else {
                $NumContributors = $NumCommenters + $NumDiscussioners;
            }

            $NumUsers = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('User')
                ->where('DateInserted>=', $DayStart)
                ->where('DateInserted<', $DayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $NumUsers = val('Hits', $NumUsers, null);

            $NumViewsData = Gdn::sql()
                ->select('Views, EmbedViews')
                ->from('AnalyticsLocal')
                ->where('TimeSlot', $TimeSlot)
                ->get()->firstRow(DATASET_TYPE_ARRAY);

            $NumViews = val('Views', $NumViewsData, null);
            $NumEmbedViews = val('EmbedViews', $NumViewsData, null);

            $DetectActiveInterval = array_sum(array(
                $NumComments,
                $NumContributors,
                $NumDiscussions,
                $NumUsers,
                $NumViews,
                $NumEmbedViews
            ));

            $StatsDate = strtotime('+1 day', $StatsDate);
            $MaxIterations--;
            $RunningTime = time() - $StartTime;
        } while ($DetectActiveInterval == 0 && $MaxIterations && $RunningTime <= 30);

        if ($DetectActiveInterval == 0) {
            // We've looped $MaxIterations times or up until yesterday and couldn't find any stats. Remember our place and return.
            self::lastSentDate($TimeSlot);
            return;
        }

        // Assemble Stats
        $Request = array_merge($Request, array(
            'VanillaID' => $VanillaID,
            'TimeSlot' => $TimeSlot,
            'CountComments' => $NumComments,
            'CountAllContributors' => $NumContributors,
            'CountDiscussions' => $NumDiscussions,
            'CountUsers' => $NumUsers,
            'CountViews' => $NumViews,
            'CountEmbedViews' => $NumEmbedViews
        ));

        // Send stats to remote server
        $this->analytics('Stats', $Request, array(
            'Success' => 'DoneStats',
            'Failure' => 'AnalyticsFailed'
        ));
    }

    /**
     *
     *
     * @param null $SetThrottled
     * @return bool
     */
    public static function throttled($SetThrottled = null) {
        static $Throttled = null;

        // Set
        if (!is_null($SetThrottled)) {
            if ($SetThrottled) {
                $ThrottleDelay = C('Garden.Analytics.ThrottleDelay', 3600);
                $ThrottleValue = time() + $ThrottleDelay;
            } else {
                $ThrottleValue = null;
            }
            $Throttled = (!is_null($ThrottleValue)) ? $ThrottleValue : 0;
            Gdn::set('Garden.Analytics.Throttle', $ThrottleValue);
        }

        // Lazy Load
        if ($Throttled === null) {
            $Throttled = Gdn::get('Garden.Analytics.Throttle', 0);
        }

        return ($Throttled > time());
    }

    /**
     * This is the asynchronous callback.
     *
     * This method is triggerd on every page request via a callback AJAX request
     * so that it may execute asychronously and reduce lag for users. It tracks
     * views, handles registration for new installations, and sends stats every day as needed.
     *
     * @return void
     */
    public function tick() {
        // Fire an event for plugins to track their own stats.
        // TODO: Make this analyze the path and throw a specific event (this event will change in future versions).
        $this->EventArguments['Path'] = Gdn::Request()->Post('Path');
        $this->fireEvent('Tick');

        // Store the view, using denormalization if enabled
        $ViewType = 'normal';
        if (preg_match('`discussion/embed`', Gdn::request()->post('ResolvedPath', ''))) {
            $ViewType = 'embed';
        }

        $this->addView($ViewType);

        if (Gdn::session()->isValid()) {
            Gdn::userModel()->updateVisit(Gdn::session()->UserID);
        }

        if (!self::checkIsEnabled()) {
            return;
        }

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            if (Gdn::get('Garden.Analytics.Notify', false) !== false) {
                $CallMessage = Sprite('Bandaid', 'InformSprite');
                $CallMessage .= sprintf(T("There's a problem with Vanilla Analytics that needs your attention.<br/> Handle it <a href=\"%s\">here &raquo;</a>"), Url('dashboard/statistics'));
                Gdn::controller()->informMessage($CallMessage, array('CssClass' => 'HasSprite'));
            }
        }

        $InstallationID = Gdn::installationID();

        // Check if we're registered with the central server already. If not, this request is
        // hijacked and used to perform that task instead of sending stats or recording a tick.
        if (is_null($InstallationID)) {
            // If the config file is not writable, gtfo
            $ConfFile = PATH_CONF.'/config.php';
            if (!is_writable($ConfFile)) {
                // Admins see a helpful notice
                if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                    $Warning = sprite('Sliders', 'InformSprite');
                    $Warning .= T('Your config.php file is not writable.<br/> Find out <a href="http://vanillaforums.org/docs/vanillastatistics">how to fix this &raquo;</a>');
                    Gdn::controller()->informMessage($Warning, array('CssClass' => 'HasSprite'));
                }
                return;
            }

            $AttemptedRegistration = Gdn::get('Garden.Analytics.Registering', false);
            // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
            if ($AttemptedRegistration !== false && (time() - $AttemptedRegistration) < 60) {
                return;
            }

            return $this->register();
        }

        // If we get here, the installation is registered and we can decide on whether or not to send stats now.
        $LastSentDate = self::lastSentDate();
        if (empty($LastSentDate) || $LastSentDate < date('Ymd', strtotime('-1 day'))) {
            return $this->stats();
        }
    }

    /**
     * Increments overall pageview view count.
     *
     * @since 2.1a
     * @access public
     */
    public function addView($ViewType = 'normal') {
        // Add a pageview entry.
        $TimeSlot = date('Ymd');
        $Px = Gdn::database()->DatabasePrefix;

        $Views = 1;
        $EmbedViews = 0;

        try {
            if (C('Garden.Analytics.Views.Denormalize', false) &&
                Gdn::cache()->activeEnabled() &&
                Gdn::cache()->type() != Gdn_Cache::CACHE_TYPE_NULL
            ) {
                $CacheKey = "QueryCache.Analytics.CountViews";

                // Increment. If not success, create key.
                $Incremented = Gdn::cache()->increment($CacheKey);
                if ($Incremented === Gdn_Cache::CACHEOP_FAILURE) {
                    Gdn::cache()->store($CacheKey, 1);
                }

                // Get current cache value
                $Views = Gdn::cache()->get($CacheKey);

                if ($ViewType == 'embed') {
                    $EmbedCacheKey = "QueryCache.Analytics.CountEmbedViews";

                    // Increment. If not success, create key.
                    $EmbedIncremented = Gdn::cache()->increment($EmbedCacheKey);
                    if ($EmbedIncremented === Gdn_Cache::CACHEOP_FAILURE) {
                        Gdn::cache()->store($EmbedCacheKey, 1);
                    }

                    // Get current cache value
                    $EmbedViews = Gdn::cache()->get($EmbedCacheKey);
                }

                // Every X views, writeback to AnalyticsLocal
                $DenormalizeWriteback = C('Garden.Analytics.Views.DenormalizeWriteback', 10);
                if (($Views % $DenormalizeWriteback) == 0) {
                    Gdn::controller()->setData('WritebackViews', $Views);
                    Gdn::controller()->setData('WritebackEmbed', $EmbedViews);

                    Gdn::database()->query(
                        "insert into {$Px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$Views}, {$EmbedViews})
               on duplicate key update
                  Views = COALESCE(Views, 0)+{$Views},
                  EmbedViews = COALESCE(EmbedViews, 0)+{$EmbedViews}",
                        array(
                            ':TimeSlot' => $TimeSlot
                        )
                    );

                    // ... and get rid of those views from the keys

                    if ($Views) {
                        Gdn::cache()->decrement($CacheKey, $Views);
                    }

                    if ($EmbedViews) {
                        Gdn::cache()->decrement($EmbedCacheKey, $EmbedViews);
                    }
                }
            } else {
                $ExtraViews = 1;
                $ExtraEmbedViews = ($ViewType == 'embed') ? 1 : 0;

                Gdn::database()->query(
                    "insert into {$Px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$ExtraViews}, {$ExtraEmbedViews})
               on duplicate key update
                  Views = COALESCE(Views, 0)+{$ExtraViews},
                  EmbedViews = COALESCE(EmbedViews, 0)+{$ExtraEmbedViews}",
                    array(
                        ':TimeSlot' => $TimeSlot
                    )
                );
            }
        } catch (Exception $Ex) {
            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                throw $Ex;
            }
        }
    }

    /**
     *
     *
     * @return int
     */
    public static function time() {
        return time();
    }

    /**
     *
     *
     * @param string $SlotType
     * @param bool $Timestamp
     * @return string
     */
    public static function timeSlot($SlotType = 'd', $Timestamp = false) {
        if (!$Timestamp) {
            $Timestamp = self::time();
        }

        if ($SlotType == 'd') {
            $Result = gmdate('Ymd', $Timestamp);
        } elseif ($SlotType == 'w') {
            $Sub = gmdate('N', $Timestamp) - 1;
            $Timestamp = strtotime("-$Sub days", $Timestamp);
            $Result = gmdate('Ymd', $Timestamp);
        } elseif ($SlotType == 'm')
            $Result = gmdate('Ym', $Timestamp).'00';
        elseif ($SlotType == 'y')
            $Result = gmdate('Y', $Timestamp).'0000';
        elseif ($SlotType == 'a')
            $Result = '00000000';

        return $Result;
    }

    /**
     *
     *
     * @param $SlotType
     * @param $Number
     * @param bool $Timestamp
     * @return int
     */
    public static function timeSlotAdd($SlotType, $Number, $Timestamp = false) {
        $Timestamp = self::timeSlotStamp($SlotType, $Timestamp);
        $Result = strtotime(sprintf('%+d %s', $Number, self::$Increments[$SlotType]), $Timestamp);
        return $Result;
    }

    /**
     *
     *
     * @param string $SlotType
     * @param bool $Timestamp
     * @return array
     */
    public static function timeSlotBounds($SlotType = 'd', $Timestamp = false) {
        $From = self::timeSlotStamp($SlotType, $Timestamp);
        $To = strtotime('+1 '.self::$Increments[$SlotType], $From);
        return array($From, $To);
    }

    /**
     *
     *
     * @param string $SlotType
     * @param bool $Timestamp
     * @return int
     * @throws Exception
     */
    public static function timeSlotStamp($SlotType = 'd', $Timestamp = false) {
        $Result = self::timeFromTimeSlot(self::timeSlot($SlotType, $Timestamp));
        return $Result;
    }

    /**
     *
     *
     * @param $TimeSlot
     * @return int
     * @throws Exception
     */
    public static function timeFromTimeSlot($TimeSlot) {
        if ($TimeSlot == '00000000') {
            return 0;
        }

        $Year = substr($TimeSlot, 0, 4);
        $Month = substr($TimeSlot, 4, 2);
        $Day = (int)substr($TimeSlot, 6, 2);
        if ($Day == 0) {
            $Day = 1;
        }
        $DateRaw = mktime(0, 0, 0, $Month, $Day, $Year);

        if ($DateRaw === false) {
            throw new Exception("Invalid timeslot '{$TimeSlot}', unable to convert to epoch");
        }

        return $DateRaw;
    }

    /**
     *
     *
     * @param $TimeSlot
     * @param string $Resolution
     * @return int
     * @throws Exception
     */
    public static function timeFromExtendedTimeSlot($TimeSlot, $Resolution = 'auto') {
        if ($TimeSlot == '00000000') {
            return 0;
        }

        list($Year, $Month, $Day, $Hour, $Minute) = array(1, 1, 1, 0, 0);
        if ($Resolution == 'auto') {
            $TimeslotLength = strlen($TimeSlot);
        } else {
            $TimeslotLength = $Resolution;
        }

        if ($TimeslotLength >= 4) {
            $Year = substr($TimeSlot, 0, 4);
        }

        if ($TimeslotLength >= 6) {
            $Month = substr($TimeSlot, 4, 2);
        }

        if ($TimeslotLength >= 8) {
            $Day = (int)substr($TimeSlot, 6, 2);
        }
        if ($Day == 0) {
            $Day = 1;
        }

        if ($TimeslotLength >= 10) {
            $Hour = (int)substr($TimeSlot, 8, 2);
        }

        if ($TimeslotLength >= 12) {
            $Minute = (int)substr($TimeSlot, 10, 2);
        }

        $DateRaw = mktime($Hour, $Minute, 0, $Month, $Day, $Year);

        if ($DateRaw === false) {
            throw new Exception("Invalid timeslot '{$TimeSlot}', unable to convert to epoch");
        }

        return $DateRaw;
    }

    /**
     *
     *
     * @return bool
     */
    public function validateCredentials() {
        $Request = array();
        $this->basicParameters($Request);

        $VanillaID = Gdn::installationID();
        $VanillaSecret = Gdn::installationSecret();
        // Don't try to send stats if we don't have a proper install
        if (is_null($VanillaID) || is_null($VanillaSecret)) {
            return false;
        }

        $Request = array_merge($Request, array(
            'VanillaID' => $VanillaID
        ));

        $Response = $this->analytics('Verify', $Request, false);
        $Status = val('Status', $Response, 404);

        if ($Status == 200) {
            return true;
        }
        return false;
    }

    /**
     * Signature check.
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
    protected function verifySignature($Request) {

        // If this response has no ID, return NULL (could not verify)
        $VanillaID = GetValue('VanillaID', $Request, null);
        if (is_null($VanillaID)) {
            return null;
        }

        // Response is bogus - wrong InstallationID
        if (!is_null(Gdn::installationID()) && $VanillaID != Gdn::installationID()) {
            return false;
        }

        // If we don't have a secret, we cannot verify anyway
        $VanillaSecret = Gdn::installationSecret();
        if (is_null($VanillaSecret)) {
            return null;
        }

        // Calculate clock desync
        $CurrentGmTime = Gdn_Statistics::time();
        $RequestTime = val('RequestTime', $Request, 0);
        $TimeDiff = abs($CurrentGmTime - $RequestTime);
        $AllowedTimeDiff = C('Garden.Analytics.RequestTimeout', 1440);

        // Allow 24* minutes of clock desync, otherwise signature is invalid
        if ($TimeDiff > $AllowedTimeDiff) {
            return false;
        }

        $SecurityHash = val('SecurityHash', $Request);

        // Remove the existing SecuritHash before calculating the signature
        unset($Request['SecurityHash']);
        // Add the real secret
        $Request['Secret'] = $VanillaSecret;

        $SignData = array_intersect_key($Request, array_fill_keys(array(
            'VanillaID',
            'Secret',
            'RequestTime',
            'TimeSlot'
        ), null));

        // ksort the array to preserve a known order
        $SignData = array_change_key_case($SignData, CASE_LOWER);
        ksort($SignData);

        // Calculate the hash
        $RealHash = sha1(http_build_query($SignData));

        if ($RealHash == $SecurityHash) {
            return true;
        }

        return false;
    }
}
