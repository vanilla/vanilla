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
   
   const STATE_ALPHA = 1;
   const STATE_BETA = 2;
   const STATE_RC = 3;
   const STATE_STABLE = 5;
   
   public function __construct(&$UpdateModel = NULL) {
      $this->UpdateModel = $UpdateModel;
   
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
         'DisplayName'     => NULL,
         'SaveFile'        => FALSE,
         'SaveVerify'      => FALSE,
         'SaveProgress'    => NULL,
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
      
      // Are we saving this to a file?
      $SaveFile = (GetValue('SaveFile', $Options) !== FALSE);
      
      // If we want to save the response to a file, handle the FP and existing issues
      if ($SaveFile === TRUE) {
         $Filename = GetValue('SaveFile', $Options);
         
         // Work out what the real filepath should be if we received a directory
         if (is_dir($Filename)) {
            $RequestFilename = basename($Path);
            $Filename = CombinePaths(array($Filename, $RequestFilename));
         }
         
         if (is_null(GetValue('DisplayName',$Options,NULL))) {
            $RequestFilename = basename($Path);
            $Options['DisplayName'] = $RequestFilename;
         }
         
         // Check if the folder exists at least
         $VacantSpace = Gdn_Filesystem::CheckFolderR(dirname($Filename), Gdn_Filesystem::O_CREATE);
         
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
            
         $TransferDataHandler = new TransferDataHandler($Options);
         $TransferDataHandler->SetMode('file', $Filename);
      }
      
      // Pick request mode based on feature availability and priority
      if (function_exists('curl_init') && 1 == 0) {
         
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
            
            // Point cURL at the File Data Handler for storing received data.
            curl_setopt($Handler, CURLOPT_WRITEFUNCTION, array($TransferDataHandler, 'CurlWrite'));
            curl_setopt($Handler, CURLOPT_HEADERFUNCTION, array($TransferDataHandler, 'CurlHeader'));
         }
         
         $TransferDataHandler->Start(microtime(true));
         $Response = curl_exec($Handler);
         $TransferDataHandler->End(microtime(true));
         $Success = TRUE;
         if ($Response === FALSE) {
            $Success = FALSE;
            $Response = curl_error($Handler);
         }
         
         curl_close($Handler);
      } else if (function_exists('fsockopen')) {
         
         /**
          * fsockopen mode
          *  - manually constructs HTTP requests
          */
   
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
         
         $TransferDataHandler->Start(microtime(true));
         // Construct the request handle
         $InPointer = @fsockopen($Host, $Port, $ErrorNumber, $Error, $Timeout);
         if (!$InPointer)
            throw new Exception(sprintf(T('Encountered an error while making a request to the remote server (%1$s): [%2$s] %3$s'), $Url, $ErrorNumber, $Error));
         
         // Send the request headers
         $HeaderString = implode("\r\n", $Header);
         fputs($InPointer, $HeaderString);
         
         // Have we seen the end of the headers yet?
         $DetectedHeaderBreak = FALSE;
         
         // Read back the response
         while ($InData = fread($InPointer, $BufferSize)) {
            if (!$DetectedHeaderBreak) {
               
               // Start off assuming we have received just header data here
               $HeaderData = $InData;
               
               // Check if we received data on the boundary between header/body
               $HeaderBreakPosition = strpos($HeaderData, "\r\n\r\n");
               
               // Found header boundary in this buffer
               if ($HeaderBreakPosition !== FALSE) {
                  $DetectedHeaderBreak = TRUE;
                  $HeaderData = substr($InData, 0, $HeaderBreakPosition+4);
                  
                  // Real data is whatever comes after the headers
                  $InData = substr($InData, $HeaderBreakPosition+4);
               } else {
                  $InData = NULL;
               }
               
               // Store header data
               if (strlen($HeaderData))
                  $TransferDataHandler->Header($HeaderData);
            }
            
            // Store actual data
            if (!is_null($InData))
               $TransferDataHandler->Store($InData);
         }
         @fclose($InPointer);
         $TransferDataHandler->End(microtime(true));
            
         $Success = TRUE;
      } else {
         throw new Exception(T('Encountered an error while making a request to the remote server: Your PHP configuration does not allow curl or fsock requests.'));
      }
      
      if (!$Success)
         throw new Exception(sprintf(T('Error performing request: %s'), $Response));
      
      $Response = trim(substr($Response, strpos($Response, "\r\n\r\n") + 4));
      $ResponseHeaders = $TransferDataHandler->Headers();
      
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
            $Success = ($SaveVerify == $DownloadedVerify);
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
      
      $DownloadPath = CombinePaths(array($this->DownloadDir, $Addon, basename(GetValue('File',$VersionInfo))));
      $DownloadAddonName = implode('-',array($Addon,GetValue('Version',$VersionInfo)));
      
      // TODO: Fix this serve method so that it doesnt add useless bytes to the file
      //$APICall = GetValue('download', $this->RPC);
      //$Request = CombinePaths(array($this->Repository,$APICall,$DownloadAddonName,'1'));
      
      // Manually download
      $Request = GetValue('Url', $VersionInfo);
      $MD5 = GetValue('MD5', $VersionInfo);
      
      // Testing data
      //$Request = 'http://www.vanillaforums.org/uploads/test/test.txt';
      //$MD5 = '364449cc86eda14a5955aabda9a1fa2c';
      
      $DisplayName = $Addon.' v'.GetValue('Version',$VersionInfo);
      
      $Response = $this->Request(
         $Request,
         NULL,
         array(
            'SaveFile'     => $DownloadPath,
            'SaveVerify'   => $MD5,
            'SaveProgress' => $this->UpdateModel,
            'BufferSize'   => 256,
            'DisplayName'  => $DisplayName,
            'UseExisting'  => !$ForceFresh,
            'SendCookies'  => FALSE
         )
      );
      
      if (!is_array($Response))
         throw new Exception($Response);
         
      $FileData = GetValue('File', $Response, FALSE);
      if ($FileData === FALSE) 
         throw new Exception(T('Invalid data format returned from DownloadModel::Request()'));
         
      $Success = GetValue('Success', $FileData, FALSE);
      if ($Success)
         return GetValue('Path', $FileData);
      return FALSE;
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
         
         $this->RemoteInfo[$Addon] = array(
            'XML'       => NULL,
            'Versions'  => array(),
            'Stability' => array()
         );
         $this->RemoteInfo[$Addon]['XML'] = $AddonData;
         $this->ParseAddonInfo($Addon);
      }
      
      return $this->RemoteInfo[$Addon]['XML'];
   }
   
   protected function ParseAddonInfo($Addon) {
      if (!array_key_exists($Addon, $this->RemoteInfo)) return FALSE;
      $AddonInfo = &$this->RemoteInfo[$Addon];
      foreach ($AddonInfo->Versions->Item as $VersionElement) {
         $VersionNumber = $VersionElement->Version;
         $Stability = $this->GetStabilityFromVersion($VersionNumber);
      }
   }
   
   public function GetStabilityFromVersion($Version) {
      if (substr($Version,-1) == 'a') return DownloadModel::STATE_ALPHA;
      if (substr($Version,-1) == 'b') return DownloadModel::STATE_BETA;
      if (preg_match('/rc[\d]?$/i', $Version)) return DownloadModel::STATE_RC;
      return DownloadModel::STATE_STABLE;
   }
   
   public function VersionInfo($Addon, $Version = NULL, $MinimumState) {
   
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
   
   public function LatestVersion($Addon, $MinimumState = NULL) {
      $LatestVersion = $this->VersionInfo($Addon);
      return GetValue('Version', $LatestVersion, FALSE);
   }
   
}

