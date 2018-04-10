<?php
/**
 * Analytics system.
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.17
 */

/**
 * Handles install-side analytics gathering and sending.
 */
class Gdn_Statistics extends Gdn_Pluggable {
    /** @var mixed  */
    protected $AnalyticsServer;

    /** @var array  */
    public static $Increments = ['h' => 'hours', 'd' => 'days', 'w' => 'weeks', 'm' => 'months', 'y' => 'years'];

    /** @var array  */
    protected $TickExtra;

    /**
     *
     */
    public function __construct() {
        parent::__construct();

        $analyticsServer = c('Garden.Analytics.Remote', 'analytics.vanillaforums.com');
        $analyticsServer = str_replace(['http://', 'https://'], '', $analyticsServer);
        $this->AnalyticsServer = $analyticsServer;

        $this->TickExtra = [];
    }

    /**
     *
     *
     * @param $method
     * @param $requestParameters
     * @param bool $callback
     * @param bool $parseResponse
     * @return array|bool|mixed|type
     * @throws Exception
     */
    public function analytics($method, $requestParameters, $callback = false, $parseResponse = true) {
        $fullMethod = explode('/', $method);
        if (sizeof($fullMethod) < 2) {
            array_unshift($fullMethod, "analytics");
        }

        list($apiController, $apiMethod) = $fullMethod;
        $apiController = strtolower($apiController);
        $apiMethod = stringEndsWith(strtolower($apiMethod), '.json', true, true).'.json';

        $finalURL = 'http://'.combinePaths([
                $this->AnalyticsServer,
                $apiController,
                $apiMethod
            ]);

        $requestHeaders = [];

        // Allow hooking of analytics events
        $this->EventArguments['AnalyticsMethod'] = &$method;
        $this->EventArguments['AnalyticsArgs'] = &$requestParameters;
        $this->EventArguments['AnalyticsUrl'] = &$finalURL;
        $this->EventArguments['AnalyticsHeaders'] = &$requestHeaders;
        $this->fireEvent('SendAnalytics');

        // Sign request
        $this->sign($requestParameters, true);
        $requestMethod = val('RequestMethod', $requestParameters, 'GET');
        unset($requestParameters['RequestMethod']);

        try {
            $proxyRequest = new ProxyRequest(false, [
                'Method' => $requestMethod,
                'Timeout' => 10,
                'Cookies' => false
            ]);
            $response = $proxyRequest->request([
                'Url' => $finalURL,
                'Log' => false
            ], $requestParameters, null, $requestHeaders);
        } catch (Exception $e) {
            $response = false;
        }

        if ($response !== false) {
            $jsonResponse = json_decode($response, true);

            if ($jsonResponse !== false) {
                if ($parseResponse) {
                    $analyticsJsonResponse = (array)val('Analytics', $jsonResponse, false);
                    // If we received a reply, parse it
                    if ($analyticsJsonResponse !== false) {
                        $this->parseAnalyticsResponse($analyticsJsonResponse, $response, $callback);
                        return $analyticsJsonResponse;
                    }
                } else {
                    return $jsonResponse;
                }
            }

            return $response;
        }

        return false;
    }

    /**
     *
     *
     * @param $method
     * @param $parameters
     * @return array|bool|mixed|type
     */
    public function api($method, $parameters) {
        $apiResponse = $this->analytics($method, $parameters, false, false);
        return $apiResponse;
    }

    /**
     *
     *
     * @param $jsonResponse
     */
    protected function analyticsFailed($jsonResponse) {
        self::throttled(true);

        $reason = val('Reason', $jsonResponse, null);
        if (!is_null($reason)) {
            Gdn::controller()->informMessage("Analytics: {$reason}");
        }
    }

