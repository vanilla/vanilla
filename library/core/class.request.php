<?php
/**
 * Incoming request parser.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2016 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Represents a Request to the application, typically from the browser but potentially generated internally, in a format
 * that can be accessed directly by the Dispatcher.
 *
 * @method string requestURI($uri = null) Get/Set the Request URI (REQUEST_URI).
 * @method string requestScript($scriptName = null) Get/Set the Request ScriptName (SCRIPT_NAME).
 * @method string requestMethod($method = null) Get/Set the Request Method (REQUEST_METHOD).
 * @method string requestHost($uri = null) Get/Set the Request Host (SERVER_NAME).
 * @method string requestAddress($ip = null) Get/Set the Request IP address (first existing of HTTP_X_ORIGINALLY_FORWARDED_FOR,
 *                HTTP_X_CLUSTER_CLIENT_IP, HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, REMOTE_ADDR).
 */
class Gdn_Request {

    /** Superglobal source. */
    const INPUT_CUSTOM = "custom";

    /** Superglobal source. */
    const INPUT_ENV = "env";

    /** Superglobal source. */
    const INPUT_FILES = "files";

    /** Superglobal source. */
    const INPUT_GET = "get";

    /** Superglobal source. */
    const INPUT_POST = "post";

    /** Superglobal source. */
    const INPUT_SERVER = "server";

    /** Superglobal source. */
    const INPUT_COOKIES = "cookies";

    /** @var bool Whether or not _ParseRequest has been called yet. */
    protected $_HaveParsedRequest = false;

    /** @var array Raw environment variables, unparsed. */
    protected $_Environment;

    /** @var array Resolved/parsed request information. */
    protected $_ParsedRequest;

    /** @var bool  */
    protected $_Parsing = false;

    /** @var array Request data/parameters, either from superglobals or from a custom array of key/value pairs. */
    protected $_RequestArguments;

    /**
     *
     */
    private function __construct() {
        $this->reset();
    }

    /**
     * Gets/Sets the relative path to the asset include path.
     *
     * The asset root represents the folder that static assets are served from.
     *
     * @param string? $assetRoot An asset root to set.
     * @return string Returns the current asset root.
     */
    public function assetRoot($assetRoot = null) {
        if ($assetRoot !== null) {
            $result = $this->_parsedRequestElement('AssetRoot', rtrim('/'.trim($assetRoot, '/'), '/'));
        } else {
            $result = $this->_parsedRequestElement('AssetRoot');
        }
        return $result;
    }

    /**
     * Generic chainable object creation method.
     *
     * This creates a new Gdn_Request object, loaded with the current Environment $_SERVER and $_ENV superglobal imports, such
     * as REQUEST_URI, SCRIPT_NAME, etc. The intended usage is for additional setter methods to be chained
     * onto this call in order to fully set up the object.
     *
     * @flow chain
     * @return Gdn_Request
     */
    public static function create() {
        return new Gdn_Request();
    }

    /**
     * Gets/Sets the domain from the current url. e.g. "http://localhost" in
     * "http://localhost/this/that/garden/index.php?/controller/action/"
     *
     * @param $Domain optional value to set
     * @return string | NULL
     */
    public function domain($Domain = null) {
        return $this->_parsedRequestElement('Domain', $Domain);
    }

    /**
     * Accessor method for unparsed request environment data, such as the REQUEST_URI, SCRIPT_NAME,
     * SERVER_NAME and REQUEST_METHOD keys in $_SERVER.
     *
     * A second argument can be supplied, which causes the value of the specified key to be changed
     * to that of the second parameter itself.
     *
     * Currently recognized keys (and their relation to $_SERVER) are:
     *  - URI      -> REQUEST_URI
     *  - SCRIPT   -> SCRIPT_NAME
     *  - HOST     -> SERVER_NAME
     *  - METHOD   -> REQUEST_METHOD
     *  - FOLDER   -> none. this is extracted from SCRIPT_NAME and only available after _ParseRequest()
     *  - SCHEME   -> none. this is derived from 'HTTPS' and 'X-Forwarded-Proto'
     *
     * @param $key Key to retrieve or set.
     * @param $value Value of $Key key to set.
     * @return string | NULL
     */
    protected function _environmentElement($key, $value = null) {
        $key = strtoupper($key);
        if ($value !== null) {
            $this->_HaveParsedRequest = false;

            switch ($key) {
                case 'URI':
                    $value = !is_null($value) ? rawurldecode($value) : $value;
                    break;
                case 'SCRIPT':
                    $value = !is_null($value) ? trim($value, '/') : $value;
                    break;
                case 'HOST':
                    $hostParts = explode(':', $value);
                    $value = array_shift($hostParts);
                    break;
                case 'SCHEME':
                case 'METHOD':
                case 'FOLDER':
                default:
                    // Do nothing special for these
                    break;
            }

            $this->_Environment[$key] = $value;
        }

        if (array_key_exists($key, $this->_Environment)) {
            return $this->_Environment[$key];
        }

        return null;
    }

