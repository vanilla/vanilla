<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace Garden\Web;

/**
 * A class for reading/writing cookies.
 */
class Cookie {
    const EXPIRE_THRESHOLD = 631152000; // 20 years

    /**
     * @var string[]
     */
    private $inCookies;

    /**
     * @var string[]
     */
    private $cookies;

    /**
     * @var array
     */
    private $sets;

    /**
     * @var string
     */
    private $path = '/';

    /**
     * @var string
     */
    private $domain = '';

    /**
     * @var bool
     */
    private $secure = false;

    /**
     * @var bool
     */
    private $flushAll = false;


    /**
     * Construct a {@link Cookie} objects.
     *
     * @param array $cookies The initial cookies array or **null** to use the **$_COOKIE** super global.
     */
    public function __construct(array $cookies = null) {
        if ($cookies === null) {
            $cookies = $_COOKIE;
        }
        $this->cookies = $this->inCookies = $cookies;
        $this->sets = [];
    }

    /**
     * Calculate a cookie's expiration time.
     *
     * @param int $expire Target expiration value.
     * @param int|null $timestamp If calculating a relative expiry, use this timestamp as the offset.
     * @return int
     */
    public function calculateExpiry($expire, $timestamp = null) {
        if ($expire > self::EXPIRE_THRESHOLD) {
            $result = $expire;
        } else {
            if ($timestamp === null || filter_var($timestamp, FILTER_VALIDATE_INT) === false) {
                $timestamp = time();
            }
            $result = $timestamp + $expire;
        }
        return $result;
    }

    /**
     * Get a cookie value.
     *
     * This method returns the current value of the cookie which will be the set value, the initial value from the request.
     *
     * @param string $name The name of the cookie to get.
     * @param mixed $default The default value if the cookie isn't set.
     * @return null
     */
    public function get($name, $default = null) {
        return isset($this->cookies[$name]) ? $this->cookies[$name] : $default;
    }

