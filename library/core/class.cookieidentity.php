<?php
/**
 * Gdn_CookieIdentity
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

/**
 * Validating, Setting, and Retrieving session data in cookies.
 */
class Gdn_CookieIdentity {

    /** @var int|null */
    public $UserID = null;

    /** @var string */
    public $CookieName;

    /** @var string */
    public $CookiePath;

    /** @var string */
    public $CookieDomain;

    /** @var string */
    public $VolatileMarker;

    /** @var bool */
    public $CookieHashMethod;

    /** @var string */
    public $CookieSalt;

    /** @var string */
    public $PersistExpiry = '30 days';

    /** @var string */
    public $SessionExpiry = '2 days';

    /**
     *
     *
     * @param null $Config
     */
    public function __construct($Config = null) {
        $this->init($Config);
    }

    /**
     *
     *
     * @param null $Config
     */
    public function init($Config = null) {
        if (is_null($Config)) {
            $Config = Gdn::config('Garden.Cookie');
        } elseif (is_string($Config))
            $Config = Gdn::config($Config);

        $DefaultConfig = array_replace(
            array('PersistExpiry' => '30 days', 'SessionExpiry' => '2 days'),
            Gdn::config('Garden.Cookie')
        );
        $this->CookieName = val('Name', $Config, $DefaultConfig['Name']);
        $this->CookiePath = val('Path', $Config, $DefaultConfig['Path']);
        $this->CookieDomain = val('Domain', $Config, $DefaultConfig['Domain']);
        $this->CookieHashMethod = val('HashMethod', $Config, $DefaultConfig['HashMethod']);
        $this->CookieSalt = val('Salt', $Config, $DefaultConfig['Salt']);
        $this->VolatileMarker = $this->CookieName.'-Volatile';
        $this->PersistExpiry = val('PersisExpiry', $Config, $DefaultConfig['PersistExpiry']);
        $this->SessionExpiry = val('SessionExpiry', $Config, $DefaultConfig['SessionExpiry']);
    }

    /**
     * Destroys the user's session cookie - essentially de-authenticating them.
     */
    protected function _clearIdentity() {
        // Destroy the cookie.
        $this->UserID = 0;
        $this->_deleteCookie($this->CookieName);
    }

    /**
     * Returns the unique id assigned to the user in the database (retrieved
     * from the session cookie if the cookie authenticates) or FALSE if not
     * found or authentication fails.
     *
     * @return int
     */
    public function getIdentity() {
        if (!is_null($this->UserID)) {
            return $this->UserID;
        }

        if (!$this->_checkCookie($this->CookieName)) {
            $this->_clearIdentity();
            return 0;
        }

        list($UserID, $Expiration) = $this->getCookiePayload($this->CookieName);

        if (!is_numeric($UserID) || $UserID < -2) { // allow for handshake special id
            return 0;
        }

        return $this->UserID = $UserID;
    }

    /**
     *
     *
     * @param $CheckUserID
     * @return bool
     */
    public function hasVolatileMarker($CheckUserID) {
        $HasMarker = $this->checkVolatileMarker($CheckUserID);
        if (!$HasMarker) {
            $this->setVolatileMarker($CheckUserID);
        }

        return $HasMarker;
    }

    /**
     *
     *
     * @param $CheckUserID
     * @return bool
     */
    public function checkVolatileMarker($CheckUserID) {
        if (!$this->_CheckCookie($this->VolatileMarker)) {
            return false;
        }

        list($UserID, $Expiration) = $this->getCookiePayload($this->CookieName);

        if ($UserID != $CheckUserID) {
            return false;
        }

        return true;
    }

    /**
     * Returns $this->_HashHMAC with the provided data, the default hashing method
     * (md5), and the server's COOKIE.SALT string as the key.
     *
     * @param string $Data The data to place in the hash.
     */
    protected static function _hash($Data, $CookieHashMethod, $CookieSalt) {
        return Gdn_CookieIdentity::_hashHMAC($CookieHashMethod, $Data, $CookieSalt);
    }

