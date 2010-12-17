<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

class DownloadModel {
   
   // Local download repository folder
   protected $DownloadDir = NULL;
   
   // Download version and file information stored here as we need it
   protected $Downloads = NULL;
   
   // URL to the online repository
   protected $Repository = NULL;
   
   // Remote methods to execute when accessing repository
   protected $RPC = NULL;
   
   // Addon version and file information stored here as we download it
   protected $Addons = NULL;
   
   protected $RemoteInfo = NULL;
   
   public function __construct() {
   
      $this->Repository = C('Update.Remote.Repository');
      $this->RPC = array(
         'query'     => C('Update.Remote.RPC.Information'),
         'download'  => C('Update.Remote.RPC.Download')
      );

      $this->Addons = array();
      $this->RemoteInfo = array();

      $this->DownloadDir = C('Update.Local.Downloads', NULL);
      $this->Downloads = array();
      
      if (!is_dir($this->DownloadDir)) {
         $Made = @mkdir($this->DownloadDir);
         if (!$Made)
            throw new Exception(sprintf(T("Could not create download location %s"), $this->DownloadDir));
      }
      
   }
   
   protected function GetDownloads($Addon, $ForceFresh = FALSE) {
      if (!array_key_exists($Addon, $this->Downloads) || $ForceFresh) {
         $AddonDir = CombinePaths(array($this->DownloadDir));
      }
   }
   
