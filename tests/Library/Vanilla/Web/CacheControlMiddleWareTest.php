<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use PHPUnit\Framework\TestCase;
use Vanilla\Web\CacheControlMiddleware;

/**
 * Test for functions of CacheControlMiddleWare
 */

class CacheControlMiddleWareTest extends TestCase {

    /**
     * Test getHttp10Headers() with max-time set to 0.
     */
    public function testGetHttp10HeadersWithMaxTimeZero() {
        $actual = CacheControlMiddleware::getHttp10Headers('private, max-age=0, no-cache');
        $expected = ['Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT', 'Pragma' => 'no-cache'];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test getHttp10Headers() with max-time not set to zero.
     */
    public function testGetHttpHeadersWithMaxTimeGreaterThanZero() {
        $actual = CacheControlMiddleware::getHttp10Headers('private, max-age=120');
        $expected = ['Expires' => gmdate('D, d M Y H:i:s T', time() + 120)];
        $this->assertSame($expected, $actual);
    }

    /**
     * Test getHttpHeaders() with empty array.
     */
    public function testGetHttpHeadersWithEmptyArray() {
        $actual = CacheControlMiddleware::getHttp10Headers('');
        $expected = [];
        $this->assertSame($expected, $actual);
    }
}