    /**
     * Returns the provided data hashed with the specified method using the
     * specified key.
     *
     * @param string $HashMethod The hashing method to use on $Data. Options are MD5 or SHA1.
     * @param string $Data The data to place in the hash.
     * @param string $Key The key to use when hashing the data.
     */
    protected static function _hashHMAC($HashMethod, $Data, $Key) {
        $PackFormats = array('md5' => 'H32', 'sha1' => 'H40');

        if (!isset($PackFormats[$HashMethod])) {
            return false;
        }

        $PackFormat = $PackFormats[$HashMethod];
        // this is the equivalent of "strlen($Key) > 64":
        if (isset($Key[63])) {
            $Key = pack($PackFormat, $HashMethod($Key));
        } else {
            $Key = str_pad($Key, 64, chr(0));
        }

        $InnerPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x36), 64));
        $OuterPad = (substr($Key, 0, 64) ^ str_repeat(chr(0x5C), 64));

        return $HashMethod($OuterPad.pack($PackFormat, $HashMethod($InnerPad.$Data)));
    }

    /**
     * Generates the user's session cookie.
     *
     * @param int $UserID The unique id assigned to the user in the database.
     * @param boolean $Persist Should the user's session remain persistent across visits?
     */
    public function setIdentity($UserID, $Persist = false) {
        if (is_null($UserID)) {
            $this->_clearIdentity();
            return;
        }

        $this->UserID = $UserID;

        // If we're persisting, both the cookie and its payload expire in 30days
        if ($Persist) {
            $PayloadExpires = strtotime($this->PersistExpiry);
            $CookieExpires = $PayloadExpires;

            // Otherwise the payload expires in 2 days and the cookie expires on borwser restart
        } else {
            // Note: $CookieExpires = 0 causes cookie to die when browser closes.
            $PayloadExpires = strtotime($this->SessionExpiry);
            $CookieExpires = 0;
        }

        // Create the cookie
        $KeyData = $UserID.'-'.$PayloadExpires;
        $this->_setCookie($this->CookieName, $KeyData, array($UserID, $PayloadExpires), $CookieExpires);
        $this->setVolatileMarker($UserID);
    }

    /**
     *
     *
     * @param integer $UserID
     * @return void
     */
    public function setVolatileMarker($UserID) {
        if (is_null($UserID)) {
            return;
        }

        // Note: 172800 is 60*60*24*2 or 2 days
        $PayloadExpires = time() + 172800;
        // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
        $CookieExpires = 0;

        $KeyData = $UserID.'-'.$PayloadExpires;
        $this->_setCookie($this->VolatileMarker, $KeyData, array($UserID, $PayloadExpires), $CookieExpires);
    }

    /**
     * Set a cookie, using path, domain, salt, and hash method from core config
     *
     * @param string $CookieName Name of the cookie
     * @param string $KeyData
     * @param mixed $CookieContents
     * @param integer $CookieExpires
     * @return void
     */
    protected function _setCookie($CookieName, $KeyData, $CookieContents, $CookieExpires) {
        self::setCookie($CookieName, $KeyData, $CookieContents, $CookieExpires, $this->CookiePath, $this->CookieDomain, $this->CookieHashMethod, $this->CookieSalt);
    }

    /**
     * Set a cookie, using specified path, domain, salt and hash method
     *
     * @param string $CookieName Name of the cookie
     * @param string $KeyData
     * @param mixed $CookieContents
     * @param integer $CookieExpires
     * @param string $Path Optional. Cookie path (auto load from config)
     * @param string $Domain Optional. Cookie domain (auto load from config)
     * @param string $CookieHashMethod Optional. Cookie hash method (auto load from config)
     * @param string $CookieSalt Optional. Cookie salt (auto load from config)
     * @return void
     */
    public static function setCookie($CookieName, $KeyData, $CookieContents, $CookieExpires, $Path = null, $Domain = null, $CookieHashMethod = null, $CookieSalt = null) {

        if (is_null($Path)) {
            $Path = Gdn::config('Garden.Cookie.Path', '/');
        }

        if (is_null($Domain)) {
            $Domain = Gdn::config('Garden.Cookie.Domain', '');
        }

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $CurrentHost = Gdn::request()->host();
        if (!stringEndsWith($CurrentHost, trim($Domain, '.'))) {
            $Domain = '';
        }

        if (!$CookieHashMethod) {
            $CookieHashMethod = Gdn::config('Garden.Cookie.HashMethod');
        }

        if (!$CookieSalt) {
            $CookieSalt = Gdn::config('Garden.Cookie.Salt');
        }

        // Create the cookie signature
        $KeyHash = self::_hash($KeyData, $CookieHashMethod, $CookieSalt);
        $KeyHashHash = self::_hashHMAC($CookieHashMethod, $KeyData, $KeyHash);
        $Cookie = array($KeyData, $KeyHashHash, time());

        // Attach cookie payload
        if (!is_null($CookieContents)) {
            $CookieContents = (array)$CookieContents;
            $Cookie = array_merge($Cookie, $CookieContents);
        }

        $CookieContents = implode('|', $Cookie);

        // Create the cookie.
        safeCookie($CookieName, $CookieContents, $CookieExpires, $Path, $Domain, null, true);
        $_COOKIE[$CookieName] = $CookieContents;
    }

    /**
     *
     *
     * @param $CookieName
     * @return bool
     */
    protected function _checkCookie($CookieName) {
        $CookieStatus = self::checkCookie($CookieName, $this->CookieHashMethod, $this->CookieSalt);
        if ($CookieStatus === false) {
            $this->_deleteCookie($CookieName);
        }
        return $CookieStatus;
    }

    /**
     *
     *
     * @param $CookieName
     * @param null $CookieHashMethod
     * @param null $CookieSalt
     * @return bool
     */
    public static function checkCookie($CookieName, $CookieHashMethod = null, $CookieSalt = null) {
        if (empty($_COOKIE[$CookieName])) {
            return false;
        }

        if (is_null($CookieHashMethod)) {
            $CookieHashMethod = Gdn::config('Garden.Cookie.HashMethod');
        }

        if (is_null($CookieSalt)) {
            $CookieSalt = Gdn::config('Garden.Cookie.Salt');
        }

        $CookieData = explode('|', $_COOKIE[$CookieName]);
        if (count($CookieData) < 5) {
            self::deleteCookie($CookieName);
            return false;
        }

        list($HashKey, $CookieHash, $Time, $UserID, $PayloadExpires) = $CookieData;
        if ($PayloadExpires < time() && $PayloadExpires != 0) {
            self::deleteCookie($CookieName);
            return false;
        }
        $KeyHash = self::_hash($HashKey, $CookieHashMethod, $CookieSalt);
        $CheckHash = self::_hashHMAC($CookieHashMethod, $HashKey, $KeyHash);

        if (!CompareHashDigest($CookieHash, $CheckHash)) {
            self::deleteCookie($CookieName);
            return false;
        }

        return true;
    }

    /**
     *
     *
     * @param $CookieName
     * @param null $CookieHashMethod
     * @param null $CookieSalt
     * @return array|bool
     */
    public static function getCookiePayload($CookieName, $CookieHashMethod = null, $CookieSalt = null) {
        if (!self::checkCookie($CookieName)) {
            return false;
        }

        $Payload = explode('|', $_COOKIE[$CookieName]);

        $Key = explode('-', $Payload[0]);
        $Expiration = array_pop($Key);
        $UserID = implode('-', $Key);
        $Payload = array_slice($Payload, 4);

        $Payload = array_merge(array($UserID, $Expiration), $Payload);

        return $Payload;
    }

    /**
     *
     *
     * @param $CookieName
     */
    protected function _deleteCookie($CookieName) {
        unset($_COOKIE[$CookieName]);
        self::deleteCookie($CookieName, $this->CookiePath, $this->CookieDomain);
    }

    /**
     *
     *
     * @param $CookieName
     * @param null $Path
     * @param null $Domain
     */
    public static function deleteCookie($CookieName, $Path = null, $Domain = null) {
        if (is_null($Path)) {
            $Path = Gdn::config('Garden.Cookie.Path');
        }

        if (is_null($Domain)) {
            $Domain = Gdn::config('Garden.Cookie.Domain');
        }

        $CurrentHost = Gdn::request()->host();
        if (!StringEndsWith($CurrentHost, trim($Domain, '.'))) {
            $Domain = '';
        }

        $Expiry = time() - 60 * 60;
        safeCookie($CookieName, "", $Expiry, $Path, $Domain);
        $_COOKIE[$CookieName] = null;
    }
}
