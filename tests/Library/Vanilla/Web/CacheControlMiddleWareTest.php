<?php
/**
 * @author Richard Flynn <richard.flynn@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Session;
use PHPUnit\Framework\TestCase;
use Vanilla\Web\CacheControlMiddleware;
use VanillaTests\Fixtures\Request;

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

    /**
     * Test __invoke().
     *
     * @param int $userID The userID code.
     * @param array $headers Array of http headers.
     * @param mixed $expected The expected result.
     * @dataProvider provideTestInvokeArrays
     */
    public function testInvoke(int $userID, array $headers, $expected) {
        $testSession = new Gdn_Session();
        $testSession->UserID = $userID;
        $testRequest = new Request('/', 'GET', [1, 2, 3]);
        $testObject = new CacheControlMiddleware($testSession);
        $modifiedObject = $testObject($testRequest, function (RequestInterface $request) use ($headers) {
            $r = new Data([]);

            foreach ($headers as $key => $value) {
                $r->setHeader($key, $value);
            }

            return $r;
        });
        $actual = $modifiedObject->getHeaders();

        if ($actual['Expires'] !== 'Sat, 01 Jan 2000 00:00:00 GMT') {
            unset($actual['Expires']);
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * Provide test data for testInvoke().
     *
     * @return array Returns an array of test data.
     */
    public function provideTestInvokeArrays() {
        $r = [
            'userIDZeroAndPublicCache' => [
                0,
                ['Cache-Control' => CacheControlMiddleware::PUBLIC_CACHE],
                [
                    'Cache-Control' => 'public, max-age=120',
                    'Vary' => 'Accept-Encoding, Cookie',
                ],
            ],
            'userIDZeroAndNoCache' => [
                0,
                ['Cache-Control' => CacheControlMiddleware::NO_CACHE],
                [
                    'Cache-Control' => 'private, no-cache, max-age=0, must-revalidate',
                    'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
                    'Pragma' => 'no-cache',
                ],
            ],
            'userIDZeroAndNoCacheControlHeaderAtAll' => [
                0,
                ['Foo' => 'bar'],
                [
                    'Foo' => 'bar',
                    'Cache-Control' => 'public, max-age=120',
                    'Vary' => 'Accept-Encoding, Cookie'
                ],
            ],
            'userID1AndPublicCache' => [
                1,
                ['Cache-Control' => CacheControlMiddleware::PUBLIC_CACHE],
                [
                    'Cache-Control' => 'public, max-age=120',
                    'Vary' => 'Accept-Encoding, Cookie',
                ],
            ],
            'userID1AndNoCache' => [
                1,
                ['Cache-Control' => CacheControlMiddleware::NO_CACHE],
                [
                    'Cache-Control' => 'private, no-cache, max-age=0, must-revalidate',
                    'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
                    'Pragma' => 'no-cache',
                ],
            ],
            'userID1AndNoCacheControlHeaderAtAll' => [
                1,
                ['Foo' => 'bar'],
                [
                    'Foo' => 'bar',
                    'Cache-Control' => 'private, no-cache, max-age=0, must-revalidate',
                    'Expires' => 'Sat, 01 Jan 2000 00:00:00 GMT',
                    'Pragma' => 'no-cache',
                ],
            ],

        ];

        return $r;
    }
}
