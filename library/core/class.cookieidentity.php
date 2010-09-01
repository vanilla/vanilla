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
 * Validating, Setting, and Retrieving session data in cookies.
 * @author Mark O'Sullivan, Todd Burry
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Garden.Core
 */

class Gdn_CookieIdentity {
   
   public $UserID = NULL;
   
   public $CookieName;
   public $CookiePath;
   public $CookieDomain;
   public $VolatileMarker;
   public $CookieHashMethod;
   public $CookieSalt;
   
   public function __construct($Config = NULL) {
      $this->Init($Config);
   }
   
   public function Init($Config = NULL) {
      if (is_null($Config))
         $Config = Gdn::Config('Garden.Cookie');
      elseif(is_string($Config))
         $Config = Gdn::Config($Config);
         
      $DefaultConfig = Gdn::Config('Garden.Cookie');         
      $this->CookieName = ArrayValue('Name', $Config, $DefaultConfig['Name']);
      $this->CookiePath = ArrayValue('Path', $Config, $DefaultConfig['Path']);
      $this->CookieDomain = ArrayValue('Domain', $Config, $DefaultConfig['Domain']);
      $this->CookieHashMethod = ArrayValue('HashMethod', $Config, $DefaultConfig['HashMethod']);
      $this->CookieSalt = ArrayValue('Salt', $Config, $DefaultConfig['Salt']);
      $this->VolatileMarker = $this->CookieName.'-Volatile';
   }
   
   /**
    * Destroys the user's session cookie - essentially de-authenticating them.
    */
   protected function _ClearIdentity() {
      // Destroy the cookie.
      $this->UserID = 0;
      $this->_DeleteCookie($this->CookieName);
   }
   
   /**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity() {
      if (!is_null($this->UserID))
         return $this->UserID;
         
      if (!$this->_CheckCookie($this->CookieName)) {
         $this->_ClearIdentity();
         return 0;
      }
      
      list($UserID, $Expiration) = $this->GetCookiePayload($this->CookieName);
      
      if (!is_numeric($UserID) || $UserID < -2) // allow for handshake special id
         return 0;

      return $this->UserID = $UserID;
   }
   
   public function HasVolatileMarker($CheckUserID) {
      $HasMarker = $this->CheckVolatileMarker($CheckUserID);
      if (!$HasMarker)
         $this->SetVolatileMarker($CheckUserID);
      
      return $HasMarker;
   }
   
   public function CheckVolatileMarker($CheckUserID) {
      if (!$this->_CheckCookie($this->VolatileMarker)) return FALSE;
      
      list($UserID, $Expiration) = $this->GetCookiePayload($this->CookieName);
      
      if ($UserID != $CheckUserID)
         return FALSE;

      return TRUE;
   }
   
   /**
    * Returns $this->_HashHMAC with the provided data, the default hashing method
    * (md5), and the server's COOKIE.SALT string as the key.
    *
    * @param string $Data The data to place in the hash.
    */
   protected static function _Hash($Data, $CookieHashMethod, $CookieSalt) {
      return Gdn_CookieIdentity::_HashHMAC($CookieHashMethod, $Data, $CookieSalt);
   }
   