    /**
     * Convenience method for accessing unparsed environment data via Request(ELEMENT) method calls.
     *
     * @return string
     */
    public function __call($method, $args) {
        $matches = [];
        if (preg_match('/^(Request)(.*)$/i', $method, $matches)) {
            $passedArg = (is_array($args) && sizeof($args)) ? $args[0] : null;
            return $this->_environmentElement(strtoupper($matches[2]), $passedArg);
        } else {
            trigger_error("Call to unknown method 'Gdn_Request->{$method}'", E_USER_ERROR);
        }
    }

    /**
     * This method allows requests to export their internal data.
     *
     * Mostly used in conjunction with FromImport()
     *
     * @param $export Data group to export
     * @return mixed
     */
    public function export($export) {
        switch ($export) {
            case 'Environment':
                return $this->_Environment;
            case 'Arguments':
                return $this->_RequestArguments;
            case 'Parsed':
                return $this->_ParsedRequest;
            default:
                return null;
        }
    }

    /**
     * Gets/Sets the optional filename (ContentDisposition) of the output.
     *
     * As with the case above (OutputFormat), this value depends heavily on there being a filename
     * at the end of the URI. In the example above, Filename() would return 'cashflow2009.pdf'.
     *
     * @param $filename Optional Filename to set.
     * @return string
     */
    public function filename($filename = null) {
        return $this->_parsedRequestElement('Filename', $filename);
    }

    /**
     * Chainable lazy Environment Bootstrap
     *
     * Convenience method allowing quick setup of the default request state... from the current environment.
     *
     * @flow chain
     * @return Gdn_Request
     */
    public function fromEnvironment() {
        $this->withURI()
            ->withArgs(self::INPUT_GET, self::INPUT_POST, self::INPUT_SERVER, self::INPUT_FILES, self::INPUT_COOKIES);

        return $this;
    }

    /**
     * Chainable Request Importer
     *
     * This method allows one method to import the raw information of another request
     *
     * @param $newRequest New Request from which to import environment and arguments.
     * @flow chain
     * @return Gdn_Request
     */
    public function fromImport($newRequest) {
        // Import Environment
        $this->_Environment = $newRequest->export('Environment');
        // Import Arguments
        $this->_RequestArguments = $newRequest->export('Arguments');

        $this->_HaveParsedRequest = false;
        $this->_Parsing = false;
        return $this;
    }

    /**
     * Get a value from the GET array or return the entire GET array.
     *
     * @param string|null $key The key of the get item or null to return the entire get array.
     * @param mixed $default The value to return if the item isn't set.
     * @return mixed
     */
    public function get($key = null, $default = null) {
        if ($key === null) {
            return $this->getRequestArguments(self::INPUT_GET);
        } else {
            return $this->getValueFrom(self::INPUT_GET, $key, $default);
        }
    }

    /**
     * Export an entire dataset (effectively, one of the superglobals) from the request arguments list
     *
     * @param int $paramType Type of data to export. One of the self::INPUT_* constants
     * @return array
     */
    public function getRequestArguments($paramType = null) {
        if ($paramType === null) {
            return $this->_RequestArguments;
        } elseif (!isset($this->_RequestArguments[$paramType])) {
            return [];
        } else {
            return $this->_RequestArguments[$paramType];
        }
    }

    /**
     * Search the currently attached data arrays for the requested argument (in order) and
     * return the first match. Return $Default if not found.
     *
     * @param string $key Name of the request argument to retrieve.
     * @param mixed $default Value to return if argument not found.
     * @return mixed
     */
    public function getValue($key, $default = false) {
        return $this->merged($key, $default);
    }

    /**
     * Search one of the currently attached data arrays for the requested argument and return its value
     * or $Default if not found.
     *
     * @param $paramType Which request argument array to query for this value. One of the self::INPUT_* constants
     * @param $key Name of the request argument to retrieve.
     * @param $default Value to return if argument not found.
     * @return mixed
     */
    public function getValueFrom($paramType, $key, $default = false) {
        $paramType = strtolower($paramType);

        if (array_key_exists($paramType, $this->_RequestArguments) && array_key_exists($key, $this->_RequestArguments[$paramType])) {
            $value = $this->_RequestArguments[$paramType][$key];
            if (is_array($value) || is_object($value)) {
                return $value;
            } else {
                return $value;
            }
        }
        return $default;
    }

