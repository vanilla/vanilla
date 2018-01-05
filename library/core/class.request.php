<?php
/**
 * Incoming request parser.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */
use Garden\Web\RequestInterface;
use Vanilla\UploadedFile;

/**
 * Represents a Request to the application, typically from the browser but potentially generated internally, in a format
 * that can be accessed directly by the Dispatcher.
 *
 * @method string requestURI($uri = null) Get/Set the Request URI (REQUEST_URI).
 * @method string requestScript($scriptName = null) Get/Set the Request ScriptName (SCRIPT_NAME).
 * @method string requestMethod($method = null) Get/Set the Request Method (REQUEST_METHOD).
 * @method string requestHost($uri = null) Get/Set the Request Host (HTTP_HOST).
 * @method string requestFolder($folder = null) Get/Set the Request script's Folder.
 * @method string requestAddress($ip = null) Get/Set the Request IP address (first existing of HTTP_X_ORIGINALLY_FORWARDED_FOR,
 *                HTTP_X_CLUSTER_CLIENT_IP, HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, REMOTE_ADDR).
 */
class Gdn_Request implements RequestInterface {

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

    /** HTTP request method. */
    const METHOD_HEAD = 'HEAD';

    /** HTTP request method. */
    const METHOD_GET = 'GET';

    /** HTTP request method. */
    const METHOD_POST = 'POST';

    /** HTTP request method. */
    const METHOD_PUT = 'PUT';

    /** HTTP request method. */
    const METHOD_PATCH = 'PATCH';

    /** HTTP request method. */
    const METHOD_DELETE = 'DELETE';

    /** HTTP request method. */
    const METHOD_OPTIONS = 'OPTIONS';