    /**
     * Set a cookie.
     *
     * @param string $name The name of the cookie to set.
     * @param string $value The new value of the cookie.
     * @param int $expire The time the cookie expires, this can be one of the following:
     *
     * - A unix timestamp.
     * - A number of seconds to expire from now if less than 20 years.
     * - A value of zero will expire at the end of the browser session.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param bool $httpOnly Whether or not the cookie should be httpOnly.
     * @return $this
     */
    public function set($name, $value, $expire = 0, $secure = false, $httpOnly = true) {
        $this->setCookie($name, $value, $expire, $this->path, $this->domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * Set a cookie with full options.
     *
     * Most code should be able to use the simpler {@link Cookie::set()} method using sensible defaults. This method is
     * here for two reasons:
     *
     * 1. This method is analogous to the {@link setcookie()} function so makes for an easier upgrade path.
     * 2. This method provides full cookie setting control for uses that go beyond a site with a simple domain/path strategy.
     *
     * Note that this method differs where the {@link $path} and {@link $domain} parameters default to this object's path
     * and domain properties rather than the defaults of the {@link setcookie()} method.
     *
     * @param string $name The name of the cookie to set.
     * @param string $value The new value of the cookie.
     * @param int $expire The time the cookie expires, this can be one of the following:
     *
     * - A unix timestamp.
     * - A number of seconds to expire from now if less than 20 years.
     * - A value of zero will expire at the end of the browser session.
     * @param string|null $path The path of the cookie or **null** to use this object's path.
     * @param string|null $domain The domain of the cookie or **null** to use this object's path.
     * @param bool $secure Indicates that the cookie should only be transmitted over a secure HTTPS connection from the client.
     * @param bool $httpOnly Whether or not the cookie should be httpOnly.
     * @return $this
     */
    public function setCookie($name, $value, $expire = 0, $path = null, $domain = null, $secure = false, $httpOnly = false) {
        if ($value === null) {
            $this->delete($name);
        } else {
            $this->cookies[$name] = $value;
            $this->sets[$name] = [
                $value,
                $this->calculateExpiry($expire),
                $path === null ? $this->path : $path,
                $domain === null ? $this->domain : $domain,
                $secure,
                $httpOnly
            ];
        }
        return $this;
    }

    /**
     * Delete a cookie.
     *
     * This removes the cookie from the cookies array and will issue a delete cookie request when the cookies are flushed.
     *
     * @param string $name The name of the cookie to delete.
     * @return $this
     */
    public function delete($name) {
        unset($this->cookies[$name]);
        return $this;
    }

    /**
     * Flush the cookies to the response.
     */
    public function flush() {
        $calls = array_merge($this->makeNewCookieCalls(), $this->makeDeleteCookieCalls());

        foreach ($calls as $name => $args) {
            setcookie($name, ...$args);
        }
    }

    /**
     * Flush cookie delete headers.
     */
    public function makeDeleteCookieCalls() {
        $deletes = array_diff_key($this->inCookies, $this->cookies);

        $expire = time() - 3600;
        $result = [];
        foreach ($deletes as $name => $_) {
            $result[$name] = ['', $expire];
        }
        return $result;
    }

    /**
     * Flush set-cookie headers.
     */
    public function makeNewCookieCalls() {
        if ($this->flushAll) {
            $sets = $this->sets;
        } else {
            $cookieDiff = array_diff_assoc($this->cookies, $this->inCookies);
            $sets = [];
            foreach ($cookieDiff as $name => $_) {
                $sets[$name] = $this->sets[$name];
            }
        }

        return $sets;
    }

    /**
     * Encode an array in a format suitable for a cookie header.
     *
     * @param array $array The cookie value array.
     * @return string Returns a string suitable to be passed to a cookie header.
     */
    private function cookieEncode(array $array) {
        $pairs = [];
        foreach ($array as $key => $value) {
            $pairs[] = "$key=".rawurlencode($value);
        }

        $result = implode('; ', $pairs);
        return $result;
    }

    /**
     * Return the all of the current cookies in a format suitable for a "Cookie" HTTP header.
     *
     * @return string Returns a cookie string.
     */
    public function makeCookieHeader() {
        return $this->cookieEncode($this->cookies);
    }

    /**
     * Return the cookies that will be set in a format suitable for "Set-Cookie" HTTP headers.
     *
     * @return array Returns an array of "Set-Cookie" header values.
     * @throws \Exception TODO.
     */
    public function makeSetCookieHeader() {
        throw new \Exception('Not implemented.', 501);
    }

    /**
     * Get the path.
     *
     * @return string Returns the path.
     */
    public function getPath() {
        return $this->path;
    }

    /**
     * Get the cookie domain.
     *
     * @return string Returns the domain.
     */
    public function getDomain() {
        return $this->domain;
    }

    /**
     * Set the cookie domain.
     *
     * @param string $domain The new cookie domain.
     * @return $this
     */
    public function setDomain($domain) {
        $this->domain = $domain;
        return $this;
    }

    /**
     * Whether or not HTTP-only cookies should be secure when set.
     *
     * @return bool Returns **true** if HTTP-only cookies should be secure or **false** otherwise.
     */
    public function isSecure() {
        return $this->secure;
    }

    /**
     * Set whether or not HTTP-only cookies should be secure when set.
     *
     * @param bool $secure The new value.
     * @return $this
     */
    public function setSecure($secure) {
        $this->secure = $secure;
        return $this;
    }

    /**
     * Whether or not only cookie changes will be flushed.
     *
     * Usually, a call to {@link Cookie::set()} or {@link Cookie::setCookie()} will cause a "Set-Cookie" header only if
     * it is different from the value coming in from the request. Setting this property to **true** will flush all of the
     * calls. If you call a cookie setter method with the same name more than once, only the most recent value will be
     * flushed in the response.
     *
     * @return bool Returns **true** all unique sets are flushed or **false** otherwise.
     */
    public function getFlushAll() {
        return $this->flushAll;
    }

    /**
     * Set whether or not only cookie changes will be flushed.
     *
     * @param bool $flushAll The new value.
     * @return $this
     * @see Cookie::getFlushAll()
     */
    public function setFlushAll($flushAll) {
        $this->flushAll = $flushAll;
        return $this;
    }
}
