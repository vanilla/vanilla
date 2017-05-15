<?php
/**
 * @author Vanilla Forums Inc.
 * @copyright 2009-2017 Vanilla Forums Inc.
 * @license GPLv2
 */

namespace VanillaTests\Library\Garden\Web;

use Garden\Web\Cookie;

/**
 * Test the {@link ResourceRoute} class.
 */
class CookieTest extends \PHPUnit\Framework\TestCase {

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
        $this->assertEquals($httpOnly, $result[$name][5]);
    }

    /**
     * Test setting a cookie with full options.
     *
     * @dataProvider provideCookieSet
     */
    public function testSetCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly) {
        $cookie = new Cookie([]);
        $cookie->setCookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);

        $data = $this->cookieDecode($cookie->makeCookieHeader());
        $this->assertSame($value, $data[$name]);

        $testExpire  = $cookie->calculateExpiry($expire);
        $testPath = $path === null ? $cookie->getPath() : $path;
        $testDomain = $domain === null ? $cookie->getDomain() : $domain;

        $result = $cookie->makeNewCookieCalls();
        $this->assertArrayHasKey($name, $result);
        $this->assertEquals($value, $result[$name][0]);
        $this->assertEquals($testExpire, $result[$name][1]);
        $this->assertEquals($testPath, $result[$name][2]);
        $this->assertEquals($testDomain, $result[$name][3]);
        $this->assertEquals($secure, $result[$name][4]);
        $this->assertEquals($httpOnly, $result[$name][5]);
    }
}
