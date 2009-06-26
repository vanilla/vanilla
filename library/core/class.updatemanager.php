<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/// <namespace>
/// Lussumo.Garden.Core
/// </namespace>

/// <summary>
/// Handles building the update request, sending it, and returning results.
/// </summary>
class Gdn_UpdateManager {
   /// <prop type="array">
   /// A collection of Type => Items to check for updates.
   /// </prop>
   private $_Items;
   
   public function __construct() {
      $this->_Items = array();
   }
   
   public function AddItem($Type, $Names) {
      if (!is_array($Names))
         $Names = array($Names);
      
      foreach ($Names as $Name) {
         $this->_Items[$Type][$Name] = FALSE;
      }
   }
   
   public function GetCurrentVersion($Type, $Name) {
      return ArrayValue($Name, ArrayValue($Type, $this->_Items, array()), FALSE);
   }
   
   public function Check($Type = '', $Name = '') {
      if ($Type != '' && $Name != '')
         $this->AddItem($Type, $Name);
      
      if (count($this->_Items) > 0) {
         // TODO: Use garden update check url instead of this:
         $UpdateUrl = Url('/lussumo/update', TRUE, TRUE);
         $Host = Gdn_Url::Host();
         $Path = CombinePaths(array(Gdn_Url::WebRoot(), 'lussumo', 'update'), '/');
         $Port = 80;
         /*
         $UpdateUrl = Gdn::Config('Garden.UpdateCheckUrl', '');
         $UpdateUrl = parse_url($UpdateUrl);
         $Host = ArrayValue('host', $UpdateUrl, 'www.lussumo.com');
         $Path = ArrayValue('path', $UpdateUrl, '/');
         $Port = ArrayValue('port', $UpdateUrl, '80');
         */
         $Path .= '?Check='.urlencode(Format::Serialize($this->_Items));
         $Locale = Gdn::Config('Garden.Locale', 'Undefined');
         $Referer = Gdn_Url::WebRoot(TRUE);
         if ($Referer === FALSE)
            $Referer = 'Undefined';
            
         $Timeout = 10;
         $Response = '';

         // Connect to the update server.
         $Pointer = @fsockopen($Host, '80', $ErrorNumber, $Error, $Timeout);

         if (!$Pointer) {
            throw new Exception(sprintf(Gdn::Translate('Encountered an error when attempting to connect to the update server (%1$s): [%2$s] %3$s'), $UpdateUrl, $ErrorNumber, $Error));
         } else {
            // send the necessary headers to get the file
            fputs($Pointer, "GET $Path HTTP/1.0\r\n" .
               "Host: $Host\r\n" .
               "User-Agent: Lussumo Garden/1.0\r\n" .
               "Accept: */*\r\n" .
               "Accept-Language: ".$Locale."\r\n" .
               "Accept-Charset: utf-8;\r\n" .
               "Keep-Alive: 300\r\n" .
               "Connection: keep-alive\r\n" .
               "Referer: $Referer\r\n\r\n");
      
            // Retrieve the response from the remote server
            while ($Line = fread($Pointer, 4096)) {
               $Response .= $Line;
            }
            fclose($Pointer);
            // Remove response headers
            $Response = substr($Response, strpos($Response, "\r\n\r\n") + 4);
         }
         
         $Result = Format::Unserialize($Response);
         // print_r($Result);
         if (is_array($Result)) {
            $this->_Items = $Result;
         } else {
            $Result = FALSE;
         }

         return $Result;
      }
   }
}

