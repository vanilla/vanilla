<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Core;

use PHPUnit\Framework\TestCase;

/**
 * Tests for the user agent type.
 * @backupGlobals enabled
 */
class UserAgentTypeTest extends TestCase {
    const HEADER_NAME = 'HTTP_X_UA_DEVICE';
    const COOKIE_NAME = 'X-UA-Device-Force';

    public function tearDown() {
        parent::tearDown();
        userAgentType(false);
    }

    /**
     * Test device detection from the header.
     *
     * @param string $value
     * @param string $expected
     * @dataProvider provideForceValues
     */
    public function testHeaderForce(string $value, string $expected) {
        $_SERVER[self::HEADER_NAME] = $value;

        $actual = userAgentType();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test device detection from a forced cookie.
     *
     * @param string $value
     * @param string $expected
     * @dataProvider provideForceValues
     */
    public function testCookieForce(string $value, string $expected) {
        $_COOKIE[self::COOKIE_NAME] = $value;

        $actual = userAgentType();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test device detection from the user agent.
     *
     * @param string $agent
     * @param string $expected
     * @dataProvider provideUserAgents
     */
    public function testUserAgentDetect(string $agent, string $expected) {
        $_SERVER['HTTP_USER_AGENT'] = $agent;

        $actual = userAgentType();

        $this->assertEquals($expected, $actual);
    }

    /**
     * Provide user agent header tests.
     *
     * @return array
     */
    public function provideForceValues(): array {
        $r = [
            ['mobile', 'mobile'],
            ['desktop', 'desktop'],
            ['tablet', 'tablet'],
            ['app', 'app'],
            ['unknown', 'desktop'],
            ['mobile;tablet', 'tablet'],
        ];

        return array_column($r, null, 0);
    }

    /**
     * Provide some sample user agents.
     *
     * This isn't an exhaustive list.
     *
     * @return array
     */
    public function provideUserAgents(): array {
        $r = [
            [
                'Mozilla/5.0 (Linux; Android 8.1.0; Pixel 2 Build/OPM2.171026.006.G1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/67.0.3396.87 Mobile Safari/537.36',
                'mobile',
            ],
            [
                'Mozilla/5.0 (iPhone; CPU iPhone OS 11_0 like Mac OS X) AppleWebKit/604.1.38 (KHTML, like Gecko) Version/11.0 Mobile/15A372 Safari/604.1',
                'mobile',
            ],
            [
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_14_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/77.0.3865.120 Safari/537.36',
                'desktop',
            ],
        ];

        return $r;
    }
}