    /**
     * Gets/Sets the host from the current url. e.g. "foo.com" in
     * "http://foo.com/this/that/garden/index.php?/controller/action/"
     *
     * @param $hostname optional value to set.
     * @return string | NULL
     */
    public function host($hostname = null) {
        return $this->requestHost($hostname);
    }

    /**
     * Return the host and port together if the port isn't standard.
     * @return string
     * @since 2.1
     */
    public function hostAndPort() {
        $host = $this->host();
        $port = $this->port();
        if (!in_array($port, [80, 443])) {
            return $host.':'.$port;
        } else {
            return $host;
        }
    }

    /**
     * Alias for requestAddress()
     *
     * @return type
     */
    public function ipAddress() {
        return $this->requestAddress();
    }

    /**
     * Returns a boolean value indicating if the current page has an authenticated postback.
     *
     * @param bool $throw Whether or not to throw an exception if this is a postback AND the transient key doesn't validate.
     * @return bool Returns true if the postback could be authenticated or false otherwise.
     * @throws Gdn_UserException Throws an exception when this is a postback AND the transient key doesn't validate.
     * @since 2.1
     */
    public function isAuthenticatedPostBack($throw = false) {
        if (!$this->isPostBack()) {
            return false;
        }

        $transientKey = Gdn::request()->post('TransientKey', false);
        $result = Gdn::session()->validateTransientKey($transientKey, false);

        if (!$result && $throw) {
            throw new Gdn_UserException('The CSRF token is invalid.', 403);
        }

        return $result;
    }

    /**
     * Check if request was a POST
     *
     * @return bool
     */
    public function isPostBack() {
        return strcasecmp($this->requestMethod(), 'post') == 0;
    }

    /**
     * Gets/sets the port of the request.
     *
     * @param int $Port
     * @return int
     * @since 2.1
     */
    public function port($Port = null) {
        return $this->_environmentElement('PORT', $Port);
    }

    /**
     * Gets/Sets the scheme from the current url. e.g. "http" in
     * "http://foo.com/this/that/garden/index.php?/controller/action/"
     *
     * @param $Scheme optional value to set.
     * @return string | NULL
     */
    public function scheme($Scheme = null) {
        return $this->requestScheme($Scheme);
    }

