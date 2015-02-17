<?php if (!defined('APPLICATION')) exit();

/**
 * Incoming request parser
 *
 * Represents a Request to the application, typically from the browser but potentially generated internally, in a format
 * that can be accessed directly by the Dispatcher.
 *
 * @method string RequestURI($URI = NULL) Get/Set the Request URI (REQUEST_URI).
 * @method string RequestScript($ScriptName = NULL) Get/Set the Request ScriptName (SCRIPT_NAME).
 * @method string RequestMethod($Method = NULL) Get/Set the Request Method (REQUEST_METHOD).
 * @method string RequestHost($URI = NULL) Get/Set the Request Host (HTTP_HOST).
 * @method string RequestFolder($URI = NULL) Get/Set the Request script's Folder.
 *
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_Request {

   const INPUT_CUSTOM   = "custom";
   const INPUT_ENV      = "env";
   const INPUT_FILES    = "files";
   const INPUT_GET      = "get";
   const INPUT_POST     = "post";
   const INPUT_SERVER   = "server";
   const INPUT_COOKIES  = "cookies";

   protected $_HaveParsedRequest = FALSE; // Bool, signifies whether or not _ParseRequest has been called yet.
   protected $_Environment;               // Raw environment variables, unparsed
   protected $_ParsedRequest;             // Resolved/parsed request information
   protected $_Parsing = FALSE;
   protected $_RequestArguments;          // Request data/parameters, either from superglobals or from a custom array of key/value pairs

   private function __construct() {
      $this->Reset();
   }

   /**
    * Gets/Sets the relative path to the asset include path.
    *
    * The asset root represents the folder that static assets are served from.
    *
    * @param $AssetRoot Optional An asset root to set
    * @return string Returns the current asset root.
    */
   public function AssetRoot($AssetRoot = null) {
      if ($AssetRoot !== null) {
         $Result = $this->_ParsedRequestElement('AssetRoot', rtrim('/'.trim($AssetRoot, '/'), '/'));
      } else {
         $Result = $this->_ParsedRequestElement('AssetRoot');
      }
      return $Result;
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
   public static function Create() {
      return new Gdn_Request();
   }

   /**
    * Gets/Sets the domain from the current url. e.g. "http://localhost" in
    * "http://localhost/this/that/garden/index.php?/controller/action/"
    *
    * @param $Domain optional value to set
    * @return string | NULL
    */
   public function Domain($Domain = NULL) {
      return $this->_ParsedRequestElement('Domain', $Domain);
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
    *
    * @param $Key Key to retrieve or set.
    * @param $Value Value of $Key key to set.
    * @return string | NULL
    */
   protected function _EnvironmentElement($Key, $Value=NULL) {
      $Key = strtoupper($Key);
      if ($Value !== NULL) {
         $this->_HaveParsedRequest = FALSE;

         switch ($Key) {
            case 'URI':
               $Value = !is_null($Value) ? urldecode($Value) : $Value;
               break;
            case 'SCRIPT':
               $Value = !is_null($Value) ? trim($Value, '/') : $Value;
               break;
            case 'HOST':
               $HostParts = explode(':', $Value);
               $Value = array_shift($HostParts);
               break;
            case 'SCHEME':
            case 'METHOD':
            case 'FOLDER':
            default:
               // Do nothing special for these
            break;
         }

         $this->_Environment[$Key] = $Value;
      }

      if (array_key_exists($Key, $this->_Environment))
         return $this->_Environment[$Key];

      return NULL;
   }

   /**
    * Convenience method for accessing unparsed environment data via Request(ELEMENT) method calls.
    *
    * @return string
    */
   public function __call($Method, $Args) {
      $Matches = array();
      if (preg_match('/^(Request)(.*)$/i',$Method,$Matches)) {
         $PassedArg = (is_array($Args) && sizeof($Args)) ? $Args[0] : NULL;
         return $this->_EnvironmentElement(strtoupper($Matches[2]),$PassedArg);
      }
      else {
         trigger_error("Call to unknown method 'Gdn_Request->{$Method}'", E_USER_ERROR);
      }
   }

   /**
    * This method allows requests to export their internal data.
    *
    * Mostly used in conjunction with FromImport()
    *
    * @param $Export Data group to export
    * @return mixed
    */
   public function Export($Export) {
      switch ($Export) {
         case 'Environment':  return $this->_Environment;
         case 'Arguments':    return $this->_RequestArguments;
         case 'Parsed':       return $this->_ParsedRequest;
         default:             return NULL;
      }
   }

   /**
    * Gets/Sets the optional filename (ContentDisposition) of the output.
    *
    * As with the case above (OutputFormat), this value depends heavily on there being a filename
    * at the end of the URI. In the example above, Filename() would return 'cashflow2009.pdf'.
    *
    * @param $Filename Optional Filename to set.
    * @return string
    */
   public function Filename($Filename = NULL) {
      return $this->_ParsedRequestElement('Filename', $Filename);
   }

   /**
    * Chainable lazy Environment Bootstrap
    *
    * Convenience method allowing quick setup of the default request state... from the current environment.
    *
    * @flow chain
    * @return Gdn_Request
    */
   public function FromEnvironment() {
      $this->WithURI()
         ->WithArgs(self::INPUT_GET, self::INPUT_POST, self::INPUT_SERVER, self::INPUT_FILES, self::INPUT_COOKIES);

      return $this;
   }

   /**
    * Chainable Request Importer
    *
    * This method allows one method to import the raw information of another request
    *
    * @param $NewRequest New Request from which to import environment and arguments.
    * @flow chain
    * @return Gdn_Request
    */
   public function FromImport($NewRequest) {
      // Import Environment
      $this->_Environment = $NewRequest->Export('Environment');
      // Import Arguments
      $this->_RequestArguments = $NewRequest->Export('Arguments');

      $this->_HaveParsedRequest = FALSE;
      $this->_Parsing = FALSE;
      return $this;
   }

   /**
    * Get a value from the GET array or return the entire GET array.
    *
    * @param string|null $Key The key of the get item or null to return the entire get array.
    * @param mixed $Default The value to return if the item isn't set.
    * @return mixed
    */
   public function Get($Key = NULL, $Default = NULL) {
      if ($Key === NULL)
         return $this->GetRequestArguments (self::INPUT_GET);
      else
         return $this->GetValueFrom(self::INPUT_GET, $Key, $Default);
   }

   /**
    * Export an entire dataset (effectively, one of the superglobals) from the request arguments list
    *
    * @param int $ParamType Type of data to export. One of the self::INPUT_* constants
    * @return array
    */
   public function GetRequestArguments($ParamType = NULL) {
      if ($ParamType === NULL)
         return $this->_RequestArguments;
      elseif (!isset($this->_RequestArguments[$ParamType]))
         return array();
      else
         return $this->_RequestArguments[$ParamType];
   }

   /**
    * Search the currently attached data arrays for the requested argument (in order) and
    * return the first match. Return $Default if not found.
    *
    * @param string $Key Name of the request argument to retrieve.
    * @param mixed $Default Value to return if argument not found.
    * @return mixed
    */
   public function GetValue($Key, $Default = FALSE) {
      return $this->Merged($Key, $Default);
   }

   /**
    * Search one of the currently attached data arrays for the requested argument and return its value
    * or $Default if not found.
    *
    * @param $ParamType Which request argument array to query for this value. One of the self::INPUT_* constants
    * @param $Key Name of the request argument to retrieve.
    * @param $Default Value to return if argument not found.
    * @return mixed
    */
   public function GetValueFrom($ParamType, $Key, $Default = FALSE) {
      $ParamType = strtolower($ParamType);

      if (array_key_exists($ParamType, $this->_RequestArguments) && array_key_exists($Key, $this->_RequestArguments[$ParamType])) {
         $Val = $this->_RequestArguments[$ParamType][$Key];
         if (is_array($Val) || is_object($Val))
            return $Val;
         else
            return $Val;
      }
      return $Default;
   }

   /**
    * Gets/Sets the host from the current url. e.g. "foo.com" in
    * "http://foo.com/this/that/garden/index.php?/controller/action/"
    *
    * @param $HostName optional value to set.
    * @return string | NULL
    */
   public function Host($Hostname = NULL) {
      return $this->RequestHost($Hostname);
   }

   /**
    * Return the host and port together if the port isn't standard.
    * @return string
    * @since 2.1
    */
   public function HostAndPort() {
      $Host = $this->Host();
      $Port = $this->Port();
      if (!in_array($Port, array(80, 443)))
         return $Host.':'.$Port;
      else
         return $Host;
   }

   public function IpAddress() {
      return $this->RequestAddress();
   }

   /*
    * Returns a boolean value indicating if the current page has an authenticated postback.
    * @return type
    * @since 2.1
    */
   public function IsAuthenticatedPostBack() {
      if (!$this->IsPostBack())
         return FALSE;

      $PostBackKey = Gdn::Request()->Post('TransientKey', FALSE);
      return Gdn::Session()->ValidateTransientKey($PostBackKey, FALSE);
   }

   public function IsPostBack() {
      return strcasecmp($this->RequestMethod(), 'post') == 0;
   }

   /**
    * Gets/sets the port of the request.
    * @param int $Port
    * @return int
    * @since 2.1
    */
   public function Port($Port = NULL) {
      return $this->_EnvironmentElement('PORT', $Port);
   }

   /**
    * Gets/Sets the scheme from the current url. e.g. "http" in
    * "http://foo.com/this/that/garden/index.php?/controller/action/"
    *
    * @param $Scheme optional value to set.
    * @return string | NULL
    */
   public function Scheme($Scheme = NULL) {
      return $this->RequestScheme($Scheme);
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
   protected function _LoadEnvironment() {
      $this->_EnvironmentElement('ConfigWebRoot', Gdn::Config('Garden.WebRoot'));
      $this->_EnvironmentElement('ConfigStripUrls', Gdn::Config('Garden.StripWebRoot', FALSE));

      if (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
         $Host = $_SERVER['HTTP_X_FORWARDED_HOST'];
      } elseif (isset($_SERVER['HTTP_HOST'])) {
         $Host = $_SERVER['HTTP_HOST'];
      } else {
         $Host = val('SERVER_NAME', $_SERVER);
      }

      // The host can have the port passed in, remove it here if it exists
      $Host = explode(':', $Host, 2);
      $Host = $Host[0];

      $this->RequestHost($Host);
      $this->RequestMethod(isset($_SERVER['REQUEST_METHOD']) ? val('REQUEST_METHOD',$_SERVER) : 'CONSOLE');

      // Request IP

      // Loadbalancers
      if ($TestIP = val('HTTP_X_CLUSTER_CLIENT_IP', $_SERVER)) {
         $IP = $TestIP;
      } elseif ($TestIP = val('HTTP_CLIENT_IP', $_SERVER)) {
         $IP = $TestIP;
      } elseif ($TestIP = val('HTTP_X_FORWARDED_FOR', $_SERVER)) {
         $IP = $TestIP;
      } else {
         $IP = val('REMOTE_ADDR', $_SERVER);
      }

      if (strpos($IP, ',') !== FALSE) {
         $Matched = preg_match_all('/([\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})(?:, )?/i', $IP, $Matches);

         // If we found matching IPs
         if ($Matched) {
            $IPs = $Matches[1];
            $IP = $IPs[0];

         // Fallback
         } else { $IP = $_SERVER['REMOTE_ADDR']; }
      }

      // Varnish
      $OriginalIP = val('HTTP_X_ORIGINALLY_FORWARDED_FOR', $_SERVER, NULL);
      if (!is_null($OriginalIP)) $IP = $OriginalIP;

      $IP = ForceIPv4($IP);
      $this->RequestAddress($IP);

      // Request Scheme

      $Scheme = 'http';
      // Webserver-originated SSL
      if (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on') $Scheme = 'https';
      // Loadbalancer-originated (and terminated) SSL
      if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) == 'https') $Scheme = 'https';
      // Varnish
      $OriginalProto = val('HTTP_X_ORIGINALLY_FORWARDED_PROTO', $_SERVER, NULL);
      if (!is_null($OriginalProto)) $Scheme = $OriginalProto;

      $this->RequestScheme($Scheme);

      if (isset($_SERVER['SERVER_PORT']))
         $Port = $_SERVER['SERVER_PORT'];
      elseif ($Scheme === 'https')
         $Port = 443;
      else
         $Port = 80;
      $this->Port($Port);

      if (is_array($_GET)) {
         $Get = FALSE;
         if ($Get === FALSE) $Get =& $_GET;
         if (!is_array($Get)) {
            $Original = array();
            parse_str($Get, $Original);
            SafeParseStr($Get, $Get, $Original);
         }

         if (isset($Get['_p'])) {
            $Path = $Get['_p'];
            unset($_GET['_p']);
         } elseif (isset($Get['p'])) {
            $Path = $Get['p'];
            unset($_GET['p']);
         } else {
            $Path = '';
         }

         $this->RequestURI($Path);
      }

      $PossibleScriptNames = array();
      if (isset($_SERVER['SCRIPT_NAME']))
         $PossibleScriptNames[] = $_SERVER['SCRIPT_NAME'];

      if (isset($_ENV['SCRIPT_NAME']))
         $PossibleScriptNames[] = $_ENV['SCRIPT_NAME'];

      if (PHP_SAPI === 'cgi' && isset($_ENV['SCRIPT_URL']))
         $PossibleScriptNames[] = $_ENV['SCRIPT_URL'];

      if (isset($_SERVER['SCRIPT_FILENAME']))
         $PossibleScriptNames[] = $_SERVER['SCRIPT_FILENAME'];

      if (isset($_SERVER['ORIG_SCRIPT_NAME']))
         $PossibleScriptNames[] = $_SERVER['ORIG_SCRIPT_NAME'];

      $this->RequestFolder('');
      $TrimURI = trim($this->RequestURI(),'/');
      foreach ($PossibleScriptNames as $ScriptName) {
         $Script = basename($ScriptName);
         $this->RequestScript($Script);

         $Folder = substr($ScriptName,0,0-strlen($Script));
         $TrimFolder = trim($Folder,'/');
         $TrimScript = trim($Script,'/');

         if (isset($_SERVER['DOCUMENT_ROOT']))
            $DocumentRoot = $_SERVER['DOCUMENT_ROOT'];
         else {
            $AbsolutePath = str_replace("\\","/",realpath($Script));
            $DocumentRoot = substr($AbsolutePath,0,strpos($AbsolutePath,$ScriptName));
         }

         if (!$DocumentRoot) continue;
         $TrimRoot = rtrim($DocumentRoot);
         $RealFolder = str_replace($TrimRoot,'', $Folder);

         if (!empty($RealFolder)) {
            $this->RequestFolder(ltrim($RealFolder,'/'));
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
    * @param $OutputFormat Optional OutputFormat to set.
    * @return string | NULL
    */
   public function OutputFormat($OutputFormat = NULL) {
      $OutputFormat = (!is_null($OutputFormat)) ? strtolower($OutputFormat) : $OutputFormat;
      return $this->_ParsedRequestElement('OutputFormat', $OutputFormat);
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
   protected function _ParseRequest() {
      $this->_Parsing = TRUE;

      /**
       * Resolve final request to send to dispatcher
       */

      $Path = $this->_EnvironmentElement('URI');

      // Get the dispatch string from the URI
      if($Path !== FALSE) {
         $this->Path(trim($Path, '/'));
      } else {
         $Expression = '/^(?:\/?'.str_replace('/', '\/', $this->_EnvironmentElement('Folder')).')?(?:'.$this->_EnvironmentElement('Script').')?\/?(.*?)\/?(?:[#?].*)?$/i';
         if (preg_match($Expression, $this->_EnvironmentElement('URI'), $Match))
            $this->Path($Match[1]);
         else
            $this->Path('');
      }

      /**
       * Resolve optional output modifying file extensions (rss, json, etc)
       */

      $UrlParts = explode('/', $this->Path());
      $Last = array_slice($UrlParts, -1, 1);
      $LastParam = array_pop($Last);
      $Match = array();
      if (preg_match('/^(.+)\.([^.]{1,4})$/', $LastParam, $Match)) {
         $this->OutputFormat($Match[2]);
         $this->Filename($Match[0]);
         //$this->Path(implode('/',array_slice($UrlParts, 0, -1)));
      }

      /**
       * Resolve WebRoot
       */

      // Attempt to get the webroot from the server
      $WebRoot = FALSE;
      if (!$WebRoot) {
         $WebRoot = explode('/', val('PHP_SELF', $_SERVER, ''));

         // Look for index.php to figure out where the web root is.
         $Key = array_search('index.php', $WebRoot);
         if ($Key !== FALSE) {
            $WebRoot = implode('/', array_slice($WebRoot, 0, $Key));
         } else {
            // Could not determine webroot.
            $WebRoot = '';
         }

      }

      $ParsedWebRoot = trim($WebRoot,'/');
      $this->WebRoot($ParsedWebRoot);
      $this->AssetRoot($ParsedWebRoot);

      /**
       * Resolve Domain
       */

      $Domain = FALSE;
      if ($Domain === FALSE || $Domain == '')
         $Domain = $this->HostAndPort();

      if ($Domain != '' && $Domain !== FALSE) {
         if (!stristr($Domain,'://'))
            $Domain = $this->Scheme().'://'.$Domain;

         $Domain = trim($Domain, '/');
      }
      $this->Domain($Domain);

      $this->_Parsing = FALSE;
      $this->_HaveParsedRequest = TRUE;
   }

   /**
    * Accessor method for parsed request data, such as the final 'controller/method' string,
    * as well as the resolved output format such as 'rss' or 'default'.
    *
    * A second argument can be supplied, which causes the value of the specified key to be changed
    * to that of the second parameter itself.
    *
    * @param string $Key element key to retrieve or set
    * @param string $Value value of $Key key to set
    * @return string|null
    */
   protected function _ParsedRequestElement($Key, $Value = NULL) {
      // Lazily parse if not already parsed
      if (!$this->_HaveParsedRequest && !$this->_Parsing)
         $this->_ParseRequest();

      if ($Value !== NULL)
         $this->_ParsedRequest[$Key] = $Value;

      if (array_key_exists($Key, $this->_ParsedRequest))
         return $this->_ParsedRequest[$Key];

      return NULL;
   }

   /**
    * Gets/Sets the final path to be sent to the dispatcher.
    *
    * @param string|true|null $Path Optional Path to set
    *  - string: Set a new path.
    *  - true: Url encode the returned path.
    *  - null: Return the path.
    * @return string | NULL
    */
   public function Path($Path = NULL) {
      if (is_string($Path)) {
         $Result = $this->_ParsedRequestElement('Path', $Path);
      } else {
         $Result = $this->_ParsedRequestElement('Path');
         if ($Path === TRUE) {
            // Encode the path.
            $Parts = explode('/', $Result);
            $Parts = array_map('urlencode', $Parts);
            $Result = implode('/', $Parts);
         }
      }

      return $Result;
   }

   public function PathAndQuery($PathAndQuery = NULL) {
      // Set the path and query if it is supplied.
      if ($PathAndQuery) {
         // Parse out the path into parts.
         $Parts = parse_url($PathAndQuery);
         $Path = val('path', $Parts, '');

         // Check for a filename.
         $Filename = basename($Path);
         if (strpos($Filename, '.') === FALSE)
            $Filename = 'default';
         $Path = trim($Path, '/');

         $Query = val('query', $Parts, '');
         if (strlen($Query) > 0) {
            parse_str($Query, $Get);
         } else {
            $Get = array();
         }

         // Set the parts of the query here.
         if (!$this->_HaveParsedRequest)
            $this->_ParseRequest();
         $this->_ParsedRequest['Path'] = $Path;
         $this->_ParsedRequest['Filename'] = $Filename;
         $this->_RequestArguments[self::INPUT_GET] = $Get;
      }

      // Construct the path and query.
      $Result = $this->Path();

//      $Filename = $this->Filename();
//      if ($Filename && $Filename != 'default')
//         $Result .= ConcatSep('/', $Result, $Filename);
      $Get = $this->GetRequestArguments(self::INPUT_GET);
      if (count($Get) > 0) {
         // mosullivan 2011-05-04 - There is a bug in this code that causes a qs
         // param to be present in the path, which makes appending with a ?
         // invalid. This code is too nasty to figure out. Kludge.
         $Result .= strpos($Result, '?') === FALSE ? '?' : '&';
         $Result .= http_build_query($Get);
      }

      return $Result;
   }

   /**
    * Get a value from the post array or return the entire POST array.
    *
    * @param string|null $Key The key of the post item or null to return the entire array.
    * @param mixed $Default The value to return if the item isn't set.
    * @return mixed
    */
   public function Post($Key = NULL, $Default = NULL) {
      if ($Key === NULL)
         return $this->GetRequestArguments (self::INPUT_POST);
      else
         return $this->GetValueFrom(self::INPUT_POST, $Key, $Default);
   }

   public function Reset() {
      $this->_Environment        = array();
      $this->_RequestArguments   = array();
      $this->_ParsedRequest      = array(
            'Path'               => '',
            'OutputFormat'       => 'default',
            'Filename'           => 'default',
            'WebRoot'            => '',
            'Domain'             => ''
      );
      $this->_LoadEnvironment();
   }

   /**
    * Get a value from the merged param array or return the entire merged array
    *
    * @param string|null $Key The key of the post item or null to return the entire array.
    * @param mixed $Default The value to return if the item isn't set.
    * @return mixed
    */
   public function Merged($Key = NULL, $Default = NULL) {
      $Merged = array();
      $QueryOrder = array(
         self::INPUT_CUSTOM,
         self::INPUT_GET,
         self::INPUT_POST,
         self::INPUT_FILES,
         self::INPUT_SERVER,
         self::INPUT_ENV,
         self::INPUT_COOKIES
      );
      $NumDataTypes = sizeof($QueryOrder);
      for ($i=$NumDataTypes; $i > 0; $i--) {
         $DataType = $QueryOrder[$i-1];
         if (!array_key_exists($DataType, $this->_RequestArguments)) continue;
         $Merged = array_merge($Merged, $this->_RequestArguments[$DataType]);
      }

      return (is_null($Key)) ? $Merged : val($Key, $Merged, $Default);
   }

   /**
    * Attach an array of request arguments to the request.
    *
    * @param int $ParamsType type of data to import. One of the self::INPUT_* constants
    * @param array $ParamsData optional data array to import if ParamsType is INPUT_CUSTOM
    * @return void
    */
   protected function _SetRequestArguments($ParamsType, $ParamsData = NULL) {
      switch ($ParamsType) {
         case self::INPUT_GET:
            $ArgumentData = $_GET;
            break;

         case self::INPUT_POST:
            $ArgumentData = $_POST;
            break;

         case self::INPUT_SERVER:
            $ArgumentData = $_SERVER;
            break;

         case self::INPUT_FILES:
            $ArgumentData = $_FILES;
            break;

         case self::INPUT_ENV:
            $ArgumentData = $_ENV;
            break;

         case self::INPUT_COOKIES:
            $ArgumentData = $_COOKIE;
            break;

         case self::INPUT_CUSTOM:
            $ArgumentData = is_array($ParamsData) ? $ParamsData : array();
            break;

      }
      $this->_RequestArguments[$ParamsType] = $ArgumentData;
   }

   public function SetRequestArguments($ParamsType, $ParamsData) {
      $this->_RequestArguments[$ParamsType] = $ParamsData;
   }

   public function SetValueOn($ParamType, $ParamName, $ParamValue) {
      if (!isset($this->_RequestArguments[$ParamType]))
         $this->_RequestArguments[$ParamType] = array();

      $this->_RequestArguments[$ParamType][$ParamName] = $ParamValue;
   }

   /**
    * Detach a dataset from the request
    *
    * @param int $ParamsType type of data to remove. One of the self::INPUT_* constants
    * @return void
    */
   public function _UnsetRequestArguments($ParamsType) {
      unset($this->_RequestArguments[$ParamsType]);
   }

   /**
    * This method allows safe creation of URLs that need to reference the application itself
    *
    * Taking the server's RewriteUrls ability into account, and using information from the
    * actual Request data, this method can construct a trustworthy URL that will point to
    * Garden's dispatcher. Examples:
    *    - Default port, no rewrites, subfolder:      http://www.forum.com/vanilla/index.php?/
    *    - Default port, rewrites                     http://www.forum.com/
    *    - Custom port, rewrites                      http://www.forum.com:8080/index.php?/
    *
    * @param sring $Path of the controller method.
    * @param mixed $WithDomain Whether or not to include the domain with the url. This can take the following values.
    * - true: Include the domain name.
    * - false: Do not include the domain. This is a relative path.
    * - //: Include the domain name, but use the // schemeless notation.
    * - /: Just return the path.
    * @param bool $SSL set to true to implement SSL
    * @return string
    *
    * @changes
    *    2.1   Added the // option to $WithDomain.
    *    2.2   Added the / option to $WithDomain.
    */
   public function Url($Path = '', $WithDomain = FALSE, $SSL = NULL) {
      static $AllowSSL = NULL; if ($AllowSSL === NULL) $AllowSSL = C('Garden.AllowSSL', FALSE);
      static $RewriteUrls = NULL; if ($RewriteUrls === NULL) $RewriteUrls = C('Garden.RewriteUrls', FALSE);

      if (!$AllowSSL) {
         $SSL = NULL;
      } elseif ($WithDomain === 'https') {
         $SSL = true;
         $WithDomain = true;
      }

      // If we are explicitly setting ssl urls one way or another
      if (!is_null($SSL)) {
         // Force the full domain in the url
         $WithDomain = TRUE;
         // And make sure to use ssl or not
         if ($SSL) {
            $Path = str_replace('http:', 'https:', $Path);
            $Scheme = 'https';
         } else {
            $Path = str_replace('https:', 'http:', $Path);
            $Scheme = 'http';
         }
      } else {
         $Scheme = $this->Scheme();
      }
      if (substr($Path, 0, 2) == '//' || in_array(strpos($Path, '://'), array(4, 5))) // Accounts for http:// and https:// - some querystring params may have "://", and this would cause things to break.
         return $Path;

      $Parts = array();

      $Port = $this->Port();
      $Host = $this->Host();
      if (!in_array($Port, array(80, 443)) && (strpos($Host, ':'.$Port) === FALSE))
         $Host .= ':'.$Port;

      if ($WithDomain === '//') {
         $Parts[] = '//'.$Host;
      } elseif ($WithDomain && $WithDomain !== '/') {
         $Parts[] = $Scheme.'://'.$Host;
      } else
         $Parts[] = '';

      if ($WithDomain !== '/' && $this->WebRoot() != '')
         $Parts[] = $this->WebRoot();

      // Strip out the hash.
      $Hash = strchr($Path, '#');
      if (strlen($Hash) > 0)
         $Path = substr($Path, 0, -strlen($Hash));

      // Strip out the querystring.
      $Query = strrchr($Path, '?');
      if (strlen($Query) > 0)
         $Path = substr($Path, 0, -strlen($Query));

      if (!$RewriteUrls) {
         $Parts[] = $this->_EnvironmentElement('Script').'?p=';
         $Query = str_replace('?', '&', $Query);
      }

      if($Path == '') {
         $PathParts = explode('/', $this->Path());
         $PathParts = array_map('rawurlencode', $PathParts);
         $Path = implode('/', $PathParts);
         // Grab the get parameters too.
         if (!$Query) {
            $Query = $this->GetRequestArguments(self::INPUT_GET);
            if (count($Query) > 0)
               $Query = ($RewriteUrls ? '?' : '&amp;').http_build_query($Query);
            else
               unset($Query);
         }
      }
      $Parts[] = ltrim($Path, '/');

      $Result = implode('/', $Parts);

      // If we are explicitly setting ssl urls one way or another
      if (!is_null($SSL)) {
         // And make sure to use ssl or not
         if ($SSL) {
            $Result = str_replace('http:', 'https:', $Result);
         } else {
            $Result = str_replace('https:', 'http:', $Result);
         }
      }

      if (isset($Query))
         $Result .= $Query;

      if (isset($Hash))
         $Result .= $Hash;

      return $Result;
   }

   /**
    * Compare two urls for equality.
    *
    * @param string $url1 The first url to compare.
    * @param string $url2 The second url to compare.
    * @return int Returns 0 if the urls are equal or 1, -1 if they are not.
    */
   function UrlCompare($url1, $url2) {
      $parts1 = parse_url($this->Url($url1));
      $parts2 = parse_url($this->Url($url2));


      $defaults = array(
         'scheme' => $this->Scheme(),
         'host' => $this->HostAndPort(),
         'path' => '/',
         'query' => ''
      );

      $parts1 = array_replace($defaults, $parts1 ?: array());
      $parts2 = array_replace($defaults, $parts2 ?: array());

      if ($parts1['host'] === $parts2['host']
         && ltrim($parts1['path'], '/') === ltrim($parts2['path'], '/')
         && $parts1['query'] === $parts2['query']) {
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
   public function UrlDomain($withDomain = true) {
      static $allowSSL = null;

      if ($allowSSL === null) {
         $allowSSL = C('Garden.AllowSSL', null);
      }

      if (!$withDomain || $withDomain === '/') {
         return '';
      }

      if (!$allowSSL && $withDomain === 'https') {
         $withDomain = 'http';
      }

      if ($withDomain === true) {
         $withDomain = $this->Scheme().'://';
      } elseif ($withDomain !== '//') {
         $withDomain .= '://';
      }

      return $withDomain.$this->HostAndPort();
   }


   /**
    * Gets/Sets the relative path to the application's dispatcher.
    *
    * @param $WebRoot Optional Webroot to set
    * @return string
    */
   public function WebRoot($WebRoot = NULL) {
      $Path = (string)$this->_ParsedRequestElement('WebRoot', $WebRoot);
      $WebRootFromConfig = $this->_EnvironmentElement('ConfigWebRoot');

      $RemoveWebRootConfig = $this->_EnvironmentElement('ConfigStripUrls');
      if ($WebRootFromConfig && $RemoveWebRootConfig) {
         $Path = str_replace($WebRootFromConfig, '', $Path);
      }
      return $Path;
   }

   /**
    * Chainable Superglobal arguments setter
    *
    * This method expects a variable number of parameters, each of which need to be a defined INPUT_*
    * constant, and will interpret these as superglobal references. These constants each refer to a
    * specific PHP superglobal and including them here causes their data to be imported into the request
    * object.
    *
    * @param self::INPUT_*
    * @flow chain
    * @return Gdn_Request
    */
   public function WithArgs() {
      $ArgAliasList = func_get_args();
      if (count($ArgAliasList))
         foreach ($ArgAliasList as $ArgAlias) {
            $this->_SetRequestArguments(strtolower($ArgAlias));
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
    * @param $CustomArgs key/value array of custom request argument data.
    * @flow chain
    * @return Gdn_Request
    */
   public function WithCustomArgs($CustomArgs) {
      $this->_SetRequestArguments(self::INPUT_CUSTOM, $CustomArgs);
      return $this;
   }

   /**
    * Chainable URI Setter, source is a controller + method + args list
    *
    * @param $Controller Gdn_Controller Object or string controller name.
    * @param $Method Optional name of the method to call. Omit or NULL for default (Index).
    * @param $Args Optional argument list to forward to the method. Omit for none.
    * @flow chain
    * @return Gdn_Request
    */
   public function WithControllerMethod($Controller, $Method = NULL, $Args = array()) {
      if (is_a($Controller, 'Gdn_Controller')) {
         // Convert object to string
         $Matches = array();
         preg_match('/^(.*)Controller$/',get_class($Controller),$Matches);
         $Controller = $Matches[1];
      }

      $Method = is_null($Method) ? 'index' : $Method;
      $Path = trim(implode('/',array_merge(array($Controller,$Method),$Args)),'/');
      $this->_EnvironmentElement('URI', $Path);
      return $this;
   }

   public function WithDeliveryType($DeliveryType) {
      $this->SetValueOn(self::INPUT_GET, 'DeliveryType', $DeliveryType);
      return $this;
   }

   public function WithDeliveryMethod($DeliveryMethod) {
      $this->SetValueOn(self::INPUT_GET, 'DeliveryMethod', $DeliveryMethod);
      return $this;
   }

   public function WithRoute($Route) {
      $ParsedURI = Gdn::Router()->GetDestination($Route);
      if ($ParsedURI)
         $this->_EnvironmentElement('URI',$ParsedURI);
      return $this;
   }

   /**
    * Chainable URI Setter, source is a simple string
    *
    * @param $URI optional URI to set as as replacement for the REQUEST_URI superglobal value
    * @flow chain
    * @return Gdn_Request
    */
   public function WithURI($URI = NULL) {
      $this->_EnvironmentElement('URI',$URI);
      return $this;
   }

}
