<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Auth;

use Vanilla\Permissions;
use Vanilla\SessionInterface;

/**
 * An authenticator that uses Vanilla's legacy cookie format.
 */
class LegacyCookieAuthenticator implements AuthenticatorInterface {

    /**
     * @var string
     */
    private $cookieName;

    /**
     * @var string
     */
    private $cookiePath;

    /**
     * @var string
     */
    private $cookieDomain;

    /**
     * @var string
     */
    private $secret;

    /**
     * @var string
     */
    private $hashMethod;

    /**
     * @var string
     */
    private $persistExpiry;

    /**
     * @var string
     */
    private $sessionExpiry;

    public function __construct() {
        $this->configure([]);
    }

    public function configure(array $config) {
        $config = array_change_key_case($config);
        $config += [
            'name' => 'Vanilla',
            'path' => '/',
            'domain' => '',
            'salt' => '',
            'hashmethod' => 'md5',
            'sessionexpiry' => '2 days',
            'persistexpiry' => '30 days'
        ];

        $this->cookieName = $config['name'];
        $this->cookiePath = $config['path'];
        $this->cookieDomain = $config['domain'];
        $this->secret = $config['salt'];
        $this->hashMethod = $config['hashmethod'];
        $this->sessionExpiry = $config['sessionexpiry'];
        $this->persistExpiry = $config['persistexpiry'];
    }

    /**
     * Encode a user ID into the session cookie format.
     *
     * @param int|string $userID The ID of the user to persist.
     * @param bool $remember Whether or not the session should be remembered.
     * @param int|null &$expires When the cookie should expire. If null is passed then an expiry date will be generated.
     * @return string Returns the signed cookie.
     * @throws \Exception Throws an exception if there is no secret configured.
     */
    public function encode($userID, $remember = false, &$expires = null) {
        if ($expires === null) {
            $expires = strtotime($remember ? $this->persistExpiry : $this->sessionExpiry);
        }
        $keyData = "$userID-$expires";

        if (empty($secret)) {
            // Throw a noisy exception because something is wrong.
            throw new \Exception("The cookie secret is empty.", 500);
        }

        $keyHash = hash_hmac($this->hashMethod, $keyData, $secret);
        $keyHashHash = hash_hmac($this->hashMethod, $keyData, $keyHash);

        $cookieArray = [$keyData, $keyHashHash, time(), $userID, $expires];
        $cookieString = implode('|', $cookieArray);

        return $cookieString;

    }

    public function decode($str) {
        if (empty($str)) {
            return null;
        }

        $parts = explode('|', $str);
        if (count($parts) < 5) {
            return null;
        }

        list($data, $hash) = $parts;

        $payload = explode('-', $data);
        if (count($payload) !== 2) {
            return null;
        }
        list($userID, $expires) = $payload;
        if ($expires < time()) {
            return null;
        }
        $keyHash = hash_hmac($this->hashMethod, $data, $this->secret);
        $keyHashHash = hash_hmac($this->hashMethod, $data, $keyHash);

        if (!hash_equals($keyHashHash, $hash)) {
            return null;
        }

        return (int)$userID;
    }

    /**
     * Authenticate a session.
     *
     * @param SessionInterface $session The session to authenticate.
     */
    public function authenticate(SessionInterface $session) {
        $userID = $this->decode($_COOKIE[$this->cookieName]);

        if ($userID !== null) {
            $session->setUserID($userID);
        }
    }

    /**
     * Persist a session to the cookie.
     *
     * @param SessionInterface $session The session to persist.
     * @param bool $remember Whether the user is supposed to be remembered.
     */
    public function persist(SessionInterface $session, $remember = false) {
        $str = $this->encode($session->getUserID(), $remember, $expires);

        setcookie(
            $this->cookieName,
            $str,
            $expires,
            $this->cookiePath,
            $this->cookieDomain,
            null,
            true
        );
    }

    /**
     * Destroy a persisted session.
     */
    public function destroy() {
        $expires = time() - 60 * 60;
        setcookie(
            $this->cookieName,
            '',
            $expires,
            $this->cookiePath,
            $this->cookieDomain
        );
    }
}