    /**
     * Load the basics of the current environment
     *
     * The purpose of this method is to consolidate all the various environment information into one
     * array under a set of common names, thereby removing the tedium of figuring out which superglobal
     * and key combination contain the requested information each time it is needed.
     *
     * @return void
     */
    protected function _loadEnvironment() {
        $this->_environmentElement('ConfigWebRoot', Gdn::config('Garden.WebRoot'));
        $this->_environmentElement('ConfigStripUrls', Gdn::config('Garden.StripWebRoot', false));

        $host = val('SERVER_NAME', $_SERVER);

        // The host can have the port passed in, remove it here if it exists
        $hostParts = explode(':', $host, 2);
        $host = $hostParts[0];

        $rawPort = null;
        if (count($hostParts) > 1) {
            $rawPort = $hostParts[1];
        }

        $this->requestHost($host);
        $this->requestMethod(isset($_SERVER['REQUEST_METHOD']) ? val('REQUEST_METHOD', $_SERVER) : 'CONSOLE');

        // Request IP

        // Load balancers
        if ($testIP = val('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
            $ip = $testIP;
        } elseif ($testIP = val('HTTP_CLIENT_IP', $_SERVER)) {
            $ip = $testIP;
        } elseif ($testIP = val('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = $testIP;
        } else {
            $ip = val('REMOTE_ADDR', $_SERVER);
        }

        if (strpos($ip, ',') !== false) {
            $matched = preg_match_all('/([\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})(?:, )?/i', $ip, $matches);

            // If we found matching IPs
            if ($matched) {
                $ips = $matches[1];
                $ip = $ips[0];

                // Fallback
            } else {
                $remoteAddr = val('REMOTE_ADDR', $_SERVER);

                if (strpos($remoteAddr, ',') !== false) {
                    $remoteAddr = substr($remoteAddr, 0, strpos($remoteAddr, ','));
                }

                $ip = $remoteAddr;
            }
        }

        $ip = forceIPv4($ip);
        $this->requestAddress($ip);

        // Request Scheme

        $scheme = 'http';

        // Webserver-originated SSL
        if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') {
            $scheme = 'https';
        }

        // Loadbalancer-originated (and terminated) SSL
        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') {
            $scheme = 'https';
        }

        // Varnish
        $originalProto = val('HTTP_X_ORIGINALLY_FORWARDED_PROTO', $_SERVER, null);
        if (!is_null($originalProto)) {
            $scheme = $originalProto;
        }

        $this->requestScheme($scheme);

        if (isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        } elseif ($rawPort) {
            $port = $rawPort;
        } else {
            if ($scheme === 'https') {
                $port = 443;
            } else {
                $port = 80;
            }
        }
        $this->port($port);

        if (is_array($_GET)) {
            $get = false;
            if ($get === false) {
                $get =& $_GET;
            }
            if (!is_array($get)) {
                $original = [];
                parse_str($get, $original);
                safeParseStr($get, $get, $original);
            }

            if (!empty($_SERVER['X_REWRITE']) || !empty($_SERVER['REDIRECT_X_REWRITE'])) {
                $path = val('PATH_INFO', $_SERVER, '');

                // Some hosts block PATH_INFO from being passed (or even manually set).
                // We set X_PATH_INFO in the .htaccess as a fallback for those situations.
                // If you work for one of those hosts, know that many beautiful kittens lost their lives for your sins.
                if (!$path) {
                    if (!empty($_SERVER['X_PATH_INFO'])) {
                        $path = $_SERVER['X_PATH_INFO'];
                    } elseif (!empty($_SERVER['REDIRECT_X_PATH_INFO'])) {
                        $path = $_SERVER['REDIRECT_X_PATH_INFO'];
                    }
                }
            } elseif (isset($get['_p'])) {
                $path = $get['_p'];
                unset($_GET['_p']);
            } elseif (isset($get['p'])) {
                $path = $get['p'];
                unset($_GET['p']);
            } else {
                $path = '';
            }

            $this->requestURI($path);
        }

        $possibleScriptNames = [];
        if (isset($_SERVER['SCRIPT_NAME'])) {
            $possibleScriptNames[] = $_SERVER['SCRIPT_NAME'];
        }

        if (isset($_ENV['SCRIPT_NAME'])) {
            $possibleScriptNames[] = $_ENV['SCRIPT_NAME'];
        }

        if (PHP_SAPI === 'cgi' && isset($_ENV['SCRIPT_URL'])) {
            $possibleScriptNames[] = $_ENV['SCRIPT_URL'];
        }

        if (isset($_SERVER['SCRIPT_FILENAME'])) {
            $possibleScriptNames[] = $_SERVER['SCRIPT_FILENAME'];
        }

        if (isset($_SERVER['ORIG_SCRIPT_NAME'])) {
            $possibleScriptNames[] = $_SERVER['ORIG_SCRIPT_NAME'];
        }

        $this->requestFolder('');
        foreach ($possibleScriptNames as $scriptName) {
            $script = basename($scriptName);
            $this->requestScript($script);

            $folder = substr($scriptName, 0, 0 - strlen($script));
            if (isset($_SERVER['DOCUMENT_ROOT'])) {
                $documentRoot = $_SERVER['DOCUMENT_ROOT'];
            } else {
                $absolutePath = str_replace("\\", "/", realpath($script));
                $documentRoot = substr($absolutePath, 0, strpos($absolutePath, $scriptName));
            }

            if (!$documentRoot) {
                continue;
            }
            $trimRoot = rtrim($documentRoot);
            $realFolder = str_replace($trimRoot, '', $folder);

            if (!empty($realFolder)) {
                $this->requestFolder(ltrim($realFolder, '/'));
                break;
            }
        }
    }

    /**
     * Gets/Sets the Output format
     *
     * This method sets the OutputFormat that the dispatcher will look at when determining
     * how to serve the request to the browser. Currently, the handled values are:
     *  - default        -> typical html response
     *  - rss            -> rss formatted
     *  - atom           -> atom formatted
     *
     * If the request ends with a filename, such as in the case of:
     *    http://www.forum.com/vanilla/index.php?/discussion/345897/attachment/234/download/cashflow2009.pdf
     * then this method will return the filetype (in this case 'pdf').
     *
     * @param $outputFormat Optional OutputFormat to set.
     * @return string | NULL
     */
    public function outputFormat($outputFormat = null) {
        $outputFormat = (!is_null($outputFormat)) ? strtolower($outputFormat) : $outputFormat;
        return $this->_parsedRequestElement('OutputFormat', $outputFormat);
    }

