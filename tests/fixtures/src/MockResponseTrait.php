<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Trait for mocking HTTP responses.
 */
trait MockResponseTrait {
    protected $mockedResponses = [];

    /**
     * Make the lookup key for a mock response.
     *
     * @param string $uri
     * @param string $method
     *
     * @return string
     */
    private function makeMockResponseKey(string $uri, string $method = HttpRequest::METHOD_GET): string {
        return $method . '-' . $uri;
    }

    /**
     * Add a single response to be queued up if a request is created.
     *
     * @param string $uri
     * @param HttpResponse|array $response
     * @param string $method
     *
     * @return $this
     */
    public function addMockResponse(
        string $uri,
        $response,
        string $method = HttpRequest::METHOD_GET
    ) {
        $key = $this->makeMockResponseKey($uri, $method);
        $this->mockedResponses[$key] = $response;
        return $this;
    }
}