   public function Request($Url, $Parameters, $Options = array()) {

      $DefaultOptions = array(
         'SendCookies'     => TRUE,
         'RequestMethod'   => 'GET',
         'FollowRedirects' => TRUE,
         'SaveFile'        => FALSE,
         'SaveVerify'      => FALSE,
         'UseExisting'     => TRUE,
         'Timeout'         => C('Garden.SocketTimeout', 2.0),
         'BufferSize'      => 8192,
         'UserAgent'       => GetValue('HTTP_USER_AGENT', $_SERVER, 'Vanilla/2.0'),
         'Referer'         => Gdn_Url::WebRoot(TRUE),
         'Authentication'  => FALSE,
         'Username'        => NULL,
         'Password'        => NULL
      );
      
      $Options = array_merge($DefaultOptions, $Options);

      // Break provided URL into constituent pieces
      $UrlParts = parse_url($Url);
      $Scheme  = GetValue('scheme', $UrlParts, 'http');
      $Host    = GetValue('host', $UrlParts, '');
      $Port    = GetValue('port', $UrlParts, '80');
      $Path    = GetValue('path', $UrlParts, '');
      $Query   = GetValue('query', $UrlParts, '');
      
      // Get environment cookies if needed
      $CookieData = '';
      if (GetValue('SendCookies', $Options, FALSE)) {
         $EncodeCookies = C('Garden.Cookie.Urlencode', TRUE);
         
         foreach ($_COOKIE as $Key => $Value) {
            if(strncasecmp($Key, 'XDEBUG', 6) == 0)
               continue;
            
            if(strlen($CookieData) > 0)
               $CookieData .= '; ';
               
            $EValue = ($EncodeCookies) ? urlencode($Value) : $Value;
            $CookieData .= "{$Key}={$EValue}";
         }
      }
      
      // Prepare response container
      $Response = '';
      
      // Figure out if we're going to do a GET or POST
      $RequestMethod = strtolower(GetValue("RequestMethod", $Options, "GET"));
      
      // Get BufferSize for incremental reads
      $BufferSize = GetValue('BufferSize', $Options);
      
      // Get Timeout for connection attempts
      $Timeout = GetValue('Timeout', $Options);
      
      $SaveFile = (GetValue('SaveFile', $Options) !== FALSE);
      
      // If we want to save the response to a file, handle the FP and existing issues
      if ($SaveFile === TRUE) {
         $Filename = GetValue('SaveFile', $Options);
         
         // Work out what the real filepath should be if we received a directory
         if (is_dir($Filename)) {
            $RequestFilename = basename($Path);
            $Filename = CombinePaths(array($Filename, $RequestFilename));
         }
         
         echo "Request(): Filename = {$Filename}\n";
         
         // Check if the folder exists at least
         $VacantSpace = Gdn_Filesystem::CheckFolderR(dirname($Filename), Gdn_Filesystem::O_CREATE);
         echo "Request(): Dir exists? "; var_dump($VacantSpace);
         
         if (file_exists($Filename)) {
            if (GetValue('UseExisting', $Options) && md5_file($Filename) == GetValue('SaveVerify', $Options)) {
               return array(
                  'Headers'      => array(),
                  'RawHeaders'   => '',
                  'Body'         => '',
                  'File'         => array(
                     'Path'         => $Filename,
                     'Size'         => filesize($Filename),
                     'Success'      => TRUE
                  )
               );
            }
         
            // Delete the file first
            $VacantSpace = @unlink($Filename);
         }
         
         if (!$VacantSpace)
            throw new Exception(sprintf(T('Could not save to file %s'), $Filename));
         
         echo "Request(): Opening {$Filename} for writing\n";
         $FilePointer = fopen($Filename, 'wb');
         if (!$FilePointer)
            throw new Exception(sprintf(T('Could not open target file %s for writing'), $Filename));
         
         $HeaderFilename = $Filename.'-headers';
         $HeaderFilePointer = fopen($HeaderFilename, 'w');
      }
      
      // Pick request mode based on feature availability and priority
      if (function_exists('curl_init') && FALSE) {
         
         echo "Request(): Using curl\n";
         
         /**
          * cURL mode
          *  - uses built-in curl methods to make the request
          */
         
         $Handler = curl_init();
         curl_setopt($Handler, CURLOPT_URL, $Url);
         curl_setopt($Handler, CURLOPT_PORT, $Port);
         curl_setopt($Handler, CURLOPT_HEADER, 1);
         curl_setopt($Handler, CURLOPT_REFERER, GetValue('Referer', $Options));
         curl_setopt($Handler, CURLOPT_USERAGENT, GetValue('UserAgent', $Options));
         curl_setopt($Handler, CURLOPT_RETURNTRANSFER, 1);
         curl_setopt($Handler, CURLOPT_BUFFERSIZE, $BufferSize);
         curl_setopt($Handler, CURLOPT_CONNECTTIMEOUT, $Timeout);
         
         // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
         if (GetValue('Authentication', $Options) === TRUE) {
            $ChallengeResponse = GetValue('Username', $Options).":".GetValue('Password', $Options);
            curl_setopt($Handler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($Handler, CURLOPT_USERPWD, $ChallengeResponse);
         }
         
         // Send cookies?
         if (strlen($CookieData) > 0 && GetValue('SendCookies', $Options))
            curl_setopt($Handler, CURLOPT_COOKIE, $CookieData);
         
         // POST method?
         if ($RequestMethod == "post") {
            curl_setopt($Handler, CURLOPT_POST, 1);
            curl_setopt($Handler, CURLOPT_POSTFIELDS, $Parameters);
         }
         
         // If we're saving to a file, tell cURL how to behave
         if ($SaveFile === TRUE) {
            
            // No header when saving a file
            curl_setopt($Handler, CURLOPT_HEADER, 0);
            
            // Point cURL at a file handle
            curl_setopt($Handler, CURLOPT_FILE, $FilePointer);
            curl_setopt($Handler, CURLOPT_WRITEHEADER, $HeaderFilePointer);
         }
         
         $Response = curl_exec($Handler);
         $Success = TRUE;
         if ($Response === FALSE) {
            $Success = FALSE;
            $Response = curl_error($Handler);
         }
         
         if ($Response === TRUE)
            $Response = file_get_contents($HeaderFilename);
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
      
         echo "Request(): Using fsockopen\n";
         
         $LogFile = fopen('/www/vanilla/vanilla/cache/download.log', 'w');
         
         /**
          * fsockopen mode
          *  - manually constructs HTTP requests
          */
         
         // Construct the request handle
         $InPointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
         if (!$InPointer)
            throw new Exception(sprintf(T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'), $Url, $ErrorNumber, $Error));
   
         $HostHeader = $Host.(($Port != 80) ? ":{$Port}" : '');
         $Header = array();
         
         array_push($Header, strtoupper($RequestMethod)." {$Path}?{$Query} HTTP/1.1");
         array_push($Header, "Host: {$HostHeader}");
         array_push($Header, "User-Agent: ".GetValue('UserAgent', $Options));
         array_push($Header, "Accept: */*");
         array_push($Header, "Accept-Charset: utf-8;");
         array_push($Header, "Referer: ".GetValue('Referer', $Options));
         array_push($Header, "Connection: close");
         
         // If you've got basic authentication enabled for the app, you're going to need to explicitly define the user/pass for this fsock call
         if (GetValue('Authentication', $Options) === TRUE) {
            $ChallengeResponse = base64_encode(GetValue('Username', $Options).":".GetValue('Password', $Options));
            array_push($Header, "Authorization: Basic {$ChallengeResponse}");
         }
         
         // Send cookies?
         if (strlen($CookieData) > 0 && GetValue('SendCookies', $Options))
            array_push($Header, "Cookie: {$CookieData}");
         
         // HTTP required newline
         array_push($Header, NULL);
         array_push($Header, NULL);
         
         // POST method?
         if ($RequestMethod == "post") {
            if (is_array($Parameters) && sizeof($Parameters))
               array_push($Header, http_build_query($Parameters));
            
            // HTTP required newline
            array_push($Header, NULL);
            array_push($Header, NULL);
         }
         
         // Send the request headers
         $HeaderString = implode("\r\n", $Header);
         fputs($InPointer, $HeaderString);
         fputs($LogFile, ">> ".$HeaderString);
         
         // Have we seen the end of the headers yet?
         $DetectedHeaderBreak = FALSE;
         // Read back the response
         while ($InData = fread($InPointer, $BufferSize)) {
            fwrite($LogFile, "<< [{$BufferSize}] ".$InData);
            if ($SaveFile === TRUE) {
               if (!$DetectedHeaderBreak) {
                  $HeaderBreakPosition = strpos($InData, "\r\n\r\n");
                  
                  // Found header break in this buffer
                  if ($HeaderBreakPosition !== FALSE) {
                     $DetectedHeaderBreak = TRUE;
                  
                     // Append the header data to the Response
                     $Response .= substr($InData, 0, $HeaderBreakPosition+4);
                     
                     // Real data is whatever comes after the headers
                     $InData = substr($InData, $HeaderBreakPosition+4);
                  }
               }
               
               if ($DetectedHeaderBreak) {
                  fwrite($FilePointer, $InData);
                  $InData = NULL; // Don't write file data to response
               }
               
            }
            
            $Response .= $InData;
         }
         @fclose($InPointer);
            
         $Success = TRUE;
      } else {
         throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
      }
      
      if ($SaveFile) {
         @fclose($FilePointer);
         @fclose($HeaderFilePointer);
         @unlink($HeaderFilename);
      }
      
      if (!$Success)
         return $Response;
      
      $ResponseHeaderData = trim(substr($Response, 0, strpos($Response, "\r\n\r\n")));
      $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
      
      $ResponseHeaderLines = explode("\n",trim($ResponseHeaderData));
      $Status = array_shift($ResponseHeaderLines);
      $ResponseHeaders = array();
      $ResponseHeaders['HTTP'] = trim($Status);
      
      // * get the numeric status code. 
      // * - trim off excess edge whitespace, 
      // * - split on spaces, 
      // * - get the 2nd element (as a single element array), 
      // * - pop the first (only) element off it... 
      // * - return that.
      $ResponseHeaders['StatusCode'] = array_pop(array_slice(explode(' ',trim($Status)),1,1));
      foreach ($ResponseHeaderLines as $Line) {
         $Line = explode(':',trim($Line));
         $Key = trim(array_shift($Line));
         $Value = trim(implode(':',$Line));
         $ResponseHeaders[$Key] = $Value;
      }
      
      if (GetValue('FollowRedirects', $Options) === TRUE) { 
         $Code = GetValue('StatusCode',$ResponseHeaders, 200);
         if (in_array($Code, array(301,302))) {
            if (array_key_exists('Location', $ResponseHeaders)) {
               $Location = GetValue('Location', $ResponseHeaders);
               return $this->Request($Location, $Parameters, $Options);
            }
         }
      }
      
      $ReturnData = array(
         'RawHeaders'   => $ResponseHeaderData,
         'Headers'      => $ResponseHeaders,
         'Body'         => $Response
      );
      
      if ($SaveFile === TRUE) {
         $Exists = file_exists($Filename);
         
         $DownloadedSize = $Exists ? filesize($Filename) : 0;
         $DownloadedVerify = md5_file($Filename);
         $SaveVerify = GetValue('SaveVerify', $Options, FALSE);
         
         if ($SaveVerify === FALSE) {
            $Success = $DownloadedSize > 0;
         } else {
            $Success = $SaveVerify == $DownloadedVerify;
         }
         
         $ReturnData['File'] = array(
            'Path'      => $Filename,
            'Size'      => $DownloadedSize,
            'Success'   => $Success
         );
      }
      
      return $ReturnData;
   }
   
   public function GetAddonArchive($Addon, $Version = NULL, $ForceFresh = FALSE) {
      
      if (is_null($Version))
         $Version = 'latest';
      
      $VersionInfo = $this->VersionInfo($Addon, $Version);
      if ($VersionInfo === FALSE) 
         throw new Exception(sprintf(T("Could not find version '%s' of %s"), $Version, $Addon));
      
      print_r($VersionInfo);
      echo "downloading version '{$Version}' of addon '{$Addon}'\n";
      
      $DownloadPath = CombinePaths(array($this->DownloadDir, $Addon, basename(GetValue('File',$VersionInfo))));
      $DownloadAddonName = implode('-',array($Addon,GetValue('Version',$VersionInfo)));
      
      // TODO: Fix this serve method so that it doesnt add useless bytes to the file
      //$APICall = GetValue('download', $this->RPC);
      //$Request = CombinePaths(array($this->Repository,$APICall,$DownloadAddonName,'1'));
      
      // Manually download
      $Request = GetValue('Url', $VersionInfo);
      
      $Response = $this->Request(
         $Request,
         NULL,
         array(
            'SaveFile'     => $DownloadPath,
            'SaveVerify'   => GetValue('MD5', $VersionInfo),
            'UseExisting'  => TRUE,
            'SendCookies'  => FALSE
         )
      );
      
      //if ($Response
      
   }
   
   public function RemoteAddonInfo($Addon) {
      if (!array_key_exists($Addon, $this->RemoteInfo)) {
         $APICall = GetValue('query', $this->RPC);
         $Request = CombinePaths(array($this->Repository,$APICall,$Addon));
         $AddonData = simplexml_load_file($Request);
         
         if ($AddonData === FALSE)
            throw new Exception(sprintf(T("Unable to contact remote addon repository at '%s'."),$Request));
         
         if (!GetValue('CurrentVersion', $AddonData))
            throw new Exception(sprintf(T("Requested addon '%s' was not found in the remote repository."),$Addon));
         
         $this->RemoteInfo[$Addon] = $AddonData;
      }
      
      return $this->RemoteInfo[$Addon];
   }
   
   public function VersionInfo($Addon, $Version = NULL) {
   
      // Normalize "latest" to NULL
      if ($Version == 'latest')
         $Version = NULL;
      
      try {
         $AddonInfo = $this->RemoteAddonInfo($Addon);
      } catch (Exception $e) { return FALSE; }
      
      if (is_null($Version)) 
         $Version = (string)$AddonInfo->Version;
      
      $CountVersions = sizeof($AddonInfo->Versions->Item);
      if (!$CountVersions) return FALSE;
      
      foreach ($AddonInfo->Versions->Item as $VersionElement) {
         if ($VersionElement->Version == $Version)
            return (array)$VersionElement;
      }
      return FALSE;
   }
   
   public function LatestVersion($Addon) {
      $LatestVersion = $this->VersionInfo($Addon);
      return GetValue('Version', $LatestVersion, FALSE);
   }
   
   
}