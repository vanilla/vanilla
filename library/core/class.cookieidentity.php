<?php if (!defined('APPLICATION')) exit();

/**
 * Validating, Setting, and Retrieving session data in cookies.
 * 
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com> 
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2003 Vanilla Forums, Inc
 * @license http://www.opensource.org/licenses/gpl-2.0.php GPL
 * @package Garden
 * @since 2.0
 */

class Gdn_CookieIdentity {
   
   public $UserID = NULL;
   
   public $CookieName;
   public $CookiePath;
   public $CookieDomain;
   public $VolatileMarker;
   public $CookieHashMethod;
   public $CookieSalt;
   
   const COOKIE_PERSIST_DAYS = 30;
   const COOKIE_SESSION_DAYS = 2;
   
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
      if (is_null($UserID)) {
         $this->_ClearIdentity();
         return;
      }
      
      $this->UserID = $UserID;
      
      // If we're persisting, both the cookie and its payload expire in 30days
      $PayloadExpires = time();
      if ($Persist) {
         
         $PayloadExpires += (86400 * self::COOKIE_PERSIST_DAYS);
         $CookieExpires = $PayloadExpires;
         
      // Otherwise the payload expires in 2 days and the cookie expires on borwser restart
      } else {
         // Note: $CookieExpires = 0 causes cookie to die when browser closes.
         
         $PayloadExpires += (86400 * self::COOKIE_SESSION_DAYS);
         $CookieExpires = 0;
      }

      // Create the cookie
      $KeyData = $UserID.'-'.$PayloadExpires;
      $this->_SetCookie($this->CookieName, $KeyData, array($UserID, $PayloadExpires), $CookieExpires);
      $this->SetVolatileMarker($UserID);
   }
   
   /**
    * 
    * 
    * @param integer $UserID
    * @return void
    */
   public function SetVolatileMarker($UserID) {
      if (is_null($UserID))
         return;
      
      // Note: 172800 is 60*60*24*2 or 2 days
      $PayloadExpires = time() + 172800;
      // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
      $CookieExpires = 0;
      
      $KeyData = $UserID.'-'.$PayloadExpires;
      $this->_SetCookie($this->VolatileMarker, $KeyData, array($UserID, $PayloadExpires), $CookieExpires);
   }
   
   /**
    * Set a cookie, using path, domain, salt, and hash method from core config
    * 
    * @param string  $CookieName Name of the cookie
    * @param string  $KeyData
    * @param mixed   $CookieContents
    * @param integer $CookieExpires
    * @return void
    */
   protected function _SetCookie($CookieName, $KeyData, $CookieContents, $CookieExpires) {
      self::SetCookie($CookieName, $KeyData, $CookieContents, $CookieExpires, $this->CookiePath, $this->CookieDomain, $this->CookieHashMethod, $this->CookieSalt);
   }
   
   /**
    * Set a cookie, using specified path, domain, salt and hash method
    * 
    * @param string  $CookieName Name of the cookie
    * @param string  $KeyData
    * @param mixed   $CookieContents
    * @param integer $CookieExpires
    * @param string  $Path Optional. Cookie path (auto load from config)
    * @param string  $Domain Optional. Cookie domain (auto load from config)
    * @param string  $CookieHashMethod Optional. Cookie hash method (auto load from config)
    * @param string  $CookieSalt Optional. Cookie salt (auto load from config)
    * @return void
    */
   public static function SetCookie($CookieName, $KeyData, $CookieContents, $CookieExpires, $Path = NULL, $Domain = NULL, $CookieHashMethod = NULL, $CookieSalt = NULL) {
      
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
      
      // Create the cookie signature
      $KeyHash = self::_Hash($KeyData, $CookieHashMethod, $CookieSalt);
      $KeyHashHash = self::_HashHMAC($CookieHashMethod, $KeyData, $KeyHash);
      $Cookie = array($KeyData, $KeyHashHash, time());
      
      // Attach cookie payload
      if (!is_null($CookieContents)) {
         $CookieContents = (array)$CookieContents;
         $Cookie = array_merge($Cookie, $CookieContents);
      }
      
      $CookieContents = implode('|',$Cookie);

      // Create the cookie.
      setcookie($CookieName, $CookieContents, $CookieExpires, $Path, $Domain, NULL, TRUE);
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
      
      list($HashKey, $CookieHash, $Time, $UserID, $PayloadExpires) = $CookieData;
      if ($PayloadExpires < time() && $PayloadExpires != 0) {
         self::DeleteCookie($CookieName);
         return FALSE;
      }
      $KeyHash = self::_Hash($HashKey, $CookieHashMethod, $CookieSalt);
      $CheckHash = self::_HashHMAC($CookieHashMethod, $HashKey, $KeyHash);

      if (!CompareHashDigest($CookieHash, $CheckHash)) {
         self::DeleteCookie($CookieName);
         return FALSE;
      }
      
      return TRUE;
   }
   
   public static function GetCookiePayload($CookieName, $CookieHashMethod = NULL, $CookieSalt = NULL) {
      if (!self::CheckCookie($CookieName)) return FALSE;
      
      $Payload = explode('|', $_COOKIE[$CookieName]);
      
      $Key = explode('-', $Payload[0]);
      $Expiration = array_pop($Key);
      $UserID = implode('-', $Key);
      $Payload = array_slice($Payload, 4);

      $Payload = array_merge(array($UserID, $Expiration), $Payload);
      
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

      $CurrentHost = Gdn::Request()->Host();
      if (!StringEndsWith($CurrentHost, trim($Domain, '.')))
         $Domain = '';
      
      $Expiry = time() - 60 * 60;
      setcookie($CookieName, "", $Expiry, $Path, $Domain);
      $_COOKIE[$CookieName] = NULL;
   }
   
}