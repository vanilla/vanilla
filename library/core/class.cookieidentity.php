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
   
   public $CookieName;
   public $CookiePath;
   public $CookieDomain;
   public $CookieHashMethod;
   public $CookieSalt;
   
   public function __contruct($Config = NULL) {
      $this->Init($Config);
   }
   
   public function Init($Config = NULL) {
      if(is_null($Config))
         $Config = Gdn::Config('Garden.Cookie');
      elseif(is_string($Config))
         $Config = Gdn::Config($Config);
         
      $DefaultConfig = Gdn::Config('Garden.Cookie');         
      $this->CookieName = ArrayValue('Name', $Config, $DefaultConfig['Name']);
      $this->CookiePath = ArrayValue('Path', $Config, $DefaultConfig['Path']);
      $this->CookieDomain = ArrayValue('Domain', $Config, $DefaultConfig['Domain']);
      $this->CookieHashMethod = ArrayValue('HashMethod', $Config, $DefaultConfig['HashMethod']);
      $this->CookieSalt = ArrayValue('Salt', $Config, $DefaultConfig['Salt']);
   }
   
   /**
    * Destroys the user's session cookie - essentially de-authenticating them.
    */
   protected function _ClearIdentity() {
      // Destroy the cookie.
      setcookie($this->CookieName, ' ', time() - 3600, $this->CookiePath, $this->CookieDomain);
      unset($_COOKIE[$this->CookieName]);
   }
   
   /**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity() {
      if (empty($_COOKIE[$this->CookieName]))
         return 0;

      list($UserID, $Expiration, $HMAC) = explode('|', $_COOKIE[$this->CookieName]);
      if ($Expiration < time())
         return 0;

      $Key = $this->_Hash($UserID . $Expiration);
      $Hash = $this->_HashHMAC($this->CookieHashMethod, $UserID . $Expiration, $Key);

      if ($HMAC != $Hash)
         return 0;

      if (!is_numeric($UserID) || $UserID < -2) // allow for handshake special id
         return 0;

      return $UserID;
   }
   
   /**
    * Returns $this->_HashHMAC with the provided data, the default hashing method
    * (md5), and the server's COOKIE.SALT string as the key.
    *
    * @param string $Data The data to place in the hash.
    */
   private function _Hash($Data) {
      if (empty($this->CookieSalt))
         trigger_error(ErrorMessage("The server's salt key has not been configured.", 'Session', 'Hash'), E_USER_ERROR);

      return $this->_HashHMAC($this->CookieHashMethod, $Data, $this->CookieSalt);
   }
   
   /**
    * Returns the provided data hashed with the specified method using the
    * specified key.
    *
    * @param string $HashMethod The hashing method to use on $Data. Options are MD5 or SHA1.
    * @param string $Data The data to place in the hash.
    * @param string $Key The key to use when hashing the data.
    */
   private function _HashHMAC($HashMethod, $Data, $Key) {
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
      }
      
      if ($Persist !== FALSE) {
         // Note: 2592000 is 60*60*24*30 or 30 days
         $Expiration = $Expire = time() + 2592000;
      } else {
         // Note: 172800 is 60*60*24*2 or 2 days
         $Expiration = time() + 172800;
         // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
         $Expire = 0;
      }

      // Create the cookie contents
      $Key = $this->_Hash($UserID . $Expiration);
      $Hash = $this->_HashHMAC($this->CookieHashMethod, $UserID . $Expiration, $Key);
      $CookieContents = $UserID . '|' . $Expiration . '|' . $Hash;

      // Create the cookie.
      setcookie($this->CookieName, $CookieContents, $Expire, $this->CookiePath, $this->CookieDomain);
   }
   
}