/**
 * File transfer handler
 *
 * This object encapsulates the process of saving a file to disk, packetwise.
 * It also handles the UpdateModel->Progress and keeps that value updated.
 */
class TransferDataHandler {

   protected $Options;
   protected $UpdateModel;
   protected $Mode = 'receive';
      
   protected $FilePointer;
   protected $Filename;
   
   protected $ResponseData;
   protected $HeaderData;
   
   protected $TransferInfo;
   protected $Receiving = FALSE;
   
   protected $SpeedStack;
   
   public function __construct($Options) {
      $this->Options = $Options;
      $this->LogFile = fopen('/www/vanilla/vanilla/cache/download.log', 'w');
      
      if (array_key_exists('SaveProgress', $Options) && !is_null($Options['SaveProgress']) && $Options['SaveProgress'] instanceof VanillaUpdateModel)
         $this->UpdateModel = $Options['SaveProgress'];
      else
         $this->UpdateModel = NULL;
         
      $this->TransferInfo = array(
         'Size'      => 0,
         'Received'  => 0,
         'Start'     => 0,
         'End'       => 0,
         'Elapsed'   => 0
      );
   }
   
   public function SetMode($Mode, $Option = NULL) {
      switch ($Mode) {
         case 'file':
            $this->Mode = 'file';
            $this->Filename = $Option;
            $this->FilePointer = @fopen($Option, 'wb');
            
            if (!$this->FilePointer)
               throw new Exception(sprintf(T('Could not open target file %s for writing'), $Option));
               
         break;
         
         default:
            $this->Mode = 'receive';
            $this->ResponseData = ''; 
         break;
      }
   }
   