    /**
     * Automatically configures a ProxyRequest array with basic parameters
     * such as IP, VanillaVersion, RequestTime, Hostname, PHPVersion, ServerType.
     *
     * @param array $request Reference to the existing request array
     * @return void
     */
    public function basicParameters(&$request) {
        $request = array_merge($request, [
            'ServerHostname' => url('/', true),
            'ServerType' => Gdn::request()->getValue('SERVER_SOFTWARE'),
            'PHPVersion' => str_replace(PHP_EXTRA_VERSION, '', PHP_VERSION),
            'VanillaVersion' => APPLICATION_VERSION
        ]);
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
     * @param $name
     * @param $value
     */
    public function addExtra($name, $value) {
        $this->TickExtra[$name] = $value;
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
        $exceptionApplications = ['dashboard'];

        // ... unless one of these paths is requested
        $exceptionPaths = ['profile*', 'activity*'];

        $path = Gdn::request()->path();
        foreach ($exceptionPaths as $exceptionPath) {
            if (fnmatch($exceptionPath, $path)) {
                return true;
            }
        }

        $applicationFolder = Gdn::controller()->ApplicationFolder;
        if (in_array($applicationFolder, $exceptionApplications)) {
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
        $serverAddress = Gdn::request()->ipAddress();
        $serverHostname = Gdn::request()->getValue('SERVER_NAME');

        // IPv6 Localhost
        if ($serverAddress == '::1') {
            return true;
        }

        // Private subnets
        foreach ([
                     '127.0.0.1/0',
                     '10.0.0.0/8',
                     '172.16.0.0/12',
                     '192.168.0.0/16'] as $localCIDR) {
            if (self::cidrCheck($serverAddress, $localCIDR)) {
                return true;
            }
        }

        // Comment local hostnames / hostname suffixes
        if ($serverHostname == 'localhost' || substr($serverHostname, -6) == '.local') {
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
        if (!c('Garden.Installed', false)) {
            return false;
        }

        // Enabled if not explicitly disabled via config
        if (!c('Garden.Analytics.Enabled', true)) {
            return false;
        }

        // Don't track things for local sites (unless overridden in config)
        if (self::checkIsLocalhost() && !c('Garden.Analytics.AllowLocal', false)) {
            return 0;
        }

        return true;
    }

    /**
     *
     *
     * credit: claudiu(at)cnixs.com via php.net/manual/en/ref.network.php
     *
     * @param $iP
     * @param $cIDR
     * @return bool
     */
    public static function cidrCheck($iP, $cIDR) {
        list ($net, $mask) = explode("/", $cIDR);

        // Allow non-standard /0 syntax
        if ($mask == 0) {
            if (ip2long($iP) == ip2long($net)) {
                return true;
            } else {
                return false;
            }
        }

        $ip_net = ip2long($net);
        $ip_mask = ~((1 << (32 - $mask)) - 1);

        $ip_ip = ip2long($iP);

        $ip_ip_net = $ip_ip & $ip_mask;

        return ($ip_ip_net == $ip_net);
    }

    /**
     *
     *
     * @param $response
     * @param $raw
     */
    protected function doneRegister($response, $raw) {
        $vanillaID = val('VanillaID', $response, false);
        $secret = val('Secret', $response, false);
        if (($secret && $vanillaID) !== false) {
            Gdn::installationID($vanillaID);
            Gdn::installationSecret($secret);
            Gdn::set('Garden.Analytics.Registering', null);
            Gdn::set('Garden.Analytics.LastSentDate', null);
        }
    }

    /**
     *
     *
     * @param $response
     * @param $raw
     */
    protected function doneStats($response, $raw) {
        $successTimeSlot = val('TimeSlot', $response, false);
        if ($successTimeSlot !== false) {
            self::lastSentDate($successTimeSlot);
        }
    }

    /**
     *
     *
     * @return mixed
     */
    public static function firstDate() {
        $firstDate = Gdn::sql()
            ->select('DateInserted', 'min')
            ->from('User')
            ->where('DateInserted >', '0000-00-00')
            ->get()->value('DateInserted');
        return $firstDate;
    }

    /**
     *
     *
     * @param null $setLastSentDate
     * @return mixed|null
     */
    public static function lastSentDate($setLastSentDate = null) {
        static $lastSentDate = null;

        // Set
        if (!is_null($setLastSentDate)) {
            $lastSentDate = $setLastSentDate;
            Gdn::set('Garden.Analytics.LastSentDate', $lastSentDate);
        }

        // Lazy Load
        if ($lastSentDate === null) {
            $lastSentDate = Gdn::get('Garden.Analytics.LastSentDate', false);
        }

        return $lastSentDate;
    }

    /**
     *
     *
     * @param $jsonResponse
     * @param $rawResponse
     * @param $callbacks
     */
    protected function parseAnalyticsResponse($jsonResponse, $rawResponse, $callbacks) {
        // Verify signature of reply
        $verified = $this->verifySignature($jsonResponse);
        if ($verified === false) {
            return;
        }

        // Only allow commands when verification was explicitly successful
        if ($verified === true) {
            // Perform response commands
            foreach ($jsonResponse as $commandName => $commandValue) {
                switch ($commandName) {
                    case 'DoDeregister':
                        if ($verified) {
                            // De-register yourself
                            Gdn::installationID(false);
                            Gdn::installationSecret(false);
                        }
                        break;

                    case 'DoDisable':
                        if ($verified) {
                            // Turn yourself off
                            saveToConfig('Garden.Analytics.Enabled', false);
                        }
                        break;

                    case 'DoCall':
                        // Call the admin's attention to the statistics
                        Gdn::set('Garden.Analytics.Notify', $commandValue);
                        break;

                    default:
                        // Nothing
                        break;
                }
            }
        }

        if (is_string($callbacks)) {
            // Assume a string is the Success event handler
            $callbacks = [
                'Success' => $callbacks
            ];
        }

        if (!is_array($callbacks)) {
            $callbacks = [];
        }

        // Assume strings are local methods
        foreach ($callbacks as $event => &$callbackMethod) {
            if (is_string($callbackMethod)) {
                $callbackMethod = [$this, $callbackMethod];
            }
        }

        $responseCode = val('Status', $jsonResponse, 500);
        $callbackExecute = null;
        switch ($responseCode) {
            case false:
            case 500:
                if (array_key_exists('Failure', $callbacks)) {
                    $callbackExecute = $callbacks['Failure'];
                }
                break;

            case true:
            case 200:
                self::throttled(false);
                if (array_key_exists('Success', $callbacks)) {
                    $callbackExecute = $callbacks['Success'];
                }
                break;
        }

        if (!is_null($callbackExecute)) {
            call_user_func($callbackExecute, $jsonResponse, $rawResponse);
        }
    }

    /**
     *
     */
    public function register() {
        // Set the time we last attempted to perform registration
        Gdn::set('Garden.Analytics.Registering', time());

        // Request registration from remote server
        $request = [];
        $this->basicParameters($request);
        $this->analytics('Register', $request, [
            'Success' => 'DoneRegister',
            'Failure' => 'AnalyticsFailed'
        ]);
    }

    /**
     * Sign a request or response.
     *
     * Uses the known site secret to sign the given request or response. The
     * request/response is passed in by reference so that it can be augmented with the signature.
     *
     * @param array $request The request array to be signed
     * @param boolean $modify Optional whether or not to modify the request in place (default false)
     */
    public function sign(&$request, $modify = false) {
        // Fail if no ID is present
        $vanillaID = getValue('VanillaID', $request, false);
        if (empty($vanillaID)) {
            return false;
        }

        if ($vanillaID != Gdn::installationID()) {
            return false;
        }

        // We're going to work on a copy for now
        $signatureArray = $request;

        // Build the request time
        $requestTime = Gdn_Statistics::time();
        // Get the secret key
        $requestSecret = Gdn::installationSecret();

        // Remove the hash from the request data before checking or building the signature
        unset($signatureArray['SecurityHash']);

        // Add the real secret and request time
        $signatureArray['Secret'] = $requestSecret;
        $signatureArray['RequestTime'] = $requestTime;

        $signData = array_intersect_key($signatureArray, array_fill_keys([
            'VanillaID',
            'Secret',
            'RequestTime',
            'TimeSlot'
        ], null));

        // ksort the array to preserve a known order
        $signData = array_change_key_case($signData, CASE_LOWER);
        ksort($signData);

        // Calculate the hash
        $realHash = sha1(http_build_query($signData));

        if ($modify) {
            $request['RequestTime'] = $requestTime;
            $request['SecurityHash'] = $realHash;
            ksort($request);
        }

        return $realHash;
    }

    /**
     *
     *
     * @throws Exception
     */
    protected function stats() {
        $startTime = time();
        $request = [];
        $this->basicParameters($request);

        $vanillaID = Gdn::installationID();
        $vanillaSecret = Gdn::installationSecret();
        // Don't try to send stats if we don't have a proper install
        if (is_null($vanillaID) || is_null($vanillaSecret)) {
            return;
        }

        // Always look at stats for the day following the previous successful send.
        $lastSentDate = self::lastSentDate();
        if ($lastSentDate === false) { // Never sent
            $statsDate = strtotime('yesterday');
        } else {
            $statsDate = strtotime('+1 day', self::timeFromTimeSlot($lastSentDate));
        }

        $statsTimeSlot = date('Ymd', $statsDate);
        if ($statsTimeSlot >= date('Ymd')) {
            return;
        }

        $detectActiveInterval = 0;
        $maxIterations = 10;
        $timeSlotLimit = date('Ymd');
        do {
            $timeSlot = date('Ymd', $statsDate);

            // We're caught up to today. Stop looping.
            if ($timeSlot >= $timeSlotLimit) {
                break;
            }

            $dayStart = date('Y-m-d 00:00:00', $statsDate);
            $dayEnd = date('Y-m-d 23:59:59', $statsDate);

            // Get relevant stats
            $numComments = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('Comment')
                ->where('DateInserted>=', $dayStart)
                ->where('DateInserted<', $dayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $numComments = val('Hits', $numComments, null);

            $numDiscussions = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('Discussion')
                ->where('DateInserted>=', $dayStart)
                ->where('DateInserted<', $dayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $numDiscussions = val('Hits', $numDiscussions, null);

            // Count the number of commenters that ONLY commented.
            $numCommenters = Gdn::sql()
                ->select('distinct c.InsertUserID', 'COUNT', 'Hits')
                ->from('Comment c')
                ->join('Discussion d', 'c.DiscussionID = d.DiscussionID')
                ->where('c.InsertUserID<>', 'd.InsertUserID', false, false)
                ->where('c.DateInserted>=', $dayStart)
                ->where('c.DateInserted<', $dayEnd)
                ->get()->value('Hits', null);

            // Count the number of users that have started a discussion.
            $numDiscussioners = Gdn::sql()
                ->select('distinct InsertUserID', 'COUNT', 'Hits')
                ->from('Discussion d')
                ->where('DateInserted>=', $dayStart)
                ->where('DateInserted<', $dayEnd)
                ->get()->value('Hits', null);
            if ($numDiscussioners === null && $numCommenters === null) {
                $numContributors = null;
            } else {
                $numContributors = $numCommenters + $numDiscussioners;
            }

            $numUsers = Gdn::sql()
                ->select('DateInserted', 'COUNT', 'Hits')
                ->from('User')
                ->where('DateInserted>=', $dayStart)
                ->where('DateInserted<', $dayEnd)
                ->get()->firstRow(DATASET_TYPE_ARRAY);
            $numUsers = val('Hits', $numUsers, null);

            $numViewsData = Gdn::sql()
                ->select('Views, EmbedViews')
                ->from('AnalyticsLocal')
                ->where('TimeSlot', $timeSlot)
                ->get()->firstRow(DATASET_TYPE_ARRAY);

            $numViews = val('Views', $numViewsData, null);
            $numEmbedViews = val('EmbedViews', $numViewsData, null);

            $detectActiveInterval = array_sum([
                $numComments,
                $numContributors,
                $numDiscussions,
                $numUsers,
                $numViews,
                $numEmbedViews
            ]);

            $statsDate = strtotime('+1 day', $statsDate);
            $maxIterations--;
            $runningTime = time() - $startTime;
        } while ($detectActiveInterval == 0 && $maxIterations && $runningTime <= 30);

        if ($detectActiveInterval == 0) {
            // We've looped $MaxIterations times or up until yesterday and couldn't find any stats. Remember our place and return.
            self::lastSentDate($timeSlot);
            return;
        }

        // Assemble Stats
        $request = array_merge($request, [
            'VanillaID' => $vanillaID,
            'TimeSlot' => $timeSlot,
            'CountComments' => $numComments,
            'CountAllContributors' => $numContributors,
            'CountDiscussions' => $numDiscussions,
            'CountUsers' => $numUsers,
            'CountViews' => $numViews,
            'CountEmbedViews' => $numEmbedViews
        ]);

        // Send stats to remote server
        $this->analytics('Stats', $request, [
            'Success' => 'DoneStats',
            'Failure' => 'AnalyticsFailed'
        ]);
    }

    /**
     *
     *
     * @param null $setThrottled
     * @return bool
     */
    public static function throttled($setThrottled = null) {
        static $throttled = null;

        // Set
        if (!is_null($setThrottled)) {
            if ($setThrottled) {
                $throttleDelay = c('Garden.Analytics.ThrottleDelay', 3600);
                $throttleValue = time() + $throttleDelay;
            } else {
                $throttleValue = null;
            }
            $throttled = (!is_null($throttleValue)) ? $throttleValue : 0;
            Gdn::set('Garden.Analytics.Throttle', $throttleValue);
        }

        // Lazy Load
        if ($throttled === null) {
            $throttled = Gdn::get('Garden.Analytics.Throttle', 0);
        }

        return ($throttled > time());
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
        $this->EventArguments['Path'] = Gdn::request()->post('Path');
        $this->fireEvent('Tick');

        // Store the view, using denormalization if enabled
        $viewType = 'normal';
        if (preg_match('`discussion/embed`', Gdn::request()->post('ResolvedPath', ''))) {
            $viewType = 'embed';
        }

        $this->addView($viewType);

        if (Gdn::session()->isValid()) {
            Gdn::userModel()->updateVisit(Gdn::session()->UserID);
        }

        if (!self::checkIsEnabled()) {
            return;
        }

        if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
            if (Gdn::get('Garden.Analytics.Notify', false) !== false) {
                $callMessage = sprite('Bandaid', 'InformSprite');
                $callMessage .= sprintf(t("There's a problem with Vanilla Analytics that needs your attention.<br/> Handle it <a href=\"%s\">here &raquo;</a>"), url('dashboard/statistics'));
                Gdn::controller()->informMessage($callMessage, ['CssClass' => 'HasSprite']);
            }
        }

        $installationID = Gdn::installationID();

        // Check if we're registered with the central server already. If not, this request is
        // hijacked and used to perform that task instead of sending stats or recording a tick.
        if (is_null($installationID)) {
            // If the config file is not writable, gtfo
            $confFile = PATH_CONF.'/config.php';
            if (!is_writable($confFile)) {
                // Admins see a helpful notice
                if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                    $warning = sprite('Sliders', 'InformSprite');
                    $warning .= t('Your config.php file is not writable.');
                    Gdn::controller()->informMessage($warning, ['CssClass' => 'HasSprite']);
                }
                return;
            }

            $attemptedRegistration = Gdn::get('Garden.Analytics.Registering', false);
            // If we last attempted to register less than 60 seconds ago, do nothing. Could still be working.
            if ($attemptedRegistration !== false && (time() - $attemptedRegistration) < 60) {
                return;
            }

            return $this->register();
        }

        // If we get here, the installation is registered and we can decide on whether or not to send stats now.
        $lastSentDate = self::lastSentDate();
        if (empty($lastSentDate) || $lastSentDate < date('Ymd', strtotime('-1 day'))) {
            return $this->stats();
        }
    }

    /**
     * Increments overall pageview view count.
     *
     * @since 2.1a
     * @access public
     */
    public function addView($viewType = 'normal') {
        // Add a pageview entry.
        $timeSlot = date('Ymd');
        $px = Gdn::database()->DatabasePrefix;

        $views = 1;
        $embedViews = 0;

        try {
            if (c('Garden.Analytics.Views.Denormalize', false) &&
                Gdn::cache()->activeEnabled() &&
                Gdn::cache()->type() != Gdn_Cache::CACHE_TYPE_NULL
            ) {
                $cacheKey = "QueryCache.Analytics.CountViews";

                // Increment. If not success, create key.
                $incremented = Gdn::cache()->increment($cacheKey);
                if ($incremented === Gdn_Cache::CACHEOP_FAILURE) {
                    Gdn::cache()->store($cacheKey, 1);
                }

                // Get current cache value
                $views = Gdn::cache()->get($cacheKey);

                if ($viewType == 'embed') {
                    $embedCacheKey = "QueryCache.Analytics.CountEmbedViews";

                    // Increment. If not success, create key.
                    $embedIncremented = Gdn::cache()->increment($embedCacheKey);
                    if ($embedIncremented === Gdn_Cache::CACHEOP_FAILURE) {
                        Gdn::cache()->store($embedCacheKey, 1);
                    }

                    // Get current cache value
                    $embedViews = Gdn::cache()->get($embedCacheKey);
                }

                // Every X views, writeback to AnalyticsLocal
                $denormalizeWriteback = c('Garden.Analytics.Views.DenormalizeWriteback', 10);
                if (($views % $denormalizeWriteback) == 0) {
                    Gdn::controller()->setData('WritebackViews', $views);
                    Gdn::controller()->setData('WritebackEmbed', $embedViews);

                    Gdn::database()->query(
                        "insert into {$px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$views}, {$embedViews})
               on duplicate key update
                  Views = COALESCE(Views, 0)+{$views},
                  EmbedViews = COALESCE(EmbedViews, 0)+{$embedViews}",
                        [
                            ':TimeSlot' => $timeSlot
                        ]
                    );

                    // ... and get rid of those views from the keys

                    if ($views) {
                        Gdn::cache()->decrement($cacheKey, $views);
                    }

                    if ($embedViews) {
                        Gdn::cache()->decrement($embedCacheKey, $embedViews);
                    }
                }
            } else {
                $extraViews = 1;
                $extraEmbedViews = ($viewType == 'embed') ? 1 : 0;

                Gdn::database()->query(
                    "insert into {$px}AnalyticsLocal (TimeSlot, Views, EmbedViews) values (:TimeSlot, {$extraViews}, {$extraEmbedViews})
               on duplicate key update
                  Views = COALESCE(Views, 0)+{$extraViews},
                  EmbedViews = COALESCE(EmbedViews, 0)+{$extraEmbedViews}",
                    [
                        ':TimeSlot' => $timeSlot
                    ]
                );
            }
        } catch (Exception $ex) {
            if (Gdn::session()->checkPermission('Garden.Settings.Manage')) {
                throw $ex;
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
     * @param string $slotType
     * @param bool $timestamp
     * @return string
     */
    public static function timeSlot($slotType = 'd', $timestamp = false) {
        if (!$timestamp) {
            $timestamp = self::time();
        }

        if ($slotType == 'd') {
            $result = gmdate('Ymd', $timestamp);
        } elseif ($slotType == 'w') {
            $sub = gmdate('N', $timestamp) - 1;
            $timestamp = strtotime("-$sub days", $timestamp);
            $result = gmdate('Ymd', $timestamp);
        } elseif ($slotType == 'm')
            $result = gmdate('Ym', $timestamp).'00';
        elseif ($slotType == 'y')
            $result = gmdate('Y', $timestamp).'0000';
        elseif ($slotType == 'a')
            $result = '00000000';

        return $result;
    }

    /**
     *
     *
     * @param $slotType
     * @param $number
     * @param bool $timestamp
     * @return int
     */
    public static function timeSlotAdd($slotType, $number, $timestamp = false) {
        $timestamp = self::timeSlotStamp($slotType, $timestamp);
        $result = strtotime(sprintf('%+d %s', $number, self::$Increments[$slotType]), $timestamp);
        return $result;
    }

    /**
     *
     *
     * @param string $slotType
     * @param bool $timestamp
     * @return array
     */
    public static function timeSlotBounds($slotType = 'd', $timestamp = false) {
        $from = self::timeSlotStamp($slotType, $timestamp);
        $to = strtotime('+1 '.self::$Increments[$slotType], $from);
        return [$from, $to];
    }

    /**
     *
     *
     * @param string $slotType
     * @param bool $timestamp
     * @return int
     * @throws Exception
     */
    public static function timeSlotStamp($slotType = 'd', $timestamp = false) {
        $result = self::timeFromTimeSlot(self::timeSlot($slotType, $timestamp));
        return $result;
    }

    /**
     *
     *
     * @param $timeSlot
     * @return int
     * @throws Exception
     */
    public static function timeFromTimeSlot($timeSlot) {
        if ($timeSlot == '00000000') {
            return 0;
        }

        $year = substr($timeSlot, 0, 4);
        $month = substr($timeSlot, 4, 2);
        $day = (int)substr($timeSlot, 6, 2);
        if ($day == 0) {
            $day = 1;
        }
        $dateRaw = mktime(0, 0, 0, $month, $day, $year);

        if ($dateRaw === false) {
            throw new Exception("Invalid timeslot '{$timeSlot}', unable to convert to epoch");
        }

        return $dateRaw;
    }

    /**
     *
     *
     * @param $timeSlot
     * @param string $resolution
     * @return int
     * @throws Exception
     */
    public static function timeFromExtendedTimeSlot($timeSlot, $resolution = 'auto') {
        if ($timeSlot == '00000000') {
            return 0;
        }

        list($year, $month, $day, $hour, $minute) = [1, 1, 1, 0, 0];
        if ($resolution == 'auto') {
            $timeslotLength = strlen($timeSlot);
        } else {
            $timeslotLength = $resolution;
        }

        if ($timeslotLength >= 4) {
            $year = substr($timeSlot, 0, 4);
        }

        if ($timeslotLength >= 6) {
            $month = substr($timeSlot, 4, 2);
        }

        if ($timeslotLength >= 8) {
            $day = (int)substr($timeSlot, 6, 2);
        }
        if ($day == 0) {
            $day = 1;
        }

        if ($timeslotLength >= 10) {
            $hour = (int)substr($timeSlot, 8, 2);
        }

        if ($timeslotLength >= 12) {
            $minute = (int)substr($timeSlot, 10, 2);
        }

        $dateRaw = mktime($hour, $minute, 0, $month, $day, $year);

        if ($dateRaw === false) {
            throw new Exception("Invalid timeslot '{$timeSlot}', unable to convert to epoch");
        }

        return $dateRaw;
    }

    /**
     *
     *
     * @return bool
     */
    public function validateCredentials() {
        $request = [];
        $this->basicParameters($request);

        $vanillaID = Gdn::installationID();
        $vanillaSecret = Gdn::installationSecret();
        // Don't try to send stats if we don't have a proper install
        if (is_null($vanillaID) || is_null($vanillaSecret)) {
            return false;
        }

        $request = array_merge($request, [
            'VanillaID' => $vanillaID
        ]);

        $response = $this->analytics('Verify', $request, false);
        $status = val('Status', $response, 404);

        if ($status == 200) {
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
     * @param type $request Array of request parameters
     * @return boolean Status of verification check, or null if no VanillaID
     */
    protected function verifySignature($request) {

        // If this response has no ID, return NULL (could not verify)
        $vanillaID = getValue('VanillaID', $request, null);
        if (is_null($vanillaID)) {
            return null;
        }

        // Response is bogus - wrong InstallationID
        if (!is_null(Gdn::installationID()) && $vanillaID != Gdn::installationID()) {
            return false;
        }

        // If we don't have a secret, we cannot verify anyway
        $vanillaSecret = Gdn::installationSecret();
        if (is_null($vanillaSecret)) {
            return null;
        }

        // Calculate clock desync
        $currentGmTime = Gdn_Statistics::time();
        $requestTime = val('RequestTime', $request, 0);
        $timeDiff = abs($currentGmTime - $requestTime);
        $allowedTimeDiff = c('Garden.Analytics.RequestTimeout', 1440);

        // Allow 24* minutes of clock desync, otherwise signature is invalid
        if ($timeDiff > $allowedTimeDiff) {
            return false;
        }

        $securityHash = val('SecurityHash', $request);

        // Remove the existing SecuritHash before calculating the signature
        unset($request['SecurityHash']);
        // Add the real secret
        $request['Secret'] = $vanillaSecret;

        $signData = array_intersect_key($request, array_fill_keys([
            'VanillaID',
            'Secret',
            'RequestTime',
            'TimeSlot'
        ], null));

        // ksort the array to preserve a known order
        $signData = array_change_key_case($signData, CASE_LOWER);
        ksort($signData);

        // Calculate the hash
        $realHash = sha1(http_build_query($signData));

        if ($realHash == $securityHash) {
            return true;
        }

        return false;
    }

    /**
     * Generate an access token for stats graphs.
     *
     * @return bool|string Returns a token or **false** if required information is missing.
     */
    public static function generateToken() {
        $id = Gdn::installationID();
        $secret = Gdn::installationSecret();
        if (empty($id) || empty($secret)) {
            return false;
        }

        $str = 'v1.'.dechex(time());
        $token = $str.'.'.hash_hmac('sha1', $str, $secret);
        return $token;
    }
}
