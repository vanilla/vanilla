<?php if (!defined('APPLICATION')) exit();

/**
 * ProxyRequest handler class
 * 
 * This class abstracts the work of doing external requests.
 * 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2010, Tim Gunter 
 * @license Proprietary
 * @package Api
 * @since 1.0
 */

class ProxyRequest {
   
   protected static $ConnectionHandles;
   
   public $MaxReadSize = 4096;
   
   public $RequestHeaders;
   
   public $ResponseHeaders;
   public $ResponseStatus;
   public $ResponseBody;
   
   public $ContentType;
   public $ContentLength;
   public $ConnectionMode;
   
   public $ActionLog;
   
   protected $Options;
   
   public function __construct($Loud = FALSE) {
      self::$ConnectionHandles = array();
      
      $this->Loud = $Loud;
   }
   
   protected function FsockConnect(&$Handle, $Host, $Port, $Options) {
      $ConnectTimeout = GetValue('ConnectTimeout', $Options);
      $ReadTimeout = GetValue('Timeout', $Options);
      $Recycle = GetValue('Recycle', $Options);
      $RecycleFrequency = GetValue('RequestsPerPointer', $Options);
      
      $Pointer = FALSE;
      
      // Try to resolve hostname
      $HostAddress = gethostbyname($Host);
      if (ip2long($HostAddress) === FALSE) {
         throw new Exception(sprintf('Encountered an error while making a request to the remote server (%s): %s', $Host, "Could not resolve hostname"));
      }
      
      // Start off assuming recycling failed
      $Recycled = FALSE;

      // If we're trying to recycle, look for an existing handler
      if ($Recycle && array_key_exists($HostAddress, self::$ConnectionHandles)) {
         $PointerInfo = &self::$ConnectionHandles[$HostAddress];
         try {
            if (!is_array($PointerInfo))
               throw new Exception();
            
            if (!isset($PointerInfo['Handle']) || !$PointerInfo['Handle'])
               throw new Exception();
            
            $Pointer = $PointerInfo['Handle'];
            $StreamMeta = stream_get_meta_data($Pointer);

            if (GetValue('timed_out', $StreamMeta) || GetValue('eof', $StreamMeta))
               throw new Exception();
            
            if ($RecycleFrequency > 0 && GetValue('Requests', $PointerInfo) > $RecycleFrequency)
               throw new Exception();
            
            //echo " : Loaded existing pointer for {$HostAddress}\n";
            $Recycled = TRUE;
            
         } catch (Exception $e) {
            $this->FsockDisconnect($Pointer, $HostAddress);
            //echo " : Threw away dead pointer for {$HostAddress}\n";
         }
      }

      if (!$Pointer) {
         $Pointer = @fsockopen($HostAddress, $Port, $ErrorNumber, $Error, $ConnectTimeout);
         if ($Recycle && !$Recycled) {
            //echo " : Making a new reusable pointer for {$HostAddress}\n";
            self::$ConnectionHandles[$HostAddress] = array(
                'Handle'         => $Pointer,
                'Host'           => $Host,
                'HostAddress'    => $HostAddress,
                'HostPort'       => $Port,
                'Requests'       => 0,
                'BytesSent'      => 0,
                'BytesReceived'  => 0
            );
         }
      }

      if (!$Pointer)
         throw new Exception(sprintf('Encountered an error while making a request to the remote server (%s): [%s] %s', $Host, $ErrorNumber, $Error));

      stream_set_timeout($Pointer, $ReadTimeout);
      
      $Handle = $Pointer;
      return $HostAddress;
   }
   
   protected function FsockDisconnect(&$Pointer, $HostAddress = NULL) {
      if ($Pointer)
         @fclose($Pointer);
      $Pointer = FALSE;
      
      if (!is_null($HostAddress) && array_key_exists($HostAddress, self::$ConnectionHandles))
         unset(self::$ConnectionHandles[$HostAddress]);
   }
   
   protected function FsockSend(&$Pointer, $Data) {
      
      $this->Action("Sending headers");
      
      $DataSent = 0;
      $DataToSend = strlen($Data);
      $StalledCount = 0;
      do {
         $BytesWritten = fwrite($Pointer, substr($Data,$DataSent), $DataToSend-$DataSent);
         if (!$BytesWritten && $DataSent < $DataToSend) {
            if ($StalledCount > 20) break;
            $StalledCount++;
            continue;
         }
         $DataSent += $BytesWritten;
         $DataAmountSent = floor(($DataSent/$DataToSend) * 100);
         //echo " : Writen {$DataSent}/{$DataToSend} bytes ({$DataAmountSent}%)\n";
      } while($DataSent < $DataToSend);
      return $DataSent;
   }
   
