<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Http;

use Garden\Http\HttpResponse;
use Garden\Web\Data;
use Vanilla\Http\InternalClient;
use Vanilla\Http\InternalResponse;

/**
 * Internal HTTP client for testing.
 *
 * Extends {@link InternalClient} to ensure {@link TestHttpResponse} instances are returned.
 *
 * @extends InternalClient<TestHttpResponse>
 */
class TestHttpClient extends InternalClient
{
    /**
     * @param HttpResponse $response
     * @return TestHttpResponse
     */
    protected function castResponse(HttpResponse $response): TestHttpResponse
    {
        if ($response instanceof TestHttpResponse) {
            return $response;
        }

        $data = new Data($response->getBody(), $response->getStatusCode(), $response->getHeaders());
        $wrapped = new TestHttpResponse($data);
        if ($response instanceof InternalResponse) {
            $wrapped->setThrowable($response->getThrowable());
        }
        $wrapped->setRequest($response->getRequest());
        return $wrapped;
    }
}
