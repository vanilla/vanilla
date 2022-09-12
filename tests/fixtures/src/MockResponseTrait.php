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
trait MockResponseTrait
{
    protected $mockedResponses = [];

    /**
     * Make the lookup key for a mock response.
     *
     * @param string $uri
     * @param string $method
     * @param string|array|null $body
     *
     * @return string
     */
    private function makeMockResponseKey(string $uri, string $method = HttpRequest::METHOD_GET, $body = null): string
    {
        $queryBody = $body && is_array($body) ? serialize($body) : $body;
        $bodyHash = $queryBody ? "-" . md5($queryBody) : "";
        return $method . "-" . $uri . $bodyHash;
    }

    /**
     * Add a single response to be queued up if a request is created.
     *
     * @param string $uri
     * @param HttpResponse $response
     * @param string $method
     * @param string|null $bodyRequest
     * @return $this
     */
    public function addMockResponse(
        string $uri,
        HttpResponse $response,
        string $method = HttpRequest::METHOD_GET,
        ?string $bodyRequest = null
    ) {
        $key = $this->makeMockResponseKey($uri, $method, $bodyRequest);
        $this->mockedResponses[$key] = $response;
        return $this;
    }
}
