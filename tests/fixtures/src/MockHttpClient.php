<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Http\HttpClient;
use Garden\Http\HttpResponse;

/**
 * Mock HTTP client for testing. Does send actual HTTP requests.
 */
class MockHttpClient extends HttpClient {

    use MockResponseTrait;

    /**
     * The default constructor adds request sending middleware. We don't want to do that.
     * We're going to use our own middleware instead.
     * @inheritdoc
     */
    public function __construct(string $baseUrl = '') {
        parent::__construct($baseUrl);

        // One big difference is the mock middleware starts with a response instead of a request.
        $this->middleware = function (HttpResponse $response): HttpResponse {
            return $response;
        };
    }

    /**
     * Instead of making a web request, check our predefined responses and return one of those.
     *
     * @overrid
     * @inheritdoc
     */
    public function request(string $method, string $uri, $body, array $headers = [], array $options = []) {
        $key = $this->makeMockResponseKey($uri, $method);

        // Lookup an existing mock response or send back a 404.
        $response = $this->mockedResponses[$key] ?? new HttpResponse(404);

        // Call the chain of middleware on the request.
        $response = call_user_func($this->middleware, $response);

        if (!$response->isResponseClass('2xx')) {
            $this->handleErrorResponse($response, $options);
        }

        return $response;
    }
}