    /**
     * Parse the Environment data into the ParsedRequest array.
     *
     * This method analyzes the Request environment and produces the ParsedRequest array which
     * contains the Path and OutputFormat keys. These are used by the Dispatcher to decide which
     * controller and method to invoke.
     *
     * @return void
     */
    protected function _parseRequest() {
        $this->_Parsing = true;

        /**
         * Resolve final request to send to dispatcher
         */

        $path = $this->_environmentElement('URI');

        // Get the dispatch string from the URI
        if ($path !== false) {
            $this->path(trim($path, '/'));
        } else {
            $expression = '/^(?:\/?'.str_replace('/', '\/', $this->_environmentElement('Folder')).')?(?:'.$this->_environmentElement('Script').')?\/?(.*?)\/?(?:[#?].*)?$/i';
            if (preg_match($expression, $this->_environmentElement('URI'), $match)) {
                $this->path($match[1]);
            } else {
                $this->path('');
            }
        }

        /**
         * Resolve optional output modifying file extensions (rss, json, etc)
         */

        $urlParts = explode('/', $this->path());
        $last = array_slice($urlParts, -1, 1);
        $lastParam = array_pop($last);
        $match = [];
        if (preg_match('/^(.+)\.([^.]{1,4})$/', $lastParam, $match)) {
            $this->outputFormat($match[2]);
            $this->filename($match[0]);
            //$this->Path(implode('/',array_slice($UrlParts, 0, -1)));
        }

        /**
         * Resolve WebRoot
         */

        // Attempt to get the web root from the server.
        $webRoot = str_replace('\\', '/', val('SCRIPT_NAME', $_SERVER, ''));
        if (($pos = strrpos($webRoot, '/index.php')) !== false) {
            $webRoot = substr($webRoot, 0, $pos);
        }

        $parsedWebRoot = trim($webRoot, '/');
        $this->webRoot($parsedWebRoot);
        $this->assetRoot($parsedWebRoot);

        /**
         * Resolve Domain
         */

        $domain = false;
        if ($domain === false || $domain == '') {
            $domain = $this->hostAndPort();
        }

        if ($domain != '' && $domain !== false) {
            if (!stristr($domain, '://')) {
                $domain = $this->scheme().'://'.$domain;
            }

            $domain = trim($domain, '/');
        }
        $this->domain($domain);

        $this->_Parsing = false;
        $this->_HaveParsedRequest = true;
    }

    /**
     * Accessor method for parsed request data, such as the final 'controller/method' string,
     * as well as the resolved output format such as 'rss' or 'default'.
     *
     * A second argument can be supplied, which causes the value of the specified key to be changed
     * to that of the second parameter itself.
     *
     * @param string $key element key to retrieve or set
     * @param string $value value of $Key key to set
     * @return string|null
     */
    protected function _parsedRequestElement($key, $value = null) {
        // Lazily parse if not already parsed
        if (!$this->_HaveParsedRequest && !$this->_Parsing) {
            $this->_parseRequest();
        }

        if ($value !== null) {
            $this->_ParsedRequest[$key] = $value;
        }

        if (array_key_exists($key, $this->_ParsedRequest)) {
            return $this->_ParsedRequest[$key];
        }

        return null;
    }

    /**
     * Gets/Sets the final path to be sent to the dispatcher.
     *
     * @param string|true|null $path Optional Path to set
     *  - string: Set a new path.
     *  - true: Url encode the returned path.
     *  - null: Return the path.
     * @return string | NULL
     */
    public function path($path = null) {
        if (is_string($path)) {
            $result = $this->_parsedRequestElement('Path', $path);
        } else {
            $result = $this->_parsedRequestElement('Path');
            if ($path === true) {
                // Encode the path.
                $parts = explode('/', $result);
                $parts = array_map('rawurlencode', $parts);
                $result = implode('/', $parts);
            }
        }

        return $result;
    }

    public function pathAndQuery($pathAndQuery = null) {
        // Set the path and query if it is supplied.
        if ($pathAndQuery) {
            // Parse out the path into parts.
            $parts = parse_url($pathAndQuery);
            $path = val('path', $parts, '');

            // Check for a filename.
            $filename = basename($path);
            if (strpos($filename, '.') === false) {
                $filename = 'default';
            }
            $path = trim($path, '/');

            $query = val('query', $parts, '');
            if (strlen($query) > 0) {
                parse_str($query, $get);
            } else {
                $get = [];
            }

            // Set the parts of the query here.
            if (!$this->_HaveParsedRequest) {
                $this->_parseRequest();
            }
            $this->_ParsedRequest['Path'] = $path;
            $this->_ParsedRequest['Filename'] = $filename;
            $this->_RequestArguments[self::INPUT_GET] = $get;
        }

        // Construct the path and query.
        $result = $this->path();

//      $Filename = $this->Filename();
//      if ($Filename && $Filename != 'default')
//         $Result .= ConcatSep('/', $Result, $Filename);
        $get = $this->getRequestArguments(self::INPUT_GET);
        if (count($get) > 0) {
            // mosullivan 2011-05-04 - There is a bug in this code that causes a qs
            // param to be present in the path, which makes appending with a ?
            // invalid. This code is too nasty to figure out. Kludge.
            $result .= strpos($result, '?') === false ? '?' : '&';
            $result .= http_build_query($get);
        }

        return $result;
    }

