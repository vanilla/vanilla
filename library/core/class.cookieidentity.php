<?php
/**
 * Gdn_CookieIdentity
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Core
 * @since 2.0
 */

use Firebase\JWT\JWT;

/**
 * Validating, Setting, and Retrieving session data in cookies.
 */
class Gdn_CookieIdentity {

    /** Signing algorithm for JWT tokens. */
    const JWT_ALGORITHM = 'HS256';

    /** Current cookie identity version. */
    const VERSION = '2';

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
            ['PersistExpiry' => '30 days', 'SessionExpiry' => '2 days'],
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

        $userID = 0;
        $name = $this->CookieName;
        $version = $this->getCookieVersion($name);

        if (array_key_exists($name, $_COOKIE)) {
            $payload = null;

            switch ($version) {
                case 1:
                    if ($this->_checkCookie($name)) {
                        list($userID) = self::getCookiePayload($name);
                        // Old cookie identity. Upgrade it.
                        $payload = $this->setIdentity($userID);
                    }
                    break;
                case self::VERSION:
                default:
                    $payload = $this->getJWTPayload($name);
                    $userID = val('sub', $payload, 0);
            }

            // The identity cookie set, but we couldn't find a user in it? Nuke it.
            if ($userID == 0) {
                $this->_clearIdentity();
            } elseif (is_array($payload)) {
                Gdn::pluginManager()
                    ->fireAs(self::class)
                    ->fireEvent('getIdentity', ['payload' => &$payload]);
            }
        }

        if (filter_var($userID, FILTER_VALIDATE_INT) === false || $userID < -2) { // allow for handshake special id
            $userID = 0;
        }

        if ($userID != 0) {
            $this->UserID = $userID;
        }
        return $userID;
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
     * Given a cookie's name, attempt to determine its version.
     *
     * @param string $name
     * @return int|null
     */
    protected function getCookieVersion($name) {
        $result = null;

        if (array_key_exists($name, $_COOKIE)) {
            $cookie = $_COOKIE[$name];

            $v1Parts = explode('|', $cookie);
            if (count($v1Parts) === 5) {
                $result = 1;
            } elseif ($this->getJWTPayload($name) !== null) {
                $result = 2;
            }
        }

        return $result;
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

        list($UserID) = self::getCookiePayload($this->CookieName);

        if ($UserID != $CheckUserID) {
            return false;
        }

        return true;
    }

    /**
     * Generates the user's session cookie.
     *
     * @param int $userID The unique id assigned to the user in the database.
     * @param boolean $persist Should the user's session remain persistent across visits?
     * @param array $data Additional data to include in the token.
     * @return array|bool
     */
    public function setIdentity($userID, $persist = false) {
        if (is_null($userID)) {
            $this->_clearIdentity();
            return true;
        }

        $this->UserID = $userID;

        // If we're persisting, both the cookie and its payload expire in 30days
        if ($persist) {
            $expiry = strtotime($this->PersistExpiry);
            // Otherwise the payload expires in 2 days and the cookie expires on browser restart
        } else {
            // Note: $CookieExpires = 0 causes cookie to die when browser closes.
            $expiry = 0;
        }

        // Generate the token.
        $timestamp = time();
        $payload = [
            'exp' => strtotime($this->PersistExpiry), // Expiration
            'iat' => $timestamp, // Issued at
            'nbf' => $timestamp, // Not before
            'sub' => $userID // Subject
        ];
        Gdn::pluginManager()
            ->fireAs(self::class)
            ->fireEvent('setIdentity', ['payload' => &$payload]);
        $jwt = JWT::encode($payload, $this->CookieSalt, self::JWT_ALGORITHM);

        // Save the cookie.
        safeCookie($this->CookieName, $jwt, $expiry, $this->CookiePath, $this->CookieDomain, null, true);
        $_COOKIE[$this->CookieName] = $jwt;
        return $payload;
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
        $this->_setCookie($this->VolatileMarker, $KeyData, [$UserID, $PayloadExpires], $CookieExpires);
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
        $KeyHash = hash_hmac($CookieHashMethod, $KeyData, $CookieSalt);
        $KeyHashHash = hash_hmac($CookieHashMethod, $KeyData, $KeyHash);
        $Cookie = [$KeyData, $KeyHashHash, time()];

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
     * Validate security of our cookie.
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

        list($HashKey, $CookieHash) = $CookieData;
        list($UserID, $Expiration) = self::getCookiePayload($CookieName);
        if ($Expiration < time()) {
            self::deleteCookie($CookieName);
            return false;
        }
        $KeyHash = hash_hmac($CookieHashMethod, $HashKey, $CookieSalt);
        $CheckHash = hash_hmac($CookieHashMethod, $HashKey, $KeyHash);

        if (!hash_equals($CheckHash, $CookieHash)) {
            self::deleteCookie($CookieName);
            return false;
        }

        return true;
    }

    /**
     * Get the pieces that make up our cookie data.
     *
     * @param string $CookieName
     * @return array
     */
    public static function getCookiePayload($CookieName) {
        $Payload = explode('|', $_COOKIE[$CookieName]);
        $Key = explode('-', $Payload[0]);
        $Expiration = array_pop($Key);
        $UserID = implode('-', $Key);
        $Payload = array_slice($Payload, 4);
        $Payload = array_merge([$UserID, $Expiration], $Payload);

        return $Payload;
    }

    /**
     * Attempt to decode a JWT payload from a cookie value.
     *
     * @param string $name Name of the cookie holding a JWT token.
     * @return array|null
     */
    public function getJWTPayload($name) {
        $result = null;

        if (array_key_exists($name, $_COOKIE)) {
            $jwt = $_COOKIE[$name];
            try {
                $payload = JWT::decode($jwt, $this->CookieSalt, [self::JWT_ALGORITHM]);
                if (is_object($payload)) {
                    $result = (array)$payload;
                }
            } catch (Exception $e) {
                Logger::event(
                    'cookie_jwt_error',
                    Logger::ERROR,
                    $e->getMessage(),
                    ['jwt' => $jwt]
                );
            }
        }

        return $result;
    }

    /**
     * Remove a cookie.
     *
     * @param $CookieName
     */
    protected function _deleteCookie($CookieName) {
        if (!array_key_exists($CookieName, $_COOKIE)) {
            return;
        }

        unset($_COOKIE[$CookieName]);
        self::deleteCookie($CookieName, $this->CookiePath, $this->CookieDomain);
    }

    /**
     * Remove a cookie.
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
