<?php
/**
 * ProxyRequest handler class.
 *
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0.18
 */

/**
 * This class abstracts the work of doing external requests.
 */
class ProxyRequest {

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
     * @param boolean $Loud
     * @param array $RequestDefaults
     * @return type
     */
    public function __construct($Loud = false, $RequestDefaults = null) {
        $this->Loud = $Loud;

        $CookieKey = md5(mt_rand(0, 72312189).microtime(true));
        if (defined('PATH_CACHE')) {
            $this->CookieJar = CombinePaths(array(PATH_CACHE, "cookiejar.{$CookieKey}"));
        } else {
            $this->CookieJar = CombinePaths(array("/tmp", "cookiejar.{$CookieKey}"));
        }

        if (!is_array($RequestDefaults)) {
            $RequestDefaults = array();
        }
        $Defaults = array(
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
        );

        $this->RequestDefaults = array_merge($Defaults, $RequestDefaults);
    }

    /**
     *
     *
     * @param $Handler
     * @param $HeaderString
     * @return int
     */
    public function curlHeader(&$Handler, $HeaderString) {
        $Line = explode(':', trim($HeaderString));
        $Key = trim(array_shift($Line));
        $Value = trim(implode(':', $Line));
        if (!empty($Key)) {
            $this->ResponseHeaders[$Key] = $Value;
        }
        return strlen($HeaderString);
    }