    /**
     * Get a value from the post array or return the entire POST array.
     *
     * @param string|null $key The key of the post item or null to return the entire array.
     * @param mixed $default The value to return if the item isn't set.
     * @return mixed
     */
    public function post($key = null, $default = null) {
        if ($key === null) {
            return $this->getRequestArguments(self::INPUT_POST);
        } else {
            return $this->getValueFrom(self::INPUT_POST, $key, $default);
        }
    }

    public function reset() {
        $this->_Environment = [];
        $this->_RequestArguments = [];
        $this->_ParsedRequest = [
            'Path' => '',
            'OutputFormat' => 'default',
            'Filename' => 'default',
            'WebRoot' => '',
            'Domain' => ''
        ];
        $this->_loadEnvironment();
    }

    /**
     * Get a value from the merged param array or return the entire merged array
     *
     * @param string|null $key The key of the post item or null to return the entire array.
     * @param mixed $default The value to return if the item isn't set.
     * @return mixed
     */
    public function merged($key = null, $default = null) {
        $merged = [];
        $queryOrder = [
            self::INPUT_CUSTOM,
            self::INPUT_GET,
            self::INPUT_POST,
            self::INPUT_FILES,
            self::INPUT_SERVER,
            self::INPUT_ENV,
            self::INPUT_COOKIES
        ];
        $numDataTypes = sizeof($queryOrder);
        for ($i = $numDataTypes; $i > 0; $i--) {
            $dataType = $queryOrder[$i - 1];
            if (!array_key_exists($dataType, $this->_RequestArguments)) {
                continue;
            }
            $merged = array_merge($merged, $this->_RequestArguments[$dataType]);
        }

        return (is_null($key)) ? $merged : val($key, $merged, $default);
    }

    /**
     * Attach an array of request arguments to the request.
     *
     * @param int $paramsType type of data to import. One of the self::INPUT_* constants
     * @param array $paramsData optional data array to import if ParamsType is INPUT_CUSTOM
     * @return void
     */
    protected function _setRequestArguments($paramsType, $paramsData = null) {
        switch ($paramsType) {
            case self::INPUT_GET:
                $argumentData = $_GET;
                break;

            case self::INPUT_POST:
                $argumentData = $_POST;
                break;

            case self::INPUT_SERVER:
                $argumentData = $_SERVER;
                break;

            case self::INPUT_FILES:
                $argumentData = $_FILES;
                break;

            case self::INPUT_ENV:
                $argumentData = $_ENV;
                break;

            case self::INPUT_COOKIES:
                $argumentData = $_COOKIE;
                break;

            case self::INPUT_CUSTOM:
                $argumentData = is_array($paramsData) ? $paramsData : [];
                break;

        }
        $this->_RequestArguments[$paramsType] = $argumentData;
    }

    public function setRequestArguments($paramsType, $paramsData) {
        $this->_RequestArguments[$paramsType] = $paramsData;
    }

    public function setValueOn($paramType, $paramName, $paramValue) {
        if (!isset($this->_RequestArguments[$paramType])) {
            $this->_RequestArguments[$paramType] = [];
        }

        $this->_RequestArguments[$paramType][$paramName] = $paramValue;
    }

    /**
     * Detach a dataset from the request
     *
     * @param int $paramsType type of data to remove. One of the self::INPUT_* constants
     * @return void
     */
    public function _unsetRequestArguments($paramsType) {
        unset($this->_RequestArguments[$paramsType]);
    }

