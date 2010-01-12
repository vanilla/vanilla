<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Vanilla Forums Inc.
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Vanilla Forums Inc. at support [at] vanillaforums [dot] com
*/

/**
 * Handles analyzing and returning various parts of the current url.
 *
 * @author Mark O'Sullivan
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

/**
 * Handles analyzing and returning various parts of the current url.
 *
 * @package Garden
 */
class Gdn_Url {


   /**
    * Returns the path to the application's dispatcher. Optionally with the
    * domain prepended.
    *  ie. http://domain.com/[web_root]/index.php/request
    *
    * @param boolean $WithDomain Should it include the domain with the WebRoot? Default is FALSE.
    * @return string
    */
   public static function WebRoot($WithDomain = FALSE) {
      static $WebRoot = NULL;
      if(is_null($WebRoot)) {
         // Attempt to get the webroot from the configuration array
         $WebRoot = Gdn::Config('Garden.WebRoot');

         // Attempt to get the webroot from the server
         if ($WebRoot === FALSE) {
            $WebRoot = explode('/', ArrayValue('PHP_SELF', $_SERVER, ''));
            // Look for index.php to figure out where the web root is.
            $Key = array_search('index.php', $WebRoot);
            if($Key !== FALSE) {
               $WebRoot = implode('/', array_slice($WebRoot, 0, $Key));
            } else {
               $WebRoot = '';
            }
         }
      }
      
      if (is_string($WebRoot) && $WebRoot != '') {
         // Strip forward slashes from the beginning of webroot
         return ($WithDomain ? Gdn_Url::Domain() : '') . preg_replace('/(^\/+)/', '', $WebRoot);
      } else {
         return $WithDomain ? Gdn_Url::Domain() : '';
      }
   }


   /**
    * Returns the domain from the current url. ie. "http://localhost/" in
    * "http://localhost/this/that/garden/index.php/controller/action/"
    *
    * @return string
    */
   public static function Domain() {
      // Attempt to get the domain from the configuration array
      $Domain = Gdn::Config('Garden.Domain', '');

      if ($Domain === FALSE || $Domain == '')
         $Domain = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
      
      if ($Domain != '' && $Domain !== FALSE) {
         if (substr($Domain, 0, 7) != 'http://')
            $Domain = 'http://'.$Domain;

         if (substr($Domain, -1, 1) != '/')
            $Domain = $Domain . '/';
      }
      return $Domain;
   }


   /**
    * Returns the host from the current url. ie. "localhost" in
    * "http://localhost/this/that/garden/index.php/controller/action/"
    *
    * @return string
    */
   public static function Host() {
      $Host = '';
      if (isset($_SERVER['HTTP_HOST']))
         $Host = $_SERVER['HTTP_HOST'];

      return $Host;
   }


   /**
    * Returns any GET parameters from the querystring. ie. "this=that&y=n" in
    * http://localhost/index.php/controller/action/?this=that&y=n"
    *
    * @return string
    */
   public static function QueryString() {
      $Return = '';
      if (is_array($_GET)) {
         foreach($_GET as $Key => $Value) {
            if ($Return != '')
               $Return .= '&';

            $Return .= urlencode($Key) . '=' . urlencode($Value);
         }
      }
      return $Return;
   }


   /**
    * Returns the Request part of the current url. ie. "/controller/action/" in
    * "http://localhost/garden/index.php/controller/action/".
    *
    * @param boolean $WithWebRoot
    * @param boolean $WithDomain
    * @param boolean $RemoveSyndication
    * @return string
    */
   public static function Request($WithWebRoot = FALSE, $WithDomain = FALSE, $RemoveSyndication = FALSE) {
      $Return = '';
      // TODO: Test this on various platforms/browsers. Very breakable.

      // Try PATH_INFO
      $Request = (isset($_SERVER['PATH_INFO'])) ? $_SERVER['PATH_INFO'] : @getenv('PATH_INFO');
      if ($Request) {
         $Return = $Request;
      }

      // Try ORIG_PATH_INFO
      if (!$Return) {
         $Request = (isset($_SERVER['ORIG_PATH_INFO'])) ? $_SERVER['ORIG_PATH_INFO'] : @getenv('ORIG_PATH_INFO');
         if ($Request != '') {
            $Return = $Request;
         }

      }
      // Try with PHP_SELF
      if (!$Return) {
         $PhpSelf = (isset($_SERVER['PHP_SELF'])) ? $_SERVER['PHP_SELF'] : @getenv('PHP_SELF');
         $ScriptName = (isset($_SERVER['SCRIPT_NAME'])) ? $_SERVER['SCRIPT_NAME'] : @getenv('SCRIPT_NAME');
         
         if ($PhpSelf && $ScriptName) {
            $Return = substr($PhpSelf, strlen($ScriptName));
         }

      }
      
      $Return = trim($Return, '/');
      if (strcasecmp(substr($Return, 0, 9), 'index.php') == 0)
         $Return = substr($Return, 9);
         
      $Return = trim($Return, '/');

      if ($RemoveSyndication) {
         $Prefix = strtolower(substr($Return, 0, strpos($Return, '/')));
         if ($Prefix == 'rss')
            $Return = substr($Return, 4);
         else if ($Prefix == 'atom')
            $Return = substr($Return, 5);
      }

      if ($WithWebRoot) {
         $WebRoot = Gdn_Url::WebRoot($WithDomain);
         if (substr($WebRoot, -1, 1) != '/')
            $WebRoot .= '/';

         $Return = $WebRoot . $Return;
      }

      return $Return;
   }
}
