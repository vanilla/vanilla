<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Garden\Http\HttpResponse;
use Vanilla\Web\Middleware\LogTransactionMiddleware;
use VanillaTests\APIv2\AbstractAPIv2Test;
use VanillaTests\Fixtures\MockHttpClient;

/**
 * Tests for logging using the transaction middleware.
 */
class LogTransactionMiddlewareTest extends AbstractAPIv2Test {

    /**
     * Test the we can retreive a value from the middleware.
     *
     * @param array $headers
     * @param int|null $expected
     *
     * @dataProvider provideHeadersAndValues
     */
    public function testStoreGetTransactionID(array $headers, ?int $expected) {
        $mockClient = new MockHttpClient();
        $mockClient->addMockResponse('/test', new HttpResponse(200));

        $middleware = new LogTransactionMiddleware();
        $mockClient->addMiddleware($middleware);

        $mockClient->get('/test', [], $headers);
        $this->assertSame($expected, $middleware->getTransactionID());
    }

    /**
     * @return array
     */
    public function provideHeadersAndValues(): array {
        return [
            'good value' => [
                [LogTransactionMiddleware::HEADER_NAME => '41351'],
                41351,
            ],
            'no value' => [
                [],
                null,
            ],
            'bad value' => [
                [LogTransactionMiddleware::HEADER_NAME => 'asdfasdfasdf'],
                null,
            ],
        ];
    }
}