    /** Special cases in $_SERVER that are also considered headers. */
    const SPECIAL_HEADERS = ['CONTENT_TYPE', 'CONTENT_LENGTH', 'PHP_AUTH_USER', 'PHP_AUTH_PW', 'PHP_AUTH_DIGEST', 'AUTH_TYPE'];

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
     * Instantiate a new instance of the {@link Gdn_Request} class.
     */
    public function __construct() {
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
     * @param $domain optional value to set
     * @return string | null
     */
    public function domain($domain = null) {
        return $this->_parsedRequestElement('Domain', $domain);
    }

    /**
     * Accessor method for unparsed request environment data, such as the REQUEST_URI, SCRIPT_NAME,
     * HTTP_HOST and REQUEST_METHOD keys in $_SERVER.
     *
     * A second argument can be supplied, which causes the value of the specified key to be changed
     * to that of the second parameter itself.
     *
     * Currently recognized keys (and their relation to $_SERVER) are:
     *  - URI      -> REQUEST_URI
     *  - SCRIPT   -> SCRIPT_NAME
     *  - HOST     -> HTTP_HOST
     *  - METHOD   -> REQUEST_METHOD
     *  - FOLDER   -> none. this is extracted from SCRIPT_NAME and only available after _ParseRequest()
     *  - SCHEME   -> none. this is derived from 'HTTPS' and 'X-Forwarded-Proto'
     *  - ADDRESS  -> first existing of HTTP_X_ORIGINALLY_FORWARDED_FOR, HTTP_X_CLUSTER_CLIENT_IP,
     *                HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, REMOTE_ADDR
     *
     * @param string $key Key to retrieve or set.
     * @param string $value Value of $Key key to set.
     * @return string | null
     */
    protected function _environmentElement($key, $value = null) {
        $key = strtoupper($key);
        if ($value !== null) {
            $this->_HaveParsedRequest = false;

            switch ($key) {
                case 'URI':
                    // Simulate REQUEST_URI decoding.
                    $value = !is_null($value) ? rawurldecode($value) : $value;
                    break;
                case 'SCRIPT':
                    $value = !is_null($value) ? trim($value, '/') : $value;
                    break;
                case 'HOST':
                    $hostParts = explode(':', $value);
                    $value = array_shift($hostParts);
                    break;
                case 'METHOD':
                    $value = strtoupper($value);
                    break;
                case 'SCHEME':
                case 'FOLDER':
                case 'ADDRESS':
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
     * Convenience method for accessing unparsed environment data via request(ELEMENT) method calls.
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
     * Mostly used in conjunction with fromImport()
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
     * at the end of the URI. In the example above, filename() would return 'cashflow2009.pdf'.
     *
     * @param $filename Optional Filename to set.
     * @return string
     */
    public function filename($filename = null) {
        return $this->_parsedRequestElement('Filename', $filename);
    }

    /**
     * Convert a header key from HTTP_HEADER_NAME format to Header-Name.
     *
     * @param string $key A header key.
     * @return string The formatted header key.
     */
    private function formatHeaderKey($key) {
        $key = $this->headerKey($key);
        if (substr($key, 0, 5) == 'HTTP_') {
            $key = substr($key, 5);
        }
        $key = strtolower($key);
        $key = str_replace('_', '-', $key);
        $key = preg_replace_callback('/(?<=^|\-)[a-z]/', function ($m) {
            return strtoupper($m[0]);
        }, $key);
        return $key;
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
     * Get the POST body of the request.
     *
     * @return array
     */
    public function getBody() {
        return (array)$this->getRequestArguments(self::INPUT_POST);
    }

    /**
     * Get the file extension of the request.
     *
     * @return string
     */
    public function getExt() {
        return (string)$this->_parsedRequestElement('Extension');
    }

    /**
     * Get the full path of the request.
     *
     * @return string;
     */
    public function getFullPath() {
        return $this->getRoot().$this->getPathExt();
    }

    /**
     * Get the hostname of the request.
     *
     * @return string
     */
    public function getHost() {
        return (string)$this->_environmentElement('HOST');
    }

    /**
     * {@inheritdoc}
     */
    public function getHeader($header) {
        return $this->getValueFrom(self::INPUT_SERVER, $this->headerKey($header), '');
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaderLine($name) {
        $value = $this->getHeader($name);
        if (empty($value)) {
            $value = '';
        } elseif (is_array($value)) {
            $value = implode(',', $value);
        }
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders() {
        $server = $this->getRequestArguments(self::INPUT_SERVER);

        $headers = [];
        foreach ($server as $name => $val) {
            if (substr($name, 0, 5) != 'HTTP_' && !in_array($name, self::SPECIAL_HEADERS)) {
                continue;
            }

            $name = $this->formatHeaderKey($name);
            $headers[$name] = $val;
        }
        return $headers;
    }

    /**
     * {@inheritdoc}
     */
    public function hasHeader($header) {
        return !empty($this->getHeader($header));
    }

    /**
     * Normalize a header name into a header key.
     *
     * @param string $name The name of the header.
     * @return string Returns a string in the form **HTTP_***.
     */
    private function headerKey($name) {
        $key = strtoupper(str_replace('-', '_', $name));
        if (substr($key, 0, 5) != 'HTTP_' && !in_array($key, self::SPECIAL_HEADERS)) {
            $key = 'HTTP_'.$key;
        }
        return $key;
    }

    /**
     * Get the host and port, but only if the port is not the standard port for the request scheme.
     *
     * @return string
     */
    public function getHostAndPort() {
        $host = $this->getHost();
        $port = $this->getPort();

        // Only append the port if it is non-standard.
        if (($port == 80 && $this->getScheme() === 'http') || ($port == 443 && $this->getScheme() === 'https')) {
            $port = '';
        } else {
            $port = ':'.$port;
        }

        return $host.$port;
    }

    /**
     * Get the IP address of the request.
     *
     * @return string;
     */
    public function getIP() {
        return (string)$this->_environmentElement('ADDRESS');
    }


    /**
     * Get the HTTP method.
     *
     * @return string Returns the HTTP method.
     */
    public function getMethod() {
        return $this->requestMethod();
    }

    /**
     * Set the HTTP method.
     *
     * @param string $method The new HTTP method.
     * @return $this
     */
    public function setMethod($method) {
        $this->requestMethod($method);
        return $this;
    }

    /**
     * Gets the request path.
     *
     * @return string
     */
    public function getPath() {
        $path = (string)$this->_parsedRequestElement('Path');
        if (strpos($path, '/') !== 0) {
            $path = "/{$path}";
        }

        return $path;
    }

    /**
     * Get the path and file extenstion.
     *
     * @return string
     */
    public function getPathExt() {
        $path = $this->getPath();
        $extension = $this->getExt();

        return $path.$extension;
    }

    /**
     * Gets the port.
     *
     * @return int
     */
    public function getPort() {
        return (int)$this->_environmentElement('PORT');
    }

    /**
     * Get the request query.
     *
     * @return array
     */
    public function getQuery() {
        return (array)$this->getRequestArguments(self::INPUT_GET);
    }

    /**
     * Get an item from the query string array.
     *
     * @return string
     */
    public function getQueryItem($key, $default = null) {
        return (string)$this->getValueFrom(self::INPUT_GET, $key, '');
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
     * Get the root directory of the request.
     *
     * @return string
     */
    public function getRoot() {
        $root = (string)$this->_parsedRequestElement('WebRoot');
        if (strpos($root, '/') !== 0) {
            $root = "/{$root}";
        }
        $root = rtrim($root, '/');

        return $root;
    }

    /**
     * Get the request scheme.
     *
     * @return string
     */
    public function getScheme() {
        return (string)$this->_environmentElement('SCHEME');
    }

    /**
     * Get the full url of the request.
     *
     * @return string
     */
    public function getUrl() {
        $scheme = $this->getScheme();
        $hostAndPort = $this->getHostAndPort();
        $fullPath = $this->getFullPath();

        $query = $this->getQuery();
        $queryString = (empty($query) ? '' : '?'.http_build_query($query));

        return "{$scheme}://{$hostAndPort}{$fullPath}{$queryString}";
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
     * Search one of the currently attached data arrays for the requested argument and return its value.
     *
     * @param string $paramType Which request argument array to query for this value. One of the **INPUT_*** constants.
     * @param string $key Name of the request argument to retrieve.
     * @param mixed $default Value to return if argument not found.
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
     * @return string | null
     */
    public function host($hostname = null) {
        return $this->_environmentElement('HOST', $hostname);
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
        return $this->_Environment['ADDRESS'];
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

        $transientKey = $this->post('TransientKey', $this->post('transientKey', $this->getHeader('X-Transient-Key')));
        $result = Gdn::session()->validateTransientKey($transientKey, false);

        if (!$result && $throw) {
            throw new Gdn_UserException(t('Invalid CSRF token.', 'Invalid CSRF token. Please try again.'), 403);
        }

        return $result;
    }

    /**
     * Check if request was a POST
     *
     * @return bool
     */
    public function isPostBack() {
        return $this->_environmentElement('METHOD') === 'POST';
    }

    /**
     * Gets/sets the port of the request.
     *
     * @param int $Port
     * @return int
     * @since 2.1
     */
    public function port($port = null) {
        return $this->_environmentElement('PORT', $port);
    }

    /**
     * Gets/Sets the scheme from the current url. e.g. "http" in
     * "http://foo.com/this/that/garden/index.php?/controller/action/"
     *
     * @param $scheme optional value to set.
     * @return string | null
     */
    public function scheme($scheme = null) {
        return $this->_environmentElement('SCHEME', $scheme);
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

        if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $host = $_SERVER['HTTP_X_FORWARDED_HOST'];
        } elseif (isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        } else {
            $host = val('SERVER_NAME', $_SERVER);
        }

        // The host can have the port passed in, remove it here if it exists
        $hostParts = explode(':', $host, 2);
        $host = $hostParts[0];

        $rawPort = null;
        if (count($hostParts) > 1) {
            $rawPort = $hostParts[1];
        }

        $this->_environmentElement('HOST', $host);
        $this->_environmentElement('METHOD', isset($_SERVER['REQUEST_METHOD']) ? val('REQUEST_METHOD', $_SERVER) : 'CONSOLE');

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
        $this->_environmentElement('ADDRESS', $ip);

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

        $this->_environmentElement('SCHEME', $scheme);

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

        $path = '';
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
        } elseif (is_array($_GET)) {
            if (isset($_GET['_p'])) {
                $path = $_GET['_p'];
                unset($_GET['_p']);
            } elseif (isset($_GET['p'])) {
                $path = $_GET['p'];
                unset($_GET['p']);
            }
        }
        // Set URI directly to avoid double decoding.
        $this->_Environment['URI'] = $path;

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

        $this->_environmentElement('FOLDER', '');
        foreach ($possibleScriptNames as $scriptName) {
            $script = basename($scriptName);
            $this->_environmentElement('SCRIPT', $script);

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
                $this->_environmentElement('FOLDER', ltrim($realFolder, '/'));
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
     * @return string | null
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
            //$this->path(implode('/',array_slice($UrlParts, 0, -1)));
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
     * @return string | null
     */
    public function path($path = null) {
        if (is_string($path)) {
            $result = $this->_parsedRequestElement('Path', ltrim($path, '/'));
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

//      $Filename = $this->filename();
//      if ($Filename && $Filename != 'default')
//         $Result .= concatSep('/', $Result, $Filename);
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
     * Merge an array of values into the request query.
     *
     * @param array $query
     * @return self
     */
    public function mergeQuery(array $query) {
        $current = $this->getQuery();
        $this->setQuery(array_merge($current, $query));

        return $this;
    }

    /**
     * Parse a PHP file array into a normalized array of UploadedFile objects.
     *
     * @param array $files A file array (e.g. $_FILES).
     * @return array
     */
    private function parseFiles(array $files) {
        /**
         * Normalize a multidimensional upload array (e.g. my-form[details][avatars][]).
         *
         * @param array $files
         * @return array
         */
        $normalizeArray = function(array $files) use (&$getUpload) {
            $result = [];
            foreach ($files['tmp_name'] as $key => $val) {
                // Consolidate the attributes and push them down the tree.
                $upload = $getUpload([
                    'error' => $files['error'][$key],
                    'name' => $files['name'][$key],
                    'size' => $files['size'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'type' => $files['type'][$key]
                ]);
                if ($upload instanceof UploadedFile && $upload->getError() === UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $result[$key] = $upload;
            }
            return $result;
        };

        /**
         * Create an instance of UploadedFile, or an array of instances, from a file array.
         *
         * @param array $value
         * @return array|UploadedFile
         */
        $getUpload = function(array $value) use (&$normalizeArray) {
            if (is_array($value['tmp_name'])) {
                // We need to go deeper.
                $result = $normalizeArray($value);
            } else {
                $result = new UploadedFile(
                    Gdn::getContainer()->get(Gdn_Upload::class),
                    $value['tmp_name'],
                    $value['size'],
                    $value['error'],
                    $value['name'],
                    $value['type']
                );
            }

            return $result;
        };

        $result = [];
        foreach ($files as $key => $value) {
            $upload = $getUpload($value);
            if ($upload instanceof UploadedFile && $upload->getError() === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $result[$key] = $upload;
        }
        return $result;
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
                $argumentData = $this->decodePost($_POST, $_SERVER, 'php://input', $_FILES);
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

    /**
     * Decode the environment's post depending on content type.
     *
     * @param array $post Usually the {@link $_POST} super-global.
     * @param array $server Usually the {@link $_SERVER} super-global.
     * @param string $inputFile Usually **php://input** for the raw input stream.
     */
    private function decodePost($post, $server, $inputFile = 'php://input', $files = null) {
        $contentType = !isset($server['CONTENT_TYPE']) ? 'application/x-www-form-urlencoded' : $server['CONTENT_TYPE'];

        if (stripos($contentType, 'application/json') !== false || stripos($contentType, 'text/plain') !== false) {
            // Decode the JSON from the content type.
            $result = json_decode(file_get_contents($inputFile), true);

            if ($result === null) {
                $result = $post;
            }
        } else {
            $result = $post;
        }

        // Add data from the PHP files array.
        if (is_array($files)) {
            $fileData = $this->parseFiles($files);
            $result = array_merge($fileData, $result);
        }

        return $result;
    }

    /**
     * Set the POST body for the request.
     *
     * @param array $body
     * @return self
     */
    public function setBody(array $body) {
        $this->setRequestArguments(self::INPUT_POST, $body);
        return $this;
    }

    /**
     * Sets the file extension of the request.
     *
     * @param string $extension
     * @return self
     */
    public function setExt($extension) {
        $extension = '.'.ltrim($extension, '.');

        $this->_parsedRequestElement('Extension', $extension);
        return $this;
    }

    /**
     * Set the full path of the request.
     *
     * @param string $fullPath
     * @return self
     */
    public function setFullPath($fullPath) {
        $fullPath = '/'.trim($fullPath, '/');

        // Try stripping the root out of the path first.
        $root = $this->getRoot();
        $rootStartsPath = (strpos($fullPath, $root) === 0);
        $canTrimRoot = (strlen($fullPath) === strlen($root) || substr($fullPath, strlen($root), 1) === '/');

        if ($root && $rootStartsPath && $canTrimRoot) {
            $pathWithoutRoot = substr($fullPath, strlen($root));
            $this->setPathExt($pathWithoutRoot);
        } else {
            $this->setRoot('');
            $this->setPathExt($fullPath);
        }

        return $this;
    }

    /**
     * Set the hostname of the request.
     *
     * @param string $host
     * @return self
     */
    public function setHost($host) {
        $this->_environmentElement('HOST', $host);
        return $this;
    }

    /**
     * Set the IP address of the request.
     *
     * @param string $ip
     * @return self
     */
    public function setIP($ip) {
        $this->_environmentElement('ADDRESS', $ip);
        return $this;
    }

    /**
     * Sets the request path.
     *
     * @param string $path
     * @return self
     */
    public function setPath($path) {
        $path = trim($path, '/');
        $this->_parsedRequestElement('Path', $path);
        return $this;
    }

    /**
     * Parse a path to separate and set the path and file extension of the request.
     *
     * @param string $path
     * @return self
     */
    public function setPathExt($path) {
        $info = pathinfo($path);

        if (isset($info['extension'])) {
            $this->setExt($info['extension']);
        }

        $path = ($info['dirname'] === '.' ? $info['filename'] : "{$info['dirname']}/{$info['filename']}");
        $this->setPath($path);

        return $this;
    }

    /**
     * Sets the port.
     *
     * @param int|string $port
     * @return self
     */
    public function setPort($port) {
        $port = intval($port);
        $this->_environmentElement('PORT', $port);

        // Override the scheme for standard ports.
        if ($port === 80) {
            $this->setScheme('http');
        } elseif ($port === 443) {
            $this->setScheme('https');
        }

        return $this;
    }

    /**
     * Sets the query for the request.
     *
     * @param array $query
     * @return self
     */
    public function setQuery(array $query) {
        $this->setRequestArguments(self::INPUT_GET, $query);
        return $this;
    }

    /**
     * Sets a value on the request's query.
     *
     * @param string $key
     * @param string $value
     * @return self
     */
    public function setQueryItem($key, $value) {
        $this->setValueOn(self::INPUT_GET, $key, $value);
        return $this;
    }

    public function setRequestArguments($paramsType, $paramsData) {
        $this->_RequestArguments[$paramsType] = $paramsData;
    }

    /**
     * Set the root directory of the request.
     *
     * @param string $root
     * @return self
     */
    public function setRoot($root) {
        $root = trim($root, '/');

        $this->_parsedRequestElement('WebRoot', $root);
        return $this;
    }

    /**
     * Set the request scheme.
     *
     * @param string $scheme
     * @return self
     */
    public function setScheme($scheme) {
        $this->_environmentElement('SCHEME', $scheme);
        return $this;
    }

    /**
     * Set the full URL of the request.
     *
     * @param string $url
     * @return Gdn_Request
     */
    public function setUrl($url) {
        // Parse a url and set its components.
        $components = parse_url($url);

        if ($components === false) {
            throw new \InvalidArgumentException('Invalid URL.');
        }

        if (isset($components['scheme'])) {
            $this->setScheme($components['scheme']);
        }

        if (isset($components['host'])) {
            $this->setHost($components['host']);
        }

        if (isset($components['port'])) {
            $this->setPort($components['port']);
        } elseif (isset($components['scheme'])) {
            $this->setPort($this->getScheme() === 'https' ? 443 : 80);
        }

        if (isset($components['path'])) {
            $this->setPathExt($components['path']);
        }

        if (isset($components['query'])) {
            parse_str($components['query'], $query);
            if (is_array($query)) {
                $this->setQuery($query);
            }
        }

        return $this;
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
            // Garden.RewriteUrls is maintained for compatibility but X_REWRITE is what really need to be used.
            $rewrite = val('X_REWRITE', $_SERVER, c('Garden.RewriteUrls', false));
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
        } else if ($withDomain && $withDomain !== '/') {
            $scheme = $this->scheme();
        }

        if (substr($path, 0, 2) == '//' || in_array(strpos($path, '://'), [4, 5])) { // Accounts for http:// and https:// - some querystring params may have "://", and this would cause things to break.
            return $path;
        }

        // Temporary strip out the hash.
        $hash = strchr($path, '#');
        if (strlen($hash) > 0) {
            $path = substr($path, 0, -strlen($hash));
        }

        // Temporary strip out the querystring.
        $query = strrchr($path, '?');
        if (strlen($query) > 0) {
            $path = substr($path, 0, -strlen($query));
        }

        // Having en empty string in here will prepend a / in front of the URL on implode.
        $parts = [''];
        if ($withDomain !== '/') {
            $port = $this->port();
            $host = $this->host();
            if (!in_array($port, [80, 443]) && (strpos($host, ':'.$port) === false)) {
                $host .= ':'.$port;
            }

            if ($withDomain === '//') {
                $parts = ['//'.$host];
            } elseif ($withDomain) {
                $parts = [$scheme.'://'.$host];
            }

            $webRoot = $this->webRoot();
            if ($webRoot != '') {
                $parts[] = $webRoot;
            }

            if (!$rewrite) {
                $parts[] = $this->_environmentElement('SCRIPT').'?p=';
                $query = str_replace('?', '&', $query);
            }
        }

        if ($path == '') {
            $path = $this->path(true);
            // Grab the get parameters too.
            if (!$query) {
                $query = http_build_query($this->getRequestArguments(self::INPUT_GET));
                if (!empty($query)) {
                    $query = ($rewrite ? '?' : '&').$query;
                }
            }
        }
        $parts[] = ltrim($path, '/');
        $result = implode('/', $parts);

        // Put back the query
        if ($query !== false) {
            $result .= $query;
        }

        // Put back the hash.
        if ($hash !== false) {
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
        if ($webRoot !== null || !$this->_HaveParsedRequest) {
            $path = (string)$this->_parsedRequestElement('WebRoot', $webRoot);
            $webRootFromConfig = $this->_environmentElement('ConfigWebRoot');

            $removeWebRootConfig = $this->_environmentElement('ConfigStripUrls');
            if ($webRootFromConfig && $removeWebRootConfig) {
                $path = str_replace($webRootFromConfig, '', $webRoot);
            }
        } else {
            $path = $this->_parsedRequestElement('WebRoot');
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
     * @param $method Optional name of the method to call. Omit or null for default (Index).
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
