<?php
/**
 * Gdn_CookieIdentity
 *
 * @author Mark O'Sullivan <markm@vanillaforums.com>
 * @author Todd Burry <todd@vanillaforums.com>
 * @author Tim Gunter <tim@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
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
     * @param null $config
     */
    public function __construct($config = null) {
        $this->init($config);
    }

    /**
     *
     *
     * @param null $config
     */
    public function init($config = null) {
        if (is_null($config)) {
            $config = Gdn::config('Garden.Cookie');
        } elseif (is_string($config))
            $config = Gdn::config($config);

        $defaultConfig = array_replace(
            ['PersistExpiry' => '30 days', 'SessionExpiry' => '2 days'],
            Gdn::config('Garden.Cookie')
        );
        $this->CookieName = val('Name', $config, $defaultConfig['Name']);
        $this->CookiePath = val('Path', $config, $defaultConfig['Path']);
        $this->CookieDomain = val('Domain', $config, $defaultConfig['Domain']);

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($this->CookieDomain, '.'))) {
            $this->CookieDomain = '';
            trigger_error('Config "Garden.Cookie.Domain" is incompatible with the current host.', E_USER_WARNING);
        }

        $this->CookieHashMethod = val('HashMethod', $config, $defaultConfig['HashMethod']);
        $this->CookieSalt = val('Salt', $config, $defaultConfig['Salt']);
        $this->VolatileMarker = $this->CookieName.'-Volatile';
        $this->PersistExpiry = val('PersistExpiry', $config, $defaultConfig['PersistExpiry']);
        $this->SessionExpiry = val('SessionExpiry', $config, $defaultConfig['SessionExpiry']);
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
     * @param $checkUserID
     * @return bool
     */
    public function hasVolatileMarker($checkUserID) {
        $hasMarker = $this->checkVolatileMarker($checkUserID);
        if (!$hasMarker) {
            $this->setVolatileMarker($checkUserID);
        }

        return $hasMarker;
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
     * @param $checkUserID
     * @return bool
     */
    public function checkVolatileMarker($checkUserID) {
        if (!$this->_CheckCookie($this->VolatileMarker)) {
            return false;
        }

        list($userID) = self::getCookiePayload($this->CookieName);

        if ($userID != $checkUserID) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve an attribute from the current JWT data.
     *
     * @param string $name
     * @return mixed
     */
    public function getAttribute($name) {
        $payload = $this->getJWTPayload($this->CookieName);
        $result = val($name, $payload, null);
        return $result;
    }

    /**
     * Update an attribute in the current JWT data.
     *
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function setAttribute($name, $value) {
        $payload = $this->getJWTPayload($this->CookieName);
        $result = false;

        if (is_scalar($value) && (is_array($payload) || is_object($payload))) {
            $expiry = val('exp', $payload, strtotime($this->PersistExpiry));
            setValue($name, $payload, $value);
            $this->setJWTPayload($this->CookieName, $payload, $expiry);
            $result = true;
        }

        return $result;
    }

    /**
     * Generates the user's session cookie.
     *
     * @throws Gdn_ErrorException If cookie salt is empty.
     * @param int $userID The unique id assigned to the user in the database.
     * @param boolean $persist Should the user's session remain persistent across visits?
     * @param array $data Additional data to include in the token.
     * @return array|bool
     */
    public function setIdentity($userID, $persist = false) {
        if (empty($this->CookieSalt)) {
            throw new Gdn_ErrorException('Cookie salt is empty.');
        }

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
            'sub' => $userID // Subject
        ];
        Gdn::pluginManager()
            ->fireAs(self::class)
            ->fireEvent('setIdentity', ['payload' => &$payload]);

        $this->setJWTPayload($this->CookieName, $payload, $expiry);
        return $payload;
    }

    /**
     *
     *
     * @param integer $userID
     * @return void
     */
    public function setVolatileMarker($userID) {
        if (is_null($userID)) {
            return;
        }

        // Note: 172800 is 60*60*24*2 or 2 days
        $payloadExpires = time() + 172800;
        // Note: setting $Expire to 0 will cause the cookie to die when the browser closes.
        $cookieExpires = 0;

        $keyData = $userID.'-'.$payloadExpires;
        $this->_setCookie($this->VolatileMarker, $keyData, [$userID, $payloadExpires], $cookieExpires);
    }

    /**
     * Set a cookie, using path, domain, salt, and hash method from core config
     *
     * @param string $cookieName Name of the cookie
     * @param string $keyData
     * @param mixed $cookieContents
     * @param integer $cookieExpires
     * @return void
     */
    protected function _setCookie($cookieName, $keyData, $cookieContents, $cookieExpires) {
        self::setCookie($cookieName, $keyData, $cookieContents, $cookieExpires, $this->CookiePath, $this->CookieDomain, $this->CookieHashMethod, $this->CookieSalt);
    }

    /**
     * Set a cookie, using specified path, domain, salt and hash method
     *
     * @throws Gdn_ErrorException If cookie salt is empty.
     * @param string $cookieName Name of the cookie
     * @param string $keyData
     * @param mixed $cookieContents
     * @param integer $cookieExpires
     * @param string $path Optional. Cookie path (auto load from config)
     * @param string $domain Optional. Cookie domain (auto load from config)
     * @param string $cookieHashMethod Optional. Cookie hash method (auto load from config)
     * @param string $cookieSalt Optional. Cookie salt (auto load from config)
     * @return void
     */
    public static function setCookie($cookieName, $keyData, $cookieContents, $cookieExpires, $path = null, $domain = null, $cookieHashMethod = null, $cookieSalt = null) {
        if (is_null($path)) {
            $path = Gdn::config('Garden.Cookie.Path', '/');
        }

        if (is_null($domain)) {
            $domain = Gdn::config('Garden.Cookie.Domain', '');
        }

        // If the domain being set is completely incompatible with the current domain then make the domain work.
        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($domain, '.'))) {
            $domain = '';
        }

        if (!$cookieHashMethod) {
            $cookieHashMethod = Gdn::config('Garden.Cookie.HashMethod');
        }

        if (!$cookieSalt) {
            $cookieSalt = Gdn::config('Garden.Cookie.Salt');
        }

        if (empty($cookieSalt)) {
            throw new Gdn_ErrorException('Cookie salt is empty.');
        }

        // Create the cookie signature
        $keyHash = hash_hmac($cookieHashMethod, $keyData, $cookieSalt);
        $keyHashHash = hash_hmac($cookieHashMethod, $keyData, $keyHash);
        $cookie = [$keyData, $keyHashHash, time()];

        // Attach cookie payload
        if (!is_null($cookieContents)) {
            $cookieContents = (array)$cookieContents;
            $cookie = array_merge($cookie, $cookieContents);
        }

        $cookieContents = implode('|', $cookie);

        // Create the cookie.
        safeCookie($cookieName, $cookieContents, $cookieExpires, $path, $domain, null, true);
        $_COOKIE[$cookieName] = $cookieContents;
    }

    /**
     *
     *
     * @param $cookieName
     * @return bool
     */
    protected function _checkCookie($cookieName) {
        $cookieStatus = self::checkCookie($cookieName, $this->CookieHashMethod, $this->CookieSalt);
        if ($cookieStatus === false) {
            $this->_deleteCookie($cookieName);
        }
        return $cookieStatus;
    }

    /**
     * Validate security of our cookie.
     *
     * @throws Gdn_ErrorException If cookie salt is empty.
     * @param $cookieName
     * @param null $cookieHashMethod
     * @param null $cookieSalt
     * @return bool
     */
    public static function checkCookie($cookieName, $cookieHashMethod = null, $cookieSalt = null) {
        if (empty($_COOKIE[$cookieName])) {
            return false;
        }

        if (is_null($cookieHashMethod)) {
            $cookieHashMethod = Gdn::config('Garden.Cookie.HashMethod');
        }

        if (is_null($cookieSalt)) {
            $cookieSalt = Gdn::config('Garden.Cookie.Salt');
        }

        if (empty($cookieSalt)) {
            throw new Gdn_ErrorException('Cookie salt is empty.');
        }

        $cookieData = explode('|', $_COOKIE[$cookieName]);
        if (count($cookieData) < 5) {
            self::deleteCookie($cookieName);
            return false;
        }

        list($hashKey, $cookieHash) = $cookieData;
        list($userID, $expiration) = self::getCookiePayload($cookieName);
        if ($expiration < time()) {
            self::deleteCookie($cookieName);
            return false;
        }
        $keyHash = hash_hmac($cookieHashMethod, $hashKey, $cookieSalt);
        $checkHash = hash_hmac($cookieHashMethod, $hashKey, $keyHash);

        if (!hash_equals($checkHash, $cookieHash)) {
            self::deleteCookie($cookieName);
            return false;
        }

        return true;
    }

    /**
     * Get the pieces that make up our cookie data.
     *
     * @param string $cookieName
     * @return array
     */
    public static function getCookiePayload($cookieName) {
        $payload = explode('|', $_COOKIE[$cookieName]);
        $key = explode('-', $payload[0]);
        $expiration = array_pop($key);
        $userID = implode('-', $key);
        $payload = array_slice($payload, 4);
        $payload = array_merge([$userID, $expiration], $payload);

        return $payload;
    }

    /**
     * Get the last time this user authenticated.
     *
     * @return int|null
     */
    public function getAuthTime() {
        $result = $this->getAttribute('iat');
        return $result;
    }

    /**
     * Attempt to decode a JWT payload from a cookie value.
     *
     * @throws Gdn_ErrorException If cookie salt is empty.
     * @param string $name Name of the cookie holding a JWT token.
     * @return array|null
     */
    public function getJWTPayload($name) {
        if (empty($this->CookieSalt)) {
            throw new Gdn_ErrorException('Cookie salt is empty.');
        }

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
     * Set the last time this user authenticated.
     *
     * @param int $timestamp
     */
    public function setAuthTime($timestamp) {
        $this->setAttribute('iat', $timestamp);
    }

    /**
     * Attempt to encode a JWT payload and assign its value to a cookie.
     *
     * @param string $name
     * @param array|object $payload
     * @param int $expiry
     * @return string
     */
    public function setJWTPayload($name, $payload, $expiry) {
        $jwt = JWT::encode($payload, $this->CookieSalt, self::JWT_ALGORITHM);

        setValue('exp', $payload, $expiry);

        // Send the updated cookie to the browser.
        safeCookie($name, $jwt, $expiry, $this->CookiePath, $this->CookieDomain, null, true);

        // Update the cookie for the current request.
        $_COOKIE[$this->CookieName] = $jwt;

        return $jwt;
    }

    /**
     * Remove a cookie.
     *
     * @param $cookieName
     */
    protected function _deleteCookie($cookieName) {
        if (!array_key_exists($cookieName, $_COOKIE)) {
            return;
        }

        unset($_COOKIE[$cookieName]);
        self::deleteCookie($cookieName, $this->CookiePath, $this->CookieDomain);
    }

    /**
     * Remove a cookie.
     *
     * @param $cookieName
     * @param null $path
     * @param null $domain
     */
    public static function deleteCookie($cookieName, $path = null, $domain = null) {
        if (is_null($path)) {
            $path = Gdn::config('Garden.Cookie.Path');
        }

        if (is_null($domain)) {
            $domain = Gdn::config('Garden.Cookie.Domain');
        }

        $currentHost = Gdn::request()->host();
        if (!stringEndsWith($currentHost, trim($domain, '.'))) {
            $domain = '';
        }

        $expiry = time() - 60 * 60;
        safeCookie($cookieName, "", $expiry, $path, $domain);
        $_COOKIE[$cookieName] = null;
    }
}