    /**
     * @param $Handler
     * @return mixed|string
     */
    protected function curlReceive(&$Handler) {
        $this->ResponseHeaders = array();
        $startTime = microtime(true);
        $Response = curl_exec($Handler);
        $this->ResponseTime = microtime(true) - $startTime;

        $this->ResponseStatus = curl_getinfo($Handler, CURLINFO_HTTP_CODE);
        $this->ContentType = strtolower(curl_getinfo($Handler, CURLINFO_CONTENT_TYPE));
        $this->ContentLength = (int)curl_getinfo($Handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD);

        $RequestHeaderInfo = trim(curl_getinfo($Handler, CURLINFO_HEADER_OUT));
        $RequestHeaderLines = explode("\n", $RequestHeaderInfo);
        $Request = trim(array_shift($RequestHeaderLines));
        $this->RequestHeaders['HTTP'] = $Request;
        // Parse header status line
        foreach ($RequestHeaderLines as $Line) {
            $Line = explode(':', trim($Line));
            $Key = trim(array_shift($Line));
            $Value = trim(implode(':', $Line));
            $this->RequestHeaders[$Key] = $Value;
        }
        $this->action(" Request Headers: ".print_r($this->RequestHeaders, true));
        $this->action(" Response Headers: ".print_r($this->ResponseHeaders, true));

        if ($Response == false) {
            $Success = false;
            $this->ResponseBody = curl_error($Handler);
            return $this->ResponseBody;
        }

        if ($this->Options['TransferMode'] == 'normal') {
            $Response = trim($Response);
        }

        $this->ResponseBody = $Response;

        if ($this->SaveFile) {
            $Success = file_exists($this->SaveFile);
            $SavedFileResponse = array(
                'Error' => curl_error($Handler),
                'Success' => $Success,
                'Size' => filesize($this->SaveFile),
                'Time' => curl_getinfo($Handler, CURLINFO_TOTAL_TIME),
                'Speed' => curl_getinfo($Handler, CURLINFO_SPEED_DOWNLOAD),
                'Type' => curl_getinfo($Handler, CURLINFO_CONTENT_TYPE),
                'File' => $this->SaveFile
            );
            $this->ResponseBody = json_encode($SavedFileResponse);
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
     *
     * @param array /string $Options URL, or array options
     * @param array $QueryParams GET/POST parameters
     * @param array $Files List of files to upload
     * @param array $ExtraHeaders Any additional headers to tack on
     * @return type
     */
    public function request($Options = null, $QueryParams = null, $Files = null, $ExtraHeaders = null) {

        // Allow requests that just want to use defaults to provide a string instead of an optionlist.
        if (is_string($Options)) {
            $Options = array('URL' => $Options);
        }
        if (is_null($Options)) {
            $Options = array();
        }

        $this->Options = $Options = array_merge($this->RequestDefaults, $Options);

        $this->ResponseHeaders = array();
        $this->ResponseStatus = "";
        $this->ResponseBody = "";
        $this->RequestBody = "";
        $this->ResponseTime = 0;
        $this->ContentLength = 0;
        $this->ContentType = '';
        $this->ConnectionMode = '';
        $this->ActionLog = array();

        if (is_string($Files)) {
            $Files = array($Files);
        }
        if (!is_array($Files)) {
            $Files = array();
        }
        if (!is_array($ExtraHeaders)) {
            $ExtraHeaders = array();
        }

        // Get the URL
        $RelativeURL = val('URL', $Options, null);
        if (is_null($RelativeURL)) {
            $RelativeURL = val('Url', $Options, null);
        }

        if (is_null($RelativeURL)) {
            throw new Exception("No URL provided");
        }

        $RequestMethod = val('Method', $Options);
        $ForceHost = val('Host', $Options);
        $FollowRedirects = val('Redirects', $Options);
        $ConnectTimeout = val('ConnectTimeout', $Options);
        $Timeout = val('Timeout', $Options);
        $SaveAs = val('SaveAs', $Options);
        $TransferMode = val('TransferMode', $Options);
        $SSLNoVerify = val('SSLNoVerify', $Options);
        $PreEncodePost = val('PreEncodePost', $Options);
        $SendCookies = val('Cookies', $Options);
        $CookieJar = val('CookieJar', $Options);
        $CookieSession = val('CookieSession', $Options);
        $CloseSesssion = val('CloseSession', $Options);
        $Redirected = val('Redirected', $Options);
        $Debug = val('Debug', $Options, false);
        $Simulate = val('Simulate', $Options);

        $OldVolume = $this->Loud;
        if ($Debug) {
            $this->Loud = true;
        }

        $Url = $RelativeURL;
        $PostData = $QueryParams;

        /*
         * If files were provided, preprocess the list and exclude files that don't
         * exist. Also, change the method to POST if it is currently GET and there are valid files to send.
         */

        $SendFiles = array();
        foreach ($Files as $File => $FilePath) {
            if (file_exists($FilePath)) {
                $SendFiles[$File] = $FilePath;
            }
        }

        $this->FileTransfer = (bool)sizeof($SendFiles);
        if ($this->FileTransfer && $RequestMethod != "PUT") {
            $this->Options['Method'] = 'POST';
            $RequestMethod = val('Method', $Options);
        }

        /*
         * If extra headers were provided, preprocess the list into the correct
         * format for inclusion into both cURL and fsockopen header queues.
         */

        // Tack on Host header if forced
        if (!is_null($ForceHost)) {
            $ExtraHeaders['Host'] = $ForceHost;
        }

        $SendExtraHeaders = array();
        foreach ($ExtraHeaders as $ExtraHeader => $ExtraHeaderValue) {
            $SendExtraHeaders[] = "{$ExtraHeader}: {$ExtraHeaderValue}";
        }

        /*
         * If the request is being saved to a file, prepare to save to the
         * filesystem.
         */
        $this->SaveFile = false;
        if ($SaveAs) {
            $SavePath = dirname($SaveAs);
            $CanSave = @mkdir($SavePath, 0775, true);
            if (!is_writable($SavePath)) {
                throw new Exception("Cannot write to save path: {$SavePath}");
            }

            $this->SaveFile = $SaveAs;
        }

        /*
         * Parse Query Parameters and collapse into a querystring in the case of
         * GETs.
         */

        $RequestMethod = strtoupper($RequestMethod);
        switch ($RequestMethod) {
            case 'PUT':
            case 'POST':
                break;

            case 'GET':
            default:
                $PostData = is_array($PostData) ? http_build_query($PostData) : $PostData;
                if (strlen($PostData)) {
                    if (stristr($RelativeURL, '?')) {
                        $Url .= '&';
                    } else {
                        $Url .= '?';
                    }
                    $Url .= $PostData;
                }
                break;
        }

        $this->action("Requesting {$Url}");

        $UrlParts = parse_url($Url);

        // Extract scheme
        $Scheme = strtolower(val('scheme', $UrlParts, 'http'));
        $this->action(" scheme: {$Scheme}");

        // Extract hostname
        $Host = val('host', $UrlParts, '');
        $this->action(" host: {$Host}");

        // Extract / deduce port
        $Port = val('port', $UrlParts, null);
        if (empty($Port)) {
            $Port = ($Scheme == 'https') ? 443 : 80;
        }
        $this->action(" port: {$Port}");

        // Extract Path&Query
        $Path = val('path', $UrlParts, '');
        $Query = val('query', $UrlParts, '');
        $this->UseSSL = ($Scheme == 'https') ? true : false;

        $this->action(" transfer mode: {$TransferMode}");

        $logContext = array(
            'url' => $Url,
            'method' => $RequestMethod
        );

        /*
         * ProxyRequest can masquerade as the current user, so collect and encode
         * their current cookies as the default case is to send them.
         */

        $Cookie = '';
        $EncodeCookies = true;
        foreach ($_COOKIE as $Key => $Value) {
            if (strncasecmp($Key, 'XDEBUG', 6) == 0) {
                continue;
            }

            if (strlen($Cookie) > 0) {
                $Cookie .= '; ';
            }

            $EncodedValue = ($EncodeCookies) ? urlencode($Value) : $Value;
            $Cookie .= "{$Key}={$EncodedValue}";
        }

        // This prevents problems for sites that use sessions.
        if ($CloseSesssion) {
            @session_write_close();
        }

        $Response = '';

        $this->action("Parameters: ".print_r($PostData, true));

        // We need cURL
        if (!function_exists('curl_init')) {
            throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow cURL requests.');
        }

        $Handler = curl_init();
        curl_setopt($Handler, CURLOPT_HEADER, false);
        curl_setopt($Handler, CURLINFO_HEADER_OUT, true);
        curl_setopt($Handler, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($Handler, CURLOPT_USERAGENT, val('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
        curl_setopt($Handler, CURLOPT_CONNECTTIMEOUT, $ConnectTimeout);
        curl_setopt($Handler, CURLOPT_HEADERFUNCTION, array($this, 'CurlHeader'));

        if ($TransferMode == 'binary') {
            curl_setopt($Handler, CURLOPT_BINARYTRANSFER, true);
        }

        if ($RequestMethod != 'GET' && $RequestMethod != 'POST') {
            curl_setopt($Handler, CURLOPT_CUSTOMREQUEST, $RequestMethod);
        }

        if ($CookieJar) {
            curl_setopt($Handler, CURLOPT_COOKIEJAR, $this->CookieJar);
            curl_setopt($Handler, CURLOPT_COOKIEFILE, $this->CookieJar);
        }

        if ($CookieSession) {
            curl_setopt($Handler, CURLOPT_COOKIESESSION, true);
        }

        if ($FollowRedirects) {
            curl_setopt($Handler, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($Handler, CURLOPT_AUTOREFERER, true);
            curl_setopt($Handler, CURLOPT_MAXREDIRS, 10);
        }

        if ($this->UseSSL) {
            $this->action(" Using SSL");
            curl_setopt($Handler, CURLOPT_SSL_VERIFYPEER, !$SSLNoVerify);
            curl_setopt($Handler, CURLOPT_SSL_VERIFYHOST, $SSLNoVerify ? 0 : 2);
        }

        if ($Timeout > 0) {
            curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);
        }

        if ($Cookie != '' && $SendCookies) {
            $this->action(" Sending client cookies");
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);
        }

        if ($this->SaveFile) {
            $this->action(" Saving to file: {$this->SaveFile}");
            $FileHandle = fopen($this->SaveFile, 'w+');
            curl_setopt($Handler, CURLOPT_FILE, $FileHandle);
        }

        // Allow POST
        if ($RequestMethod == 'POST') {
            if ($this->FileTransfer) {
                $this->action(" POSTing files");
                foreach ($SendFiles as $File => $FilePath) {
                    $PostData[$File] = "@{$FilePath}";
                }
            } else {
                if ($PreEncodePost && is_array($PostData)) {
                    $PostData = http_build_query($PostData);
                }
            }

            curl_setopt($Handler, CURLOPT_POST, true);
            curl_setopt($Handler, CURLOPT_POSTFIELDS, $PostData);

            if (!is_array($PostData) && !is_object($PostData)) {
                $SendExtraHeaders['Content-Length'] = strlen($PostData);
            }

            $this->RequestBody = $PostData;
        }

        // Allow PUT
        if ($RequestMethod == 'PUT') {
            if ($this->FileTransfer) {
                $SendFile = val('0', $SendFiles);
                $SendFileSize = filesize($SendFile);
                $this->action(" PUTing file: {$SendFile}");
                $SendFileObject = fopen($SendFile, 'r');

                curl_setopt($Handler, CURLOPT_PUT, true);
                curl_setopt($Handler, CURLOPT_INFILE, $SendFileObject);
                curl_setopt($Handler, CURLOPT_INFILESIZE, $SendFileSize);

                $SendExtraHeaders[] = "Content-Length: {$SendFileSize}";
            } else {
                curl_setopt($Handler, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($Handler, CURLOPT_POSTFIELDS, $PostData);

                if (!is_array($PostData) && !is_object($PostData)) {
                    $SendExtraHeaders['Content-Length'] = strlen($PostData);
                } else {
                    $TempPostData = http_build_str($PostData);
                    $SendExtraHeaders['Content-Length'] = strlen($TempPostData);
                }

                $this->RequestBody = $PostData;
            }
        }

        // Any extra needed headers
        if (sizeof($SendExtraHeaders)) {
            curl_setopt($Handler, CURLOPT_HTTPHEADER, $SendExtraHeaders);
        }

        // Set URL
        curl_setopt($Handler, CURLOPT_URL, $Url);
        curl_setopt($Handler, CURLOPT_PORT, $Port);

        if (val('Log', $Options, true)) {
            Logger::event('http_request', Logger::DEBUG, '{method} {url}', $logContext);
        }

        $this->curlReceive($Handler);

        if ($Simulate) {
            return null;
        }

        curl_close($Handler);


        $logContext['responseCode'] = $this->ResponseStatus;
        $logContext['responseTime'] = $this->ResponseTime;
        if (debug()) {
            if ($this->ContentType == 'application/json') {
                $body = @json_decode($this->ResponseBody, true);
                if (!$body) {
                    $body = $this->ResponseBody;
                }
                $logContext['responseBody'] = $body;
            } else {
                $logContext['responseBody'] = $this->ResponseBody;
            }
        }
        if (val('Log', $Options, true)) {
            if ($this->responseClass('2xx')) {
                Logger::event('http_response', Logger::DEBUG, '{responseCode} {method} {url} in {responseTime}s', $logContext);
            } else {
                Logger::event('http_response_error', Logger::DEBUG, '{responseCode} {method} {url} in {responseTime}s', $logContext);
            }
        }

        $this->Loud = $OldVolume;
        return $this->ResponseBody;
    }

    /**
     *
     *
     * @param $Message
     * @param null $Loud
     */
    protected function action($Message, $Loud = null) {
        if ($this->Loud || $Loud) {
            echo "{$Message}\n";
            flush();
            ob_flush();
        }

        $this->ActionLog[] = $Message;
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
     * @param string $Class
     * @return boolean Whether the response matches or not
     */
    public function responseClass($Class) {
        $Code = (string)$this->ResponseStatus;
        if (is_null($Code)) {
            return false;
        }
        if (strlen($Code) != strlen($Class)) {
            return false;
        }

        for ($i = 0; $i < strlen($Class); $i++) {
            if ($Class{$i} != 'x' && $Class{$i} != $Code{$i}) {
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
     *
     *
     * @return mixed
     */
    public function body() {
        return $this->ResponseBody;
    }
}