    /**
     * This method allows safe creation of URLs that need to reference the application itself
     *
     * Taking the server's Rewrite ability into account, and using information from the
     * actual Request data, this method can construct a trustworthy URL that will point to
     * Garden's dispatcher. Examples:
     *    - Default port, no rewrites, subfolder:      http://www.forum.com/vanilla/index.php?/
     *    - Default port, rewrites                     http://www.forum.com/
     *    - Custom port, rewrites                      http://www.forum.com:8080/index.php?/
     *
     * @param sring $path of the controller method.
     * @param mixed $withDomain Whether or not to include the domain with the url. This can take the following values.
     * - true: Include the domain name.
     * - false: Do not include the domain. This is a relative path.
     * - //: Include the domain name, but use the // schemeless notation.
     * - /: Just return the path.
     * @param bool $ssl set to true to implement SSL
     * @return string
     *
     * @changes
     *    2.1   Added the // option to $WithDomain.
     *    2.2   Added the / option to $WithDomain.
     */
    public function url($path = '', $withDomain = false, $ssl = null) {
        static $allowSSL = null;
        if ($allowSSL === null) {
            $allowSSL = c('Garden.AllowSSL', false);
        }
        static $rewrite = null;
        if ($rewrite === null) {
            $rewrite = val('X_REWRITE', $_SERVER, c('Garden.RewriteUrls', true));
        }

        if (!$allowSSL) {
            $ssl = null;
        } elseif ($withDomain === 'https') {
            $ssl = true;
            $withDomain = true;
        }

        // If we are explicitly setting ssl urls one way or another
        if (!is_null($ssl)) {
            // Force the full domain in the url
            $withDomain = true;
            // And make sure to use ssl or not
            if ($ssl) {
                $path = str_replace('http:', 'https:', $path);
                $scheme = 'https';
            } else {
                $path = str_replace('https:', 'http:', $path);
                $scheme = 'http';
            }
        } else {
            $scheme = $this->scheme();
        }
        if (substr($path, 0, 2) == '//' || in_array(strpos($path, '://'), [4, 5])) { // Accounts for http:// and https:// - some querystring params may have "://", and this would cause things to break.
            return $path;
        }

        $parts = [];

        $port = $this->port();
        $host = $this->host();
        if (!in_array($port, [80, 443]) && (strpos($host, ':'.$port) === false)) {
            $host .= ':'.$port;
        }

        if ($withDomain === '//') {
            $parts[] = '//'.$host;
        } elseif ($withDomain && $withDomain !== '/') {
            $parts[] = $scheme.'://'.$host;
        } else {
            $parts[] = '';
        }

        if ($withDomain !== '/' && $this->webRoot() != '') {
            $parts[] = $this->webRoot();
        }

        // Strip out the hash.
        $hash = strchr($path, '#');
        if (strlen($hash) > 0) {
            $path = substr($path, 0, -strlen($hash));
        }

        // Strip out the querystring.
        $query = strrchr($path, '?');
        if (strlen($query) > 0) {
            $path = substr($path, 0, -strlen($query));
        }

        if (!$rewrite && $withDomain !== '/') {
            $parts[] = $this->_environmentElement('Script').'?p=';
            $query = str_replace('?', '&', $query);
        }

        if ($path == '') {
            $pathParts = explode('/', $this->path());
            $pathParts = array_map('rawurlencode', $pathParts);
            $path = implode('/', $pathParts);
            // Grab the get parameters too.
            if (!$query) {
                $query = $this->getRequestArguments(self::INPUT_GET);
                if (count($query) > 0) {
                    $query = ($rewrite ? '?' : '&amp;').http_build_query($query);
                } else {
                    unset($query);
                }
            }
        }
        $parts[] = ltrim($path, '/');

        $result = implode('/', $parts);

        // If we are explicitly setting ssl urls one way or another
        if (!is_null($ssl)) {
            // And make sure to use ssl or not
            if ($ssl) {
                $result = str_replace('http:', 'https:', $result);
            } else {
                $result = str_replace('https:', 'http:', $result);
            }
        }

        if (isset($query)) {
            $result .= $query;
        }

        if (isset($hash)) {
            $result .= $hash;
        }

        return $result;
    }

    /**
     * Compare two urls for equality.
     *
     * @param string $url1 The first url to compare.
     * @param string $url2 The second url to compare.
     * @return int Returns 0 if the urls are equal or 1, -1 if they are not.
     */
    function urlCompare($url1, $url2) {
        $parts1 = parse_url($this->url($url1));
        $parts2 = parse_url($this->url($url2));

        $defaults = [
            'scheme' => $this->scheme(),
            'host' => $this->hostAndPort(),
            'path' => '/',
            'query' => ''
        ];

        $parts1 = array_replace($defaults, $parts1 ?: []);
        $parts2 = array_replace($defaults, $parts2 ?: []);

        if ($parts1['host'] === $parts2['host']
            && ltrim($parts1['path'], '/') === ltrim($parts2['path'], '/')
            && $parts1['query'] === $parts2['query']
        ) {
            return 0;
        }

        return strcmp($url1, $url2);
    }