   public function SetTransferInfo($Option, $Value) {
      $this->TransferInfo[$Option] = $Value;
   }
   
   public function Start($StartTime = NULL) {
      if (!is_null($StartTime))
         $this->SetTransferInfo('Start', $StartTime);
         
      return GetValue('Start', $this->TransferInfo, 0);
   }
   
   public function End($EndTime = NULL) {
      if (!is_null($EndTime))
         $this->SetTransferInfo('End', $EndTime);
         
      return GetValue('End', $this->TransferInfo, 0);
   }
   
   public function Elapsed() {
      $DiffValue = $this->End();
      if ($DiffValue == 0) $DiffValue = microtime(true);
      
      return $DiffValue - GetValue('Start', $this->TransferInfo, microtime(true));
   }
   
   public function Received($Bytes = NULL) {
      if (!is_null($Bytes)) {
         // Increment
         if (substr($Bytes, 0, 1) == '+') {
            $Bytes = substr($Bytes, 1) + $this->Received();
         }
         
         // Otherwise its a straight set
         $this->SetTransferInfo('Received', $Bytes);
      }
      return GetValue('Received', $this->TransferInfo, 0);
   }
   
   public function Size($Bytes = NULL) {
      if (!is_null($Bytes)) {
         // Otherwise its a straight set
         $this->SetTransferInfo('Size', $Bytes);
      }
      return GetValue('Size', $this->TransferInfo, 0);
   }
   
   // KB/sec
   public function Speed() {
      $R = $this->Received() / 1024;
      $S = $this->Size() / 1024;
      $T = $this->Elapsed();
      
      $Rate = round($R / $T, 2);
      return $Rate;
   }
   
   public function AverageSpeed() {
      if (!is_array($this->SpeedStack))
         $this->SpeedStack = array();
         
      while (sizeof($this->SpeedStack) >= 10)
         array_shift($this->SpeedStack);
         
      array_push($this->SpeedStack, $this->Speed());
      return round(array_sum($this->SpeedStack) / sizeof($this->SpeedStack),2);
   }
   
   public function Response() {
      return $this->ResponseData;
   }
   
   public function CurlHeader($Curl, $Data) {
      $Bytes = $this->Header($Data);
      return $Bytes;
   }
   
   public function CurlWrite($Curl, $Data) {
      $Bytes = $this->Store($Data);
      return $Bytes;
   }
   
   public function Store($Data) {
      if (!$this->Receiving)
         $this->ParseHeaders();
      
      switch ($this->Mode) {
         case 'file':
            $Bytes = fwrite($this->FilePointer, $Data);
         break;
         
         case 'receive':
            $this->ResponseData .= $Data;
            $Bytes = strlen($Data);
         break;
      }
      
      $this->Progress($Bytes);
      return $Bytes;
   }
   
   public function Header($Data) {
      $this->HeaderData .= $Data;
      $Bytes = strlen($Data);
      return $Bytes;
   }
   
   public function Progress($WrittenBytes = NULL) {
      if (!is_null($WrittenBytes)) {
         $this->Received('+'.$WrittenBytes);
         
         if ($this->UpdateModel instanceof VanillaUpdateModel) {
            $ProgressPercent = round(($this->Received() / $this->Size()) * 100,2);
            $LastProgress = $this->UpdateModel->Progress('download', 'get');
            
            if (floor($ProgressPercent) > floor($LastProgress)) {
               // Speed in kilobytes per second.
               $DownloadSpeed = $this->AverageSpeed();
               $DownloadMessage = sprintf(T("Downloading %s (%s kb/s)"), GetValue('DisplayName', $this->Options), $DownloadSpeed);
               
               $this->UpdateModel->SetMeta('download/message', $DownloadMessage);
               $this->UpdateModel->Progress('download', 'get', $ProgressPercent, TRUE);
            }
         }
      }
   }
   
   protected function ParseHeaders() {
      $Headers = $this->Headers();
      foreach ($Headers as $HeaderKey => $HeaderValue) {
         switch ($HeaderKey) {
            case 'Content-Length':
               $this->Size($HeaderValue);
            break;
            
            case 'HTTP':
               $this->SetTransferInfo('Status', $HeaderValue);
            break;
         }
      }
      $this->Receiving = TRUE;
   }
   
   public function Headers() {
      $ResponseHeaderLines = explode("\n",trim($this->HeaderData));
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
      
      return $ResponseHeaders;
   }
   
   public function RawHeaders() {
      return $this->HeaderData;
   }
   
}