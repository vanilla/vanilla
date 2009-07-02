<?php if (!defined('APPLICATION')) exit();
/*
Copyright 2008, 2009 Mark O'Sullivan
This file is part of Garden.
Garden is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
Garden is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with Garden.  If not, see <http://www.gnu.org/licenses/>.
Contact Mark O'Sullivan at mark [at] lussumo [dot] com
*/

/**
 * Validating, Setting, and Retrieving session data in cookies.
 * @author Mark O'Sullivan, Todd Burry
 * @copyright 2009 Mark O'Sullivan
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @version @@GARDEN-VERSION@@
 * @namespace Lussumo.Garden.Core
 */

class Gdn_CookieIdentity {
	/**
    * Destroys the user's session cookie - essentially de-authenticating them.
    */
   protected function _ClearIdentity() {
      // Retrieve information about the cookie.
      $CookieName = Gdn::Config('Garden.Cookie.Name', 'GardenCookie');
      $CookiePath = Gdn::Config('Garden.Cookie.Path', '/');
      $CookieDomain = Gdn::Config('Garden.Cookie.Domain', '');
      // Destroy the cookie.
      setcookie($CookieName, ' ', time() - 3600, $CookiePath, $CookieDomain);
      unset($_COOKIE[$CookieName]);
   }
	
	/**
    * Returns the unique id assigned to the user in the database (retrieved
    * from the session cookie if the cookie authenticates) or FALSE if not
    * found or authentication fails.
    *
    * @return int
    */
   public function GetIdentity() {
      $CookieName = Gdn::Config('Garden.Cookie.Name', 'LussumoCookie');
      $HashMethod = Gdn::Config('Garden.Cookie.HashMethod', '');
      if (empty($_COOKIE[$CookieName]))
         return 0;

      list($UserID, $Expiration, $HMAC) = explode('|', $_COOKIE[$CookieName]);
      if ($Expiration < time())
         return 0;

      $Key = $this->_Hash($UserID . $Expiration);
      $Hash = $this->_HashHMAC($HashMethod, $UserID . $Expiration, $Key);

      if ($HMAC != $Hash)
         return 0;

      if (!is_numeric($UserID) || $UserID <= 0)
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
      $Salt = Gdn::Config('Garden.Cookie.Salt');
      $HashMethod = Gdn::Config('Garden.Cookie.HashMethod');
      if (empty($Salt))
         trigger_error(ErrorMessage("The server's salt key has not been configured.", 'Session', 'Hash'), E_USER_ERROR);

      return $this->_HashHMAC($HashMethod, $Data, $Salt);
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
      // Load some configuration settings.
      $CookieName = Gdn::Config('Garden.Cookie.Name');
      $CookiePath = Gdn::Config('Garden.Cookie.Path');
      $CookieDomain = Gdn::Config('Garden.Cookie.Domain');
      $HashMethod = Gdn::Config('Garden.Cookie.HashMethod');

      // Create the cookie contents
      $Key = $this->_Hash($UserID . $Expiration);
      $Hash = $this->_HashHMAC($HashMethod, $UserID . $Expiration, $Key);
      $CookieContents = $UserID . '|' . $Expiration . '|' . $Hash;

      // Create the cookie.
      setcookie($CookieName, $CookieContents, $Expire, $CookiePath, $CookieDomain);
   }
	
}