   protected function FsockReceive(&$Pointer) {
      
      $this->Action("Reading response headers");
      
      // Get the first line of the response headers, for the status
      do {
         $Status = trim(fgets($Pointer, $this->MaxReadSize));
         $Matched = preg_match('|^HTTP.+? (\d+?) |', $Status, $Matches);
         if (!$Matched) continue;
         
         $this->ResponseHeaders['HTTP'] = $Status;
         break;
      } while (true);
      
		$this->ResponseStatus = $Matches[1];
      
      $TransferEncoding = 'normal';
      $ConnectionMode = 'close';
      while ($Line = fgets($Pointer, $this->MaxReadSize)) {
			if ($Line == "\r\n") { break; }
         
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $this->ResponseHeaders[$Key] = $Value;
         
         if ($Key == 'Connection')
				$this->ConnectionMode = $ConnectionMode = strtolower($Value);
         
			if ($Key == 'Content-Length')
				$this->ContentLength = (int)$Value;

			if ($Key == 'Content-Type')
				$this->ContentType = strtolower($Value);
         
			if ($Key == 'Transfer-Encoding')
				$TransferEncoding = strtolower($Value);
      }
      
      //print_r($this->ResponseHeaders);
      
      $Loud = ($ConnectionMode == 'close');
      
      // Keepalive, not chunked
      if (isset($this->ContentLength) && $TransferEncoding != 'chunked') {
         
         if ($ConnectionMode == 'close') {
            $this->Action("Reading response body directly until feof because ConnectionMode = {$ConnectionMode}");
            while (!feof($Pointer)) {
               $this->ResponseBody .= fread($Pointer, $this->MaxReadSize);
            }
            return $this->ResponseBody;
         }
         
         else {
            $this->Action("Reading response body progressively based on ContentLength = {$this->ContentLength}");
            $TotalBytes = 0;
            do {
               $LeftToRead = $this->ContentLength - $TotalBytes;
               if (!$LeftToRead) break;

               $this->ResponseBody .= $Data = fread($Pointer, $LeftToRead);
               $TotalBytes += $BytesRead = strlen($Data);
               unset($Data);

               if (feof($Pointer))
                  break;
            } while ($LeftToRead);
            if ($TotalBytes < $this->ContentLength)
               throw new Exception("Connection failed after {$TotalBytes}/{$this->ContentLength} bytes (te: normal)");

            return $this->ResponseBody;
         }
      }
      
      if ($TransferEncoding == 'chunked') {
         $this->Action("Reading response body in chunks because TE = {$TransferEncoding}");
         
         // Chunked encoding
         do {
            $this->Action("  + scanning for a chunk");
            $ChunkLength = rtrim(fgets($Pointer, $this->MaxReadSize));
            $ChunkExtended = strpos($ChunkLength,';');
            if ($ChunkExtended !== FALSE)
               $ChunkLength = substr($ChunkLength, 0, $ChunkExtended);
            
            $this->Action("  + chunk: {$ChunkLength}");
            
            $ChunkLength = hexdec($ChunkLength);
            if ($ChunkLength < 1) { 
               //if ($Loud) die('zerochunk');
               break;
            }
            
            $this->Action("  + chunk dec len: {$ChunkLength}");

            $TotalBytes = 0;
            do {
               $LeftToRead = $ChunkLength - $TotalBytes;
               $this->Action("  + ltr: {$LeftToRead}");
               if (!$LeftToRead) {
                  $this->Action("  + break");
                  break;
               }
               $this->ResponseBody .= $Data = fread($Pointer, $LeftToRead);
               $TotalBytes += $BytesRead = strlen($Data);
               unset($Data);
               
               if (feof($Pointer))
                  break;
            } while ($LeftToRead);
            if ($TotalBytes < $ChunkLength)
               throw new Exception("Connection failed after {$TotalBytes}/{$ChunkLength} bytes (te: chunked)");

            // Chunks are terminated by CRLF
            $Rubbish = fgets($Pointer, $this->MaxReadSize);
            
            $this->Action("  + chunk terminator: {$Rubbish}");
            $this->Action("  + chunk complete, chunksize = {$ChunkLength}");
         } while ($ChunkLength);
         
         fgets($Pointer, $this->MaxReadSize);
         return $this->ResponseBody;
      }
      
      throw new Exception("Unable to detect reading mode for incoming data");
   }
   