    /**
     * Conditionally gets the domain of the request.
     *
     * This method will return nothing or the domain with an http, https, or // scheme depending on {@link $withDomain}.
     *
     * @param bool $withDomain How to include the domain in the result.
     * - false or /: The domain will not be returned.
     * - //: The domain prefixed with //.
     * - http: The domain prefixed with http://.
     * - https: The domain prefixed with https://.
     * - true: The domain prefixed with the current request scheme.
     * @return string Returns the domain according to the rules set by {@see $withDomain}.
     */
    public function urlDomain($withDomain = true) {
        static $allowSSL = null;

        if ($allowSSL === null) {
            $allowSSL = c('Garden.AllowSSL', null);
        }

        if (!$withDomain || $withDomain === '/') {
            return '';
        }

        if (!$allowSSL && $withDomain === 'https') {
            $withDomain = 'http';
        }

        if ($withDomain === true) {
            $withDomain = $this->scheme().'://';
        } elseif ($withDomain !== '//') {
            $withDomain .= '://';
        }

        return $withDomain.$this->hostAndPort();
    }


    /**
     * Gets/Sets the relative path to the application's dispatcher.
     *
     * @param string? $webRoot The new web root to set.
     * @return string
     */
    public function webRoot($webRoot = null) {
        $path = (string)$this->_parsedRequestElement('WebRoot', $webRoot);
        $webRootFromConfig = $this->_environmentElement('ConfigWebRoot');

        $removeWebRootConfig = $this->_environmentElement('ConfigStripUrls');
        if ($webRootFromConfig && $removeWebRootConfig) {
            $path = str_replace($webRootFromConfig, '', $path);
        }
        return $path;
    }

    /**
     * Chainable Superglobal arguments setter
     *
     * This method expects a variable number of parameters, each of which need to be a defined INPUT_*
     * constant, and will interpret these as superglobal references. These constants each refer to a
     * specific PHP superglobal and including them here causes their data to be imported into the request
     * object.
     *
     * @param self ::INPUT_*
     * @flow chain
     * @return Gdn_Request
     */
    public function withArgs() {
        $argAliasList = func_get_args();
        if (count($argAliasList)) {
            foreach ($argAliasList as $argAlias) {
                $this->_setRequestArguments(strtolower($argAlias));
            }
        }

        return $this;
    }

    /**
     * Chainable Custom arguments setter
     *
     * The request object allows for a custom array of data (that does not come from the request
     * itself) to be attached in front of the other request superglobals and transparently override
     * their values when they are requested via val(). This method sets that data.
     *
     * @param $customArgs key/value array of custom request argument data.
     * @flow chain
     * @return Gdn_Request
     */
    public function withCustomArgs($customArgs) {
        $this->_setRequestArguments(self::INPUT_CUSTOM, $customArgs);
        return $this;
    }

    /**
     * Chainable URI Setter, source is a controller + method + args list
     *
     * @param $controller Gdn_Controller Object or string controller name.
     * @param $method Optional name of the method to call. Omit or NULL for default (Index).
     * @param $args Optional argument list to forward to the method. Omit for none.
     * @flow chain
     * @return Gdn_Request
     */
    public function withControllerMethod($controller, $method = null, $args = []) {
        if (is_a($controller, 'Gdn_Controller')) {
            // Convert object to string
            $matches = [];
            preg_match('/^(.*)Controller$/', get_class($controller), $matches);
            $controller = $matches[1];
        }

        $method = is_null($method) ? 'index' : $method;
        $path = trim(implode('/', array_merge([$controller, $method], $args)), '/');
        $this->_environmentElement('URI', $path);
        return $this;
    }

    public function withDeliveryType($deliveryType) {
        $this->setValueOn(self::INPUT_GET, 'DeliveryType', $deliveryType);
        return $this;
    }

    public function withDeliveryMethod($deliveryMethod) {
        $this->setValueOn(self::INPUT_GET, 'DeliveryMethod', $deliveryMethod);
        return $this;
    }

    public function withRoute($route) {
        $parsedURI = Gdn::router()->getDestination($route);
        if ($parsedURI) {
            $this->_environmentElement('URI', $parsedURI);
        }
        return $this;
    }

    /**
     * Chainable URI Setter, source is a simple string
     *
     * @param $uri optional URI to set as as replacement for the REQUEST_URI superglobal value
     * @flow chain
     * @return Gdn_Request
     */
    public function withURI($uri = null) {
        $this->_environmentElement('URI', $uri);
        return $this;
    }
}
