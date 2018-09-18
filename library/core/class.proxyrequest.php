<?php
/**
 * ProxyRequest handler class.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.18
 */

/**
 * This class abstracts the work of doing external requests.
 */
class ProxyRequest {
    const MAX_LOG_BODYLENGTH = 500;

    protected $CookieJar;

    public $MaxReadSize = 4096;

    public $RequestDefaults;

    public $RequestHeaders;

    public $RequestBody;

    public $ParsedBody;

    public $ResponseHeaders;

    public $ResponseStatus;

    public $ResponseBody;

    public $ResponseTime;

    public $ContentType;

    public $ContentLength;

    public $ConnectionMode;

    protected $FileTransfer;

    protected $UseSSL;

    protected $SaveFile;

    public $ActionLog;

    protected $Options;

    /**
     * Set up ProxyRequest.
     *
     * Options:
     *   URL
     *   Host
     *   Method
     *   ConnectTimeout
     *   Timeout
     *   Redirects
     *   Cookies
     *   SaveAs
     *   CloseSession
     *   Redirected
     *   Debug
     *   Simulate
     *
     * @param boolean $loud
     * @param array $requestDefaults
     * @return type
     */
    public function __construct($loud = false, $requestDefaults = null) {
        $this->Loud = $loud;

        $cookieKey = md5(mt_rand(0, 72312189).microtime(true));
        if (defined('PATH_CACHE')) {
            $this->CookieJar = combinePaths([PATH_CACHE, "cookiejar.{$cookieKey}"]);
        } else {
            $this->CookieJar = combinePaths(["/tmp", "cookiejar.{$cookieKey}"]);
        }

        if (!is_array($requestDefaults)) {
            $requestDefaults = [];
        }
        $defaults = [
            'URL' => null,
            'Host' => null,
            'Method' => 'GET',
            'ConnectTimeout' => 5,
            'Timeout' => 5,
            'TransferMode' => 'normal',   // or 'binary'
            'SaveAs' => null,
            'Redirects' => true,
            'SSLNoVerify' => false,
            'PreEncodePost' => true,
            'Cookies' => true,       // Send my cookies?
            'CookieJar' => false,      // Create a cURL CookieJar?
            'CookieSession' => false,      // Should old cookies be trashed starting now?
            'CloseSession' => true,       // Whether to close the session. Should always do this.
            'Redirected' => false,      // Flag. Is this a redirected request?
            'Debug' => false,      // Debug output on?
            'Simulate' => false       // Don't actually request, just set up
        ];

        $this->RequestDefaults = array_merge($defaults, $requestDefaults);
    }

    /**
     *
     *
     * @param $handler
     * @param $headerString
     * @return int
     */
    public function curlHeader(&$handler, $headerString) {
        $line = explode(':', $headerString);
        $key = trim(array_shift($line));
        $value = trim(implode(':', $line));
        // Prevent overwriting existing $this->ResponseHeaders[$Key] entries.
        if (array_key_exists($key, $this->ResponseHeaders)) {
            if (!is_array($this->ResponseHeaders[$key])) {
                // Transform ResponseHeader to an array.
                $this->ResponseHeaders[$key] = [$this->ResponseHeaders[$key]];
            }
            $this->ResponseHeaders[$key][] = $value;
        } elseif (!empty($key)) {
            $this->ResponseHeaders[$key] = $value;
        }
        return strlen($headerString);
    }

    /**
     * @param $handler
     * @return mixed|string
     */
    protected function curlReceive(&$handler) {
        $startTime = microtime(true);
        $response = curl_exec($handler);
        $this->ResponseTime = microtime(true) - $startTime;

        if ($response == false) {
            $this->ResponseBody = curl_error($handler);
            $this->ResponseStatus = 400;
        } else {
            $this->ResponseStatus = curl_getinfo($handler, CURLINFO_HTTP_CODE);
            $this->ContentType = strtolower(curl_getinfo($handler, CURLINFO_CONTENT_TYPE));
            $this->ContentLength = (int)curl_getinfo($handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

            $requestHeaderInfo = trim(curl_getinfo($handler, CURLINFO_HEADER_OUT));
            $requestHeaderLines = explode("\n", $requestHeaderInfo);
            $request = trim(array_shift($requestHeaderLines));
            $this->RequestHeaders['HTTP'] = $request;
            // Parse header status line
            foreach ($requestHeaderLines as $line) {
                $line = explode(':', trim($line));
                $key = trim(array_shift($line));
                $value = trim(implode(':', $line));
                $this->RequestHeaders[$key] = $value;
            }
            $this->action(" Request Headers: " . print_r($this->RequestHeaders, true));

            if ($this->Options['TransferMode'] == 'normal') {
                $response = trim($response);
            }

            $this->ResponseBody = $response;

            if ($this->SaveFile) {
                $success = file_exists($this->SaveFile);
                $savedFileResponse = [
                    'Error' => curl_error($handler),
                    'Success' => $success,
                    'Size' => filesize($this->SaveFile),
                    'Time' => curl_getinfo($handler, CURLINFO_TOTAL_TIME),
                    'Speed' => curl_getinfo($handler, CURLINFO_SPEED_DOWNLOAD),
                    'Type' => curl_getinfo($handler, CURLINFO_CONTENT_TYPE),
                    'File' => $this->SaveFile
                ];
                $this->ResponseBody = json_encode($savedFileResponse);
            }
        }

        return $this->ResponseBody;
    }

    /**
     * Send a request and receive the response.
     *
     * Options:
     *     'URL'                  => NULL,
     *     'Host'                 => NULL,       // Override the Host: header
     *     'Method'               => 'GET',      // HTTP Method
     *     'ConnectTimeout'       => 5,          // Connection timeout
     *     'Timeout'              => 5,          // Request timeout
     *     'TransferMode'         => 'normal',   // or 'binary'
     *     'SaveAs'               => NULL,       // Download the response to this file
     *     'Redirects'            => TRUE,       // Allow 302 and 302 redirects
     *     'SSLNoVerify'          => FALSE,      // Verify the remote SSL cert
     *     'PreEncodePost'        => TRUE,       //
     *     'Cookies'              => TRUE,       // Send user's browser cookies?
     *     'CookieJar'            => FALSE,      // Create a cURL CookieJar?
     *     'CookieSession'        => FALSE,      // Should old cookies be trashed starting now?
     *     'CloseSession'         => TRUE,       // Whether to close the session. Should always do this.
     *     'Redirected'           => FALSE,      // Is this a redirected request?
     *     'Debug'                => FALSE,      // Debug output
     *     'Simulate'             => FALSE       // Don't actually request, just set up
     *     'ProtocolMask'         => Mask of CURLPROTO_* values
     *
     * @param array /string $options URL, or array options
     * @param array $queryParams GET/POST parameters
     * @param array $files List of files to upload
     * @param array $extraHeaders Any additional headers to tack on
     * @return type
     */
    public function request($options = null, $queryParams = null, $files = null, $extraHeaders = null) {

        // Allow requests that just want to use defaults to provide a string instead of an optionlist.
        if (is_string($options)) {
            $options = ['URL' => $options];
        }
        if (is_null($options)) {
            $options = [];
        }

        $this->Options = $options = array_merge($this->RequestDefaults, $options);

        $this->ResponseHeaders = [];
        $this->ResponseStatus = 0;
        $this->ResponseBody = "";
        $this->RequestBody = "";
        $this->ResponseTime = 0;
        $this->ContentLength = 0;
        $this->ContentType = '';
        $this->ConnectionMode = '';
        $this->ActionLog = [];

        if (is_string($files)) {
            $files = [$files];
        }
        if (!is_array($files)) {
            $files = [];
        }
        if (!is_array($extraHeaders)) {
            $extraHeaders = [];
        }

        // Get the URL
        $relativeURL = val('URL', $options, null);
        if (is_null($relativeURL)) {
            $relativeURL = val('Url', $options, null);
        }

        if (is_null($relativeURL)) {
            throw new Exception("No URL provided");
        }

        $requestMethod = val('Method', $options);

        $forceHost = val('Host', $options);
        $followRedirects = val('Redirects', $options);
        $connectTimeout = val('ConnectTimeout', $options);
        $timeout = val('Timeout', $options);
        $saveAs = val('SaveAs', $options);
        $transferMode = val('TransferMode', $options);
        $sSLNoVerify = val('SSLNoVerify', $options);
        $preEncodePost = val('PreEncodePost', $options);
        $sendCookies = val('Cookies', $options);
        $cookieJar = val('CookieJar', $options);
        $cookieSession = val('CookieSession', $options);
        $closeSesssion = val('CloseSession', $options);
        $redirected = val('Redirected', $options);
        $debug = val('Debug', $options, false);
        $simulate = val('Simulate', $options);
        $protocolMask = val('ProtocolMask', $options);

        $oldVolume = $this->Loud;
        if ($debug) {
            $this->Loud = true;
        }

        $url = $relativeURL;
        $postData = $queryParams;

        /*
         * If files were provided, preprocess the list and exclude files that don't
         * exist. Also, change the method to POST if it is currently GET and there are valid files to send.
         */

        $sendFiles = [];
        foreach ($files as $file => $filePath) {
            if (file_exists($filePath)) {
                $sendFiles[$file] = $filePath;
            }
        }

        $this->FileTransfer = (bool)sizeof($sendFiles);
        if ($this->FileTransfer && $requestMethod != "PUT") {
            $this->Options['Method'] = 'POST';
            $requestMethod = val('Method', $options);
        }

        /*
         * If extra headers were provided, preprocess the list into the correct
         * format for inclusion into both cURL and fsockopen header queues.
         */

        // Tack on Host header if forced
        if (!is_null($forceHost)) {
            $extraHeaders['Host'] = $forceHost;
        }

        $sendExtraHeaders = [];
        foreach ($extraHeaders as $extraHeader => $extraHeaderValue) {
            $sendExtraHeaders[] = "{$extraHeader}: {$extraHeaderValue}";
        }

        /*
         * If the request is being saved to a file, prepare to save to the
         * filesystem.
         */
        $this->SaveFile = false;
        if ($saveAs) {
            $savePath = dirname($saveAs);
            $canSave = @mkdir($savePath, 0775, true);
            if (!is_writable($savePath)) {
                throw new Exception("Cannot write to save path: {$savePath}");
            }

            $this->SaveFile = $saveAs;
        }

        /*
         * Parse Query Parameters and collapse into a querystring in the case of
         * GETs.
         */

        $requestMethod = strtoupper($requestMethod);
        switch ($requestMethod) {
            case 'PUT':
            case 'POST':
                break;

            case 'GET':
            default:
                $postData = is_array($postData) ? http_build_query($postData) : $postData;
                if (strlen($postData)) {
                    if (stristr($relativeURL, '?')) {
                        $url .= '&';
                    } else {
                        $url .= '?';
                    }
                    $url .= $postData;
                }
                break;
        }

        $this->action("Requesting {$url}");

        $urlParts = parse_url($url);

        // Extract scheme
        $scheme = strtolower(val('scheme', $urlParts, 'http'));
        $this->action(" scheme: {$scheme}");

        // Extract hostname
        $host = val('host', $urlParts, '');
        $this->action(" host: {$host}");

        // Extract / deduce port
        $port = val('port', $urlParts, null);
        if (empty($port)) {
            $port = ($scheme == 'https') ? 443 : 80;
        }
        $this->action(" port: {$port}");

        // Extract Path&Query
        $path = val('path', $urlParts, '');
        $query = val('query', $urlParts, '');
        $this->UseSSL = ($scheme == 'https') ? true : false;

        $this->action(" transfer mode: {$transferMode}");

        $logContext = [
            'requestUrl' => $url,
            'requestMethod' => $requestMethod
        ];

        /*
         * ProxyRequest can masquerade as the current user, so collect and encode
         * their current cookies as the default case is to send them.
         */

        $cookie = '';
        $encodeCookies = true;
        foreach ($_COOKIE as $key => $value) {
            if (!debug() && strncasecmp($key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($cookie) > 0) {
                $cookie .= '; ';
            }

            $encodedValue = ($encodeCookies) ? urlencode($value) : $value;
            $cookie .= "{$key}={$encodedValue}";
        }

        // This prevents problems for sites that use sessions.
        if ($closeSesssion) {
            @session_write_close();
        }

        $response = '';

        $this->action("Parameters: ".print_r($postData, true));

        // We need cURL
        if (!function_exists('curl_init')) {
            throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow cURL requests.');
        }

        $handler = curl_init();
        curl_setopt($handler, CURLOPT_HEADER, false);
        curl_setopt($handler, CURLINFO_HEADER_OUT, true);
        curl_setopt($handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($handler, CURLOPT_USERAGENT, val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/'.c('Vanilla.Version')));
        curl_setopt($handler, CURLOPT_CONNECTTIMEOUT, $connectTimeout);
        curl_setopt($handler, CURLOPT_HEADERFUNCTION, [$this, 'CurlHeader']);
        curl_setopt($handler, CURLOPT_PROTOCOLS, CURLPROTO_HTTP | CURLPROTO_HTTPS);

        if ($transferMode == 'binary') {
            curl_setopt($handler, CURLOPT_BINARYTRANSFER, true);
        }

        if ($requestMethod != 'GET' && $requestMethod != 'POST') {
            curl_setopt($handler, CURLOPT_CUSTOMREQUEST, $requestMethod);
        }

        if ($cookieJar) {
            curl_setopt($handler, CURLOPT_COOKIEJAR, $this->CookieJar);
            curl_setopt($handler, CURLOPT_COOKIEFILE, $this->CookieJar);
        }

        if ($cookieSession) {
            curl_setopt($handler, CURLOPT_COOKIESESSION, true);
        }

        if ($followRedirects) {
            curl_setopt($handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($handler, CURLOPT_AUTOREFERER, true);
            curl_setopt($handler, CURLOPT_MAXREDIRS, 10);
        }

        if ($this->UseSSL) {
            $this->action(" Using SSL");
            curl_setopt($handler, CURLOPT_SSL_VERIFYPEER, !$sSLNoVerify);
            curl_setopt($handler, CURLOPT_SSL_VERIFYHOST, $sSLNoVerify ? 0 : 2);
        }

        if ($protocolMask) {
            curl_setopt($handler, CURLOPT_PROTOCOLS, $protocolMask);
        }

        if ($timeout > 0) {
            curl_setopt($handler, CURLOPT_TIMEOUT, $timeout);
        }

        if ($cookie != '' && $sendCookies) {
            $this->action(" Sending client cookies");
            curl_setopt($handler, CURLOPT_COOKIE, $cookie);
        }

        if ($this->SaveFile) {
            $this->action(" Saving to file: {$this->SaveFile}");
            $fileHandle = fopen($this->SaveFile, 'w+');
            curl_setopt($handler, CURLOPT_FILE, $fileHandle);
        }

        // Allow POST
        if ($requestMethod == 'POST') {
            if ($this->FileTransfer) {
                $this->action(" POSTing files");
                foreach ($sendFiles as $file => $filePath) {
                    $postData[$file] = "@{$filePath}";
                }
            } else {
                if ($preEncodePost && is_array($postData)) {
                    $postData = http_build_query($postData);
                }
            }

            curl_setopt($handler, CURLOPT_POST, true);
            curl_setopt($handler, CURLOPT_POSTFIELDS, $postData);

            if (!is_array($postData) && !is_object($postData)) {
                $sendExtraHeaders['Content-Length'] = strlen($postData);
            }

            $this->RequestBody = $postData;
        }

        // Allow PUT
        if ($requestMethod == 'PUT') {
            if ($this->FileTransfer) {
                $sendFile = val('0', $sendFiles);
                $sendFileSize = filesize($sendFile);
                $this->action(" PUTing file: {$sendFile}");
                $sendFileObject = fopen($sendFile, 'r');

                curl_setopt($handler, CURLOPT_PUT, true);
                curl_setopt($handler, CURLOPT_INFILE, $sendFileObject);
                curl_setopt($handler, CURLOPT_INFILESIZE, $sendFileSize);

                $sendExtraHeaders[] = "Content-Length: {$sendFileSize}";
            } else {
                curl_setopt($handler, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($handler, CURLOPT_POSTFIELDS, $postData);

                if (!is_array($postData) && !is_object($postData)) {
                    $sendExtraHeaders['Content-Length'] = strlen($postData);
                } else {
                    $tempPostData = http_build_str($postData);
                    $sendExtraHeaders['Content-Length'] = strlen($tempPostData);
                }

                $this->RequestBody = $postData;
            }
        }

        // Allow HEAD
        if ($requestMethod == 'HEAD') {
            curl_setopt($handler, CURLOPT_HEADER, true);
            curl_setopt($handler, CURLOPT_NOBODY, true);
        }

        // Any extra needed headers
        if (sizeof($sendExtraHeaders)) {
            curl_setopt($handler, CURLOPT_HTTPHEADER, $sendExtraHeaders);
        }

        // Set URL
        curl_setopt($handler, CURLOPT_URL, $url);
        curl_setopt($handler, CURLOPT_PORT, $port);

        if (val('LogRequest', $options, false)) {
            Logger::event('http_request', Logger::INFO, '{requestMethod} {requestUrl}', $logContext);
        }

        $this->curlReceive($handler);

        if ($simulate) {
            return null;
        }

        curl_close($handler);


        $logContext['responseCode'] = $this->ResponseStatus;
        $logContext['responseTime'] = $this->ResponseTime;

        // Add the response body to the log entry if it isn't too long or we are debugging.
        $logResponseBody = val('LogResponseBody', $options, null);
        $logResponseBody = $logResponseBody === null ?
            !in_array($requestMethod, ['GET', 'OPTIONS']) && strlen($this->ResponseBody) < self::MAX_LOG_BODYLENGTH :
            $logResponseBody;

        if ($logResponseBody || debug() || (!$this->responseClass('2xx') && val('LogResponseErrorBody', $options, true))) {
            if (stripos($this->ContentType, 'application/json') !== false) {
                $body = @json_decode($this->ResponseBody, true);
                if (!$body) {
                    $body = $this->ResponseBody;
                }
                $logContext['responseBody'] = $body;
            } else {
                $logContext['responseBody'] = $this->ResponseBody;
            }
        }
        $logLevel = val('Log', $options, true) ? Logger::INFO : Logger::DEBUG;
        if (val('Log', $options, true)) {
            if ($this->responseClass('2xx')) {
                Logger::event('http_response', $logLevel, '{responseCode} {requestMethod} {requestUrl} in {responseTime}s', $logContext);
            } else {
                Logger::event('http_response_error', $logLevel, '{responseCode} {requestMethod} {requestUrl} in {responseTime}s', $logContext);
            }
        }

        $this->Loud = $oldVolume;
        return $this->ResponseBody;
    }

    /**
     *
     *
     * @param $message
     * @param null $loud
     */
    protected function action($message, $loud = null) {
        if ($this->Loud || $loud) {
            echo "{$message}\n";
            flush();
            ob_flush();
        }

        $this->ActionLog[] = $message;
    }

    /**
     *
     */
    public function __destruct() {
        if (file_exists($this->CookieJar)) {
            @unlink($this->CookieJar);
        }
    }

    /**
     *
     *
     * @return $this
     */
    public function clean() {
        return $this;
    }

    /**
     * Check if the provided response matches the provided response type
     *
     * Class is a string representation of the HTTP status code, with 'x' used
     * as a wildcard.
     *
     * Class '2xx' = All 200-level responses
     * Class '30x' = All 300-level responses up to 309
     *
     * @param string $class
     * @return boolean Whether the response matches or not
     */
    public function responseClass($class) {
        $code = (string)$this->ResponseStatus;
        if (is_null($code)) {
            return false;
        }
        if (strlen($code) != strlen($class)) {
            return false;
        }

        for ($i = 0; $i < strlen($class); $i++) {
            if ($class{$i} != 'x' && $class{$i} != $code{$i}) {
                return false;
            }
        }

        return true;
    }

    /**
     *
     *
     * @return mixed
     */
    public function headers() {
        return $this->ResponseHeaders;
    }

    /**
     *
     *
     * @return mixed
     */
    public function status() {
        return $this->ResponseStatus;
    }

    /**
     * Get request total time
     *
     * @return float
     */
    public function time() {
        return $this->ResponseTime;
    }

    /**
     *
     *
     * @return mixed
     */
    public function body() {
        return $this->ResponseBody;
    }
}