   protected function CurlReceive(&$Handler) {
      $Response = curl_exec($Handler);
      
      $this->ResponseStatus = curl_getinfo($Handler, CURLINFO_HTTP_CODE);
      $this->ContentType = strtolower(curl_getinfo($Handler, CURLINFO_CONTENT_TYPE));
      $this->ContentLength = (int)curl_getinfo($Handler, CURLINFO_CONTENT_LENGTH_DOWNLOAD);
      
      if ($Response == FALSE) {
         $Success = FALSE;
         $this->ResponseBody = curl_error($Handler);
         return $this->ResponseBody;
      }
      
      $ResponseParts = explode("\r\n\r\n", $Response);

      $ResponseHeaderData = trim(array_shift($ResponseParts));
      $ResponseHeaderLines = explode("\n",$ResponseHeaderData);

      // Parse header status line
      $Status = trim(array_shift($ResponseHeaderLines));
      $this->ResponseHeaders['HTTP'] = $Status;
      foreach ($ResponseHeaderLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $this->ResponseHeaders[$Key] = $Value;
      }
      
      $this->ResponseBody = trim(implode("\r\n\r\n",$ResponseParts));
      return $this->ResponseBody;
   }
   
   public function Request($Options, $QueryParams = NULL) {
      
      if (is_string($Options)) {
         $Options = array(
             'URL'      => $Options
         );
      }

      $Defaults = array(
          'URL'                  => NULL,
          'Method'               => 'GET',
          'ConnectTimeout'       => 5,
          'Timeout'              => 2,
          'Redirects'            => TRUE,
          'Recycle'              => FALSE,      // Whether to reuse this pointer if possible.
          'RequestsPerPointer'   => 0,          // How often to recycle pointers reusable pointers.
          'Cookies'              => TRUE,       // Send cookies?
          'CloseSession'         => TRUE,       // Whether to close the session. Should always do this.
          'Redirected'           => FALSE,      // Flag. Is this a redirected request?
          'Debug'                => FALSE,      // Debug output on?
          'Simulate'             => FALSE       // Don't actually request, just set up
      );

      $this->ResponseHeaders = array();
      $this->ResponseStatus = "";
      $this->ResponseBody = "";
      
      $this->ContentLength = 0;
      $this->ContentType = '';
      $this->ConnectionMode = '';
      $this->ActionLog = array();
      
      $this->Options = $Options = array_merge($Defaults, $Options);
      
      if (is_null($QueryParams)) $QueryParams = array();

      $RelativeURL = GetValue('URL', $Options);
      $RequestMethod = GetValue('Method', $Options);
      $FollowRedirects = GetValue('Redirects', $Options);
      $ConnectTimeout = GetValue('ConnectTimeout', $Options);
      $Timeout = GetValue('Timeout', $Options);
      $Recycle = GetValue('Recycle', $Options);
      $SendCookies = GetValue('Cookies', $Options);
      $CloseSesssion = GetValue('CloseSession', $Options);
      $Redirected = GetValue('Redirected', $Options);
      $Debug = GetValue('Debug', $Options, FALSE);
      $Simulate = GetValue('Simulate', $Options);
      
      if ($CloseSesssion)
         @session_write_close();
      
      $OldVolume = $this->Loud;
      if ($Debug)
         $this->Loud = TRUE;

      $Url = $RelativeURL;
      
      $RequestMethod = strtoupper($RequestMethod);
      $PostData = http_build_query($QueryParams);
      switch ($RequestMethod) {
         case 'POST':
            
            break;
         
         case 'GET':
         default:
            if (stristr($RelativeURL, '?'))
               $Url .= '&';
            else
               $Url .= '?';
            $Url .= $PostData;
            break;
      }
      

      if ($Debug) echo " : Requesting {$Url}\n";
      $this->Action("Requesting {$Url}");

      $UrlParts = parse_url($Url);
      $Scheme = GetValue('scheme', $UrlParts, 'http');
      $Host = GetValue('host', $UrlParts, '');
      $Port = GetValue('port', $UrlParts, '80');
      if (empty($Port)) $Port = 80;
      $Path = GetValue('path', $UrlParts, '');
      $Query = GetValue('query', $UrlParts, '');
      
      // Get the cookie.
      $Cookie = '';
      $EncodeCookies = TRUE;

      foreach($_COOKIE as $Key => $Value) {
         if (strncasecmp($Key, 'XDEBUG', 6) == 0)
            continue;

         if (strlen($Cookie) > 0)
            $Cookie .= '; ';

         $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
         $Cookie .= "{$Key}={$EValue}";
      }
      $Response = '';
      if ((function_exists('curl_init') && !$Recycle) || !function_exists('fsockopen')) {

         //$Url = $Scheme.'://'.$Host.$Path;
         $this->Action("cURL");

         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_USERAGENT, GetValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($Handler, CURLOPT_CONNECTTIMEOUT, $ConnectTimeout);
         
         if ($Timeout > 0)
            curl_setopt($Handler, CURLOPT_TIMEOUT, $Timeout);

         if ($Cookie != '' && $SendCookies)
            curl_setopt($Handler, CURLOPT_COOKIE, $Cookie);

         if ($RequestMethod == 'POST') {
            curl_setopt($Handler, CURLOPT_POST, 1);
            curl_setopt($Handler, CURLOPT_POSTFIELDS, $PostData);
         }
         
         $this->CurlReceive($Handler);

         if ($Simulate) return NULL;
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         $this->Action("fsockopen");
         
         $Pointer = FALSE;
         $HostAddress = $this->FsockConnect($Pointer, $Host, 80, $Options);

         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $SendHeaders = array();
         
         $SendHeaders[] = "{$RequestMethod} $Path?$Query HTTP/1.1";
         $SendHeaders[] = "Host: {$HostHeader}";
            // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
            // "Authorization: Basic ". base64_encode ("username:password")."\r\n" . 
         //$SendHeaders[] = "User-Agent: Vanilla/2.0 RunnerBot";
         $SendHeaders[] = "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10.6; rv:2.0.1) Gecko/20100101 Firefox/4.0.1";
         $SendHeaders[] = "Accept: */*";
         $SendHeaders[] = "Accept-Charset: utf-8;";
         $SendHeaders[] = "Accept-Encoding: chunked";
         $SendHeaders[] = "Cache-Control: no-cache";
         $SendHeaders[] = "Date: ".date("r");
         $SendHeaders[] = "Pragma: no-cache";

         if (!$Recycle)
            $SendHeaders[] = "Connection: close";
         else
            $SendHeaders[] = "Connection: Keep-Alive";

         if (strlen($Cookie) > 0 && $SendCookies)
            $SendHeaders[] = "Cookie: {$Cookie}";
            
         if ($RequestMethod == 'POST') {
            $SendHeaders[] = "Content-Type: application/x-www-form-urlencoded";
            $PostDataLength = strlen($PostData);
            $SendHeaders[] = "Content-Length: {$PostDataLength}";
         }
         
         $this->RequestHeaders = $SendHeaders;
         
         $Header = "";
         foreach ($SendHeaders as $SendHeader) {
            $Header .= "{$SendHeader}\r\n";
            if ($Debug) echo " > {$SendHeader}\n";
         }
         $Header .= "\r\n";
         if ($RequestMethod == 'POST') {
            if ($Debug) echo "Sending POST data\n";
            $Header .= $PostData;
            if ($Debug) echo " > {$PostData}\n";
            $Header .= "\r\n";
         }
         
         if ($Simulate) return NULL;
         
         // Send the request headers
         $this->FsockSend($Pointer, $Header);
         
         // Read from the server
         $this->FsockReceive($Pointer);

         if (!$Recycle || $this->ConnectionMode == 'close') {
            if ($Debug) echo " : Closing onetime pointer for {$HostAddress}\n";
            $this->FsockDisconnect($Pointer, $HostAddress);
         }

         if (in_array($this->ResponseStatus, array(301,302)) && $FollowRedirects) {
            $Location = GetValue('Location', $this->ResponseHeaders, NULL);
            if (is_null($Location))
               $Location = GetValue('location', $this->ResponseHeaders, NULL);
            
            if (is_null($Location))
               throw new Exception("Received status code {$this->ResponseStatus} (redirect) but no 'Location' provided.");
            
            if (substr($Location,0,4) != 'http') {
               $Location = ltrim($Location, '/');
               $Location = "http://{$Host}/{$Location}";
            }
            $Options['URL'] = $Location;
            $Options['Redirected'] = TRUE;
            return $this->Request($Options, $QueryParams);
         }

      } else {
         throw new Exception('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.');
      }
      
      $this->Loud = $OldVolume;
      return $this->ResponseBody;
   }
   
   protected function Action($Message, $Loud = NULL) {
      if ($this->Loud || $Loud) {
         echo "{$Message}\n";
         flush();
         ob_flush();
      }
      
      $this->ActionLog[] = $Message;
   }
}