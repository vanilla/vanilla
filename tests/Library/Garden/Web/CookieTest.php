<?php
/**
 * @author Vanilla Forums Inc.
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Garden\Web;

use VanillaTests\SharedBootstrapTestCase;
use Garden\Web\Cookie;

/**
 * Test the {@link ResourceRoute} class.
 */
class CookieTest extends SharedBootstrapTestCase {

    /**
     * Parse a Cookie header into its individual cookie key-value pairs.
     *
     * @param string $header
     * @return array
     */
    private function cookieDecode($header) {
        $cookies = [];
        $pairs = explode(';', $header);

        foreach ($pairs as $currentPair) {
            list($key, $value) = explode('=', trim($currentPair));
            $cookies[$key] = $value;
        }

        return $cookies;
    }

    /**
     * Test domain accessors.
     */
    public function testGetSetDomain() {
        $cookie = new Cookie();
        $cookie->setDomain('example.com');
        $this->assertSame('example.com', $cookie->getDomain());
    }

    /**
     * Test the flush all accessors.
     */
    public function testGetSetFlushAll() {
        $cookie = new Cookie();
        $f = !$cookie->getFlushAll();
        $cookie->setFlushAll($f);
        $this->assertSame($f, $cookie->getFlushAll());
    }

    /**
     * Test secure accessors.
     */
    public function testGetSetSecure() {
        $cookie = new Cookie();
        $c = !$cookie->isSecure();
        $cookie->setSecure($c);
        $this->assertSame($c, $cookie->isSecure());
    }

    /**
     * Test the cookie prefix accessors.
     */
    public function testGetSetPrefix() {
        $cookie = new Cookie();
        $cookie->setPrefix('px');
        $this->assertSame('px', $cookie->getPrefix());
    }

    /**
     * Test deleting a cookie.
     */
    public function testDelete() {
        $cookie = new Cookie(['foo' => 'bar']);
        $cookie->delete('foo');
        $this->assertNull($cookie->get('foo'));
    }

    /**
     * Provide parameters for cookie-setting functions.
     *
     * @return array
     */
    public function provideCookieSet() {
        // Parameter order: name, value, expire, path, domain, secure, httpOnly
        $data = [
            'simple' => ['foo', 'bar', 0, null, null, false, false],
            'expires' => ['foo', 'bar', 300, null, null, false, false],
            'domain' => ['foo', 'bar', 0, null, 'vanillaforums.com', false, false],
            'path' => ['foo', 'bar', 0, '/site', null, false, false],
            'secure' => ['foo', 'bar', 0, null, null, true, false],
            'http-only' => ['foo', 'bar', 0, null, null, false, true],
            'complex' => ['foo', 'bar', 500, '/site', 'vanillaforums.com', true, true]
        ];
        return $data;
    }

    /**
     * Provide parameters for calculating a cookie's expiry.
     */
    public function provideExpiry() {
        $currentTimestamp = time();
        $data = [
            'Twenty-four hours' => [86400, ($currentTimestamp + 86400), $currentTimestamp],
            'One year' => [31536000, ($currentTimestamp + 31536000), $currentTimestamp],
            'Maximum' => [Cookie::EXPIRE_THRESHOLD, ($currentTimestamp + Cookie::EXPIRE_THRESHOLD), $currentTimestamp]
        ];
        $absoluteTimestamp = (Cookie::EXPIRE_THRESHOLD + 1);
        $absoluteDateTime = date('F j, Y H:i:s e', $absoluteTimestamp);
        $data[$absoluteDateTime] = [$absoluteTimestamp, $absoluteTimestamp, $currentTimestamp];
        return $data;
    }

    /**
     * Test calculating a cookie's expiry, relative to the current timestamp.
     *
     * @param int $expiry The integer offset or timestamp value.
     * @param int $expected The expected expiry, expressed as a timestamp.
     * @param int $timestamp The timestamp to be used as an offset for relative expiry values.
     * @dataProvider provideExpiry
     */
    public function testCalculateExpiry($expiry, $expected, $timestamp) {
        $cookie = new Cookie();
        $actual = $cookie->calculateExpiry($expiry, $timestamp);
        $this->assertEquals($expected, $actual);
    }

    /**
     * Test getting a single cookie value.
     */
    public function testGet() {
        $data = ['foo' => 'bar'];
        $cookie = new Cookie($data);

        $this->assertSame($data['foo'], $cookie->get('foo'));
        $this->assertNull($cookie->get('does-not-exist'));
        $this->assertSame('default-value', $cookie->get('does-not-exist', 'default-value'));
    }

    /**
     * Test generating a Cookie header.
     */
    public function testMakeCookieHeader() {
        $data = [
            'foo' => 'bar',
            'UserID' => 123,
            'TransientKey' => 'abcdefghij1234567890'
        ];
        $cookie = new Cookie($data);

        $header = $cookie->makeCookieHeader();
        $result = $this->cookieDecode($header);

        ksort($data);
        ksort($result);
        $this->assertEquals($data, $result);
    }

    /**
     * Test building parameters for deleted cookies.
     */
    public function testMakeDeleteCookieCalls() {
        $data = ['foo' => 'bar'];
        $cookie = new Cookie($data);
        $cookie->delete('foo');
        $result = $cookie->makeDeleteCookieCalls();
        $this->assertArrayHasKey('foo', $result);
    }

    /**
     * Test building parameters for new/modified cookies.
     */
    public function testMakeNewCookieCalls() {
        $data = [
            'foo' => 'bar',
            'UserID' => 123
        ];
        $cookie = new Cookie($data);
        $cookie->setFlushAll(false);
        $cookie->set('foo', 'bar');
        $cookie->set('forum', 'Vanilla');
        $cookie->set('UserID', 456);
        $result = $cookie->makeNewCookieCalls();

        $this->assertArrayHasKey('forum', $result);
        $this->assertEquals(456, $result['UserID'][0]);
        $this->assertArrayNotHasKey('foo', $result);
    }

    /**
     * Test building parameters for set cookies, even if the key/value already exists.
     */
    public function testMakeNewCookieCallsFlushAll() {
        $data = ['foo' => 'bar'];
        $cookie = new Cookie($data);
        $cookie->setFlushAll(true);
        $cookie->set('foo', 'bar');
        $result = $cookie->makeNewCookieCalls();

        $this->assertArrayHasKey('foo', $result);
    }

    /**
     * Test setting a cookie with limited options.
     *
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @dataProvider provideCookieSet
     */
    public function testSet($name, $value, $expire, $path, $domain, $secure, $httpOnly) {
        $cookie = new Cookie([]);
        $cookie->set($name, $value, $expire, $secure, $httpOnly);

        $data = $this->cookieDecode($cookie->makeCookieHeader());
        $this->assertSame($value, $data[$name]);

        $testExpire  = $cookie->calculateExpiry($expire);

        $result = $cookie->makeNewCookieCalls();
        $this->assertArrayHasKey($name, $result);
        $this->assertEquals($value, $result[$name][0]);
        $this->assertEquals($testExpire, $result[$name][1]);
        $this->assertSame($httpOnly, $result[$name][5]);
    }

    /**
     * Test setting a cookie with full options.
     *
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool $secure
     * @param bool $httpOnly
     * @dataProvider provideCookieSet
     */
    public function testSetCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly) {
        $cookie = new Cookie([]);
        $cookie->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);

        $data = $this->cookieDecode($cookie->makeCookieHeader());
        $this->assertSame($value, $data[$name]);

        $testPath = $path === null ? $cookie->getPath() : $path;
        $testDomain = $domain === null ? $cookie->getDomain() : $domain;

        $result = $cookie->makeNewCookieCalls();
        $this->assertArrayHasKey($name, $result);
        $this->assertEquals($value, $result[$name][0]);
        $this->assertEquals($testPath, $result[$name][2]);
        $this->assertEquals($testDomain, $result[$name][3]);
        $this->assertEquals($secure, $result[$name][4]);
        $this->assertEquals($httpOnly, $result[$name][5]);
    }

    /**
     * Test setting a cookie with sameSite attribute.
     *
     * @param string|null $expected
     * @param string $name
     * @param mixed $value
     * @param int $expire
     * @param string $path
     * @param string $domain
     * @param bool|null $secure
     * @param bool $httpOnly
     * @param string|null $sameSite
     * @dataProvider provideCookieSetSameSite
     */
    public function testSetCookieSameSite($expected, $name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite = null) {
        $cookie = new Cookie([]);
        $cookie->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly, $sameSite);

        $result = $cookie->makeNewCookieCalls();
        $this->assertEquals($expected, $result[$name][6]);
    }

    /**
     * Provide parameters for setting cookie security.
     *
     * @return array
     */
    public function provideCookieSetSecurity(): array {

        // Parameter order: expected, security
        $data = [
            'cookie-security-false' => [false, false],
            'cookie-security-true' => [true, true],
            'cookie-security-null' => [false, null],
        ];
        return $data;
    }

    /**
     * Test setting cookie security.
     *
     * @param bool|null $expected
     * @param bool|null $security
     * @dataProvider provideCookieSetSecurity
     */
    public function testSetCookieSecurityStatic($expected, $security): void {
        $cookie = new Cookie();
        $cookieName = 'foo';
        $cookie->setCookie($cookieName, 'bar', 0, '/site', 'vanillaforums.com', $security, true, null);
        $result = $cookie->makeNewCookieCalls();
        [$value, $expiry, $path, $domain, $secure, $httpOnly, $sameSite] = $result[$cookieName];
        $this->assertSame($expected, $secure);
    }

    /**
     * Test cookie::setSecure().
     *
     * @param bool $expected
     * @param bool $setSecure
     * @dataProvider provideCookieSetSecurityDynamic
     */
    public function testSetCookieSecurityDynamic(bool $expected, bool $setSecure): void {
        $cookie = new Cookie();
        $cookie->setSecure($setSecure);
        $cookieName = 'foo';
        $cookie->setCookie($cookieName, 'bar', 0, '/site', 'vanillaforums.com', null, true, null);

        $result = $cookie->makeNewCookieCalls();
        [$value, $expiry, $path, $domain, $secure, $httpOnly, $sameSite] = $result[$cookieName];
        $this->assertSame($expected, $secure);
    }
    /**
     * Provide parameters for setting cookie::setSecure().
     *
     * @return array
     */
    public function provideCookieSetSecurityDynamic(): array {

        // Parameter order: expected, Cookie::setSecure()
        $data = [
            'cookie-security-false' => [false, false],
            'cookie-security-true' => [true, true],
        ];
        return $data;
    }

    /**
     * Provide parameters for cookie-setting functions.
     *
     * @return array
     */
    public function provideCookieSetSameSite() {
        // Parameter order: expected, name, value, expire, path, domain, secure, httpOnly, sameSite
        $data = [
            'not-secure-samesite-null' => [null, 'foo', 'bar', 500, '/site', 'vanillaforums.com', false, true, null],
            'secure-samesite-null' => ['None', 'foo', 'bar', 500, '/site', 'vanillaforums.com', true, true, null],
            'not-secure-samesite' => [null, 'foo', 'bar', 500, '/site', 'vanillaforums.com', false, true, 'None'],
            'secure-samesite' => ['None', 'foo', 'bar', 500, '/site', 'vanillaforums.com', true, true, 'None']
        ];
        return $data;
    }

    /**
     * Setting a cookie with a null value deletes it.
     */
    public function testDeleteWithSet(): void {
        $cookie = new Cookie(['foo' => 'bar']);
        $cookie->setCookie('foo', null);

        $r = $cookie->makeDeleteCookieCalls();
        $this->assertArrayHasKey('foo', $r);
    }

    /**
     * The cookie prefix should be prepended to cookie names.
     */
    public function testCookiePrefix() {
        $cookie = new Cookie();
        $cookie->setPrefix('_');

        $cookie->set('a', 'b');
        $this->assertSame('b', $cookie->get('a'));
        $this->assertSame('b', $cookie->get('/_a'));

        $cookie->delete('a');
        $this->assertSame('foo', $cookie->get('a', 'foo'));
    }

    /**
     * I should be able to specify an absolute cookie name by prefixing with a "/".
     */
    public function testCookiePrefixRoot() {
        $cookie = new Cookie();
        $cookie->setPrefix('f');

        $cookie->set('/foo', 'bar');
        $this->assertSame('bar', $cookie->get('oo'));
    }
}
