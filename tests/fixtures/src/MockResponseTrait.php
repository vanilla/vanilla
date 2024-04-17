<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Fixtures;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Vanilla\Utility\ArrayUtils;

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
        if (is_array($body)) {
            $body = ArrayUtils::flattenArray(".", $body);
            ksort($body);
            $body = json_encode($body);
        }
        $bodyHash = md5($body);
        return $method . "-" . $uri . $bodyHash;
    }

    /**
     * Add a single response to be queued up if a request is created.
     *
     * @param string $uri
     * @param HttpResponse $response
     * @param string $method
     * @param string|null|array $bodyRequest
     * @return $this
     */
    public function addMockResponse(
        string $uri,
        HttpResponse $response,
        string $method = HttpRequest::METHOD_GET,
        $bodyRequest = null
    ) {
        $key = $this->makeMockResponseKey($uri, $method, $bodyRequest);
        $mockRequest = new HttpRequest($method, $uri, $bodyRequest);
        $response->setRequest($mockRequest);
        $this->mockedResponses[$key] = $response;
        return $this;
    }
}