   /**
    * Returns the provided data hashed with the specified method using the
    * specified key.
    *
    * @param string $HashMethod The hashing method to use on $Data. Options are MD5 or SHA1.
    * @param string $Data The data to place in the hash.
    * @param string $Key The key to use when hashing the data.
    */
   protected static function _HashHMAC($HashMethod, $Data, $Key) {
      $PackFormats = array('md5' => 'H32', 'sha1' => 'H40');

      if (!isset($PackFormats[$HashMethod]))
         return false;

      $PackFormat = $PackFormats[$HashMethod];
      // this is the equivalent of "strlen($Key) > 64":
      if (isset($Key[63]))
         $Key = pack($PackFormat, $HashMethod($Key));
      else
         $Key = str_pad($Key, 64, chr(0));

      $InnerPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x36), 64));
      $OuterPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x5C), 64));

      return $HashMethod($OuterPad . pack($PackFormat, $HashMethod($InnerPad . $Data)));
   }
   
   /**
    * Generates the user's session cookie.
    *
    * @param int $UserID The unique id assigned to the user in the database.
    * @param boolean $Persist Should the user's session remain persistent across visits?
    */
   public function SetIdentity($UserID, $Persist = FALSE) {
      if(is_null($UserID)) {
         $this->_ClearIdentity();
         return;
      }
      
      $this->UserID = $UserID;
      
      if ($Persist !== FALSE) {
         // Note: 2592000 is 60*60*24*30 or 30 days
         $Expiration = $Expire = time() + 2592000;
      } else {
         // Note: 172800 is 60*60*24*2 or 2 days
         $Expiration = time() + 172800;
         // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
         $Expire = 0;
      }

      // Create the cookie.
      $KeyData = $UserID.'-'.$Expiration;
      $this->_SetCookie($this->CookieName, $KeyData, array($UserID, $Expiration), $Expire);
      $this->SetVolatileMarker($UserID);
   }
   
   public function SetVolatileMarker($UserID) {
      if (is_null($UserID))
         return;
      
      // Note: 172800 is 60*60*24*2 or 2 days
      $Expiration = time() + 172800;
      // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
      $Expire = 0;
      
      $KeyData = $UserID.'-'.$Expiration;
      $this->_SetCookie($this->VolatileMarker, $KeyData, array($UserID, $Expiration), $Expire);
   }
   
   protected function _SetCookie($CookieName, $KeyData, $CookieContents, $Expire) {
      self::SetCookie($CookieName, $KeyData, $CookieContents, $Expire, $this->CookiePath, $this->CookieDomain, $this->CookieHashMethod, $this->CookieSalt);
   }
   
   public static function SetCookie($CookieName, $KeyData, $CookieContents, $Expire, $Path = NULL, $Domain = NULL, $CookieHashMethod = NULL, $CookieSalt = NULL) {
      
      if (is_null($Path))
         $Path = Gdn::Config('Garden.Cookie.Path', '/');

      if (is_null($Domain))
         $Domain = Gdn::Config('Garden.Cookie.Domain', '');

      // If the domain being set is completely incompatible with the current domain then make the domain work.
      $CurrentHost = Gdn::Request()->Host();
      if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
         $Domain = '';
   
      if (!$CookieHashMethod)
         $CookieHashMethod = Gdn::Config('Garden.Cookie.HashMethod');
      
      if (!$CookieSalt)
         $CookieSalt = Gdn::Config('Garden.Cookie.Salt');
      
      // Create the cookie contents
      $Key = self::_Hash($KeyData, $CookieHashMethod, $CookieSalt);
      $Hash = self::_HashHMAC($CookieHashMethod, $KeyData, $Key);
      $Cookie = array($KeyData,$Hash,time());
      if (!is_null($CookieContents)) {
         if (!is_array($CookieContents)) $CookieContents = array($CookieContents);
         $Cookie = array_merge($Cookie, $CookieContents);
      }
         
      $CookieContents = implode('|',$Cookie);

      // Create the cookie.
      setcookie($CookieName, $CookieContents, $Expire, $Path, $Domain);
      $_COOKIE[$CookieName] = $CookieContents;
   }
   
   protected function _CheckCookie($CookieName) {
      $CookieStatus = self::CheckCookie($CookieName, $this->CookieHashMethod, $this->CookieSalt);
      if ($CookieStatus === FALSE)
         $this->_DeleteCookie($CookieName);
      return $CookieStatus;
   }
   
   public static function CheckCookie($CookieName, $CookieHashMethod = NULL, $CookieSalt = NULL) {
      if (empty($_COOKIE[$CookieName])) {
         return FALSE;
      }
      
      if (is_null($CookieHashMethod))
         $CookieHashMethod = Gdn::Config('Garden.Cookie.HashMethod');
      
      if (is_null($CookieSalt))
         $CookieSalt = Gdn::Config('Garden.Cookie.Salt');
      
      $CookieData = explode('|', $_COOKIE[$CookieName]);
      if (count($CookieData) < 5) {
         self::DeleteCookie($CookieName);
         return FALSE;
      }
      
      list($HashKey, $CookieHash, $Time, $UserID, $Expiration) = $CookieData;
      if ($Expiration < time() && $Expiration != 0) {
         self::DeleteCookie($CookieName);
         return FALSE;
      }
      
      $Key = self::_Hash($HashKey, $CookieHashMethod, $CookieSalt);
      $GeneratedHash = self::_HashHMAC($CookieHashMethod, $HashKey, $Key);

      if ($CookieHash != $GeneratedHash) {
         self::DeleteCookie($CookieName);
         return FALSE;
      }
      
      return TRUE;
   }
   
   public static function GetCookiePayload($CookieName, $CookieHashMethod = NULL, $CookieSalt = NULL) {
      if (!self::CheckCookie($CookieName)) return FALSE;
      
      $Payload = explode('|', $_COOKIE[$CookieName]);
      
      // Get rid of check fields like HashKey, HMAC and Time
      array_shift($Payload);
      array_shift($Payload);
      array_shift($Payload);
      
      return $Payload;
   }
   
   protected function _DeleteCookie($CookieName) {
      unset($_COOKIE[$CookieName]);
      self::DeleteCookie($CookieName, $this->CookiePath, $this->CookieDomain);
   }
   
   public static function DeleteCookie($CookieName, $Path = NULL, $Domain = NULL) {

      if (is_null($Path))
         $Path = Gdn::Config('Garden.Cookie.Path');

      if (is_null($Domain))
         $Domain = Gdn::Config('Garden.Cookie.Domain');
      
      $Expiry = strtotime('one year ago');
      setcookie($CookieName, "", $Expiry, $Path, $Domain);
      $_COOKIE[$CookieName] = NULL;
   }
   
}