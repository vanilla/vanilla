<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Utility\Spans;

use Garden\Http\HttpResponse;

/**
 * Span for tracking a http request.
 */
class RequestSpan extends AbstractSpan
{
    /**
     * @param \Garden\Http\HttpRequest|\Garden\Web\RequestInterface $request
     * @param string|null $parentUuid
     */
    public function __construct($request, ?string $parentUuid)
    {
        parent::__construct("http-request", $parentUuid, [
            "method" => $request->getMethod(),
            "url" => $this->getRequestUrl($request),
            "query" => new \ArrayObject($this->getRequestQuery($request)),
        ]);
    }

    /**
     * @param \Garden\Http\HttpRequest|\Garden\Web\RequestInterface $request
     * @return array
     */
    private function getRequestQuery($request): array
    {
        if ($request instanceof \Garden\Http\HttpRequest) {
            parse_str($request->getUri()->getQuery(), $params);
            return $params;
        } else {
            return $request->getQuery();
        }
    }

    /**
     * @param \Garden\Http\HttpRequest|\Garden\Web\RequestInterface $request
     * @return string
     */
    private function getRequestUrl($request): string
    {
        if ($request instanceof \Garden\Http\HttpRequest) {
            // Include hostname for external requests.
            $url = $request->getUrl();
        } else {
            $url = $request->getPath();
        }

        // Strip off the query.
        $url = str_replace("?" . $request->getUri()->getQuery(), "", $url);
        return $url;
    }

    /**
     * Nothing special to finish here.
     *
     * @param HttpResponse|null $response
     *
     * @return RequestSpan
     */
    public function finish(?HttpResponse $response = null): RequestSpan
    {
        return parent::finishInternal([
            "statusCode" => $response ? $response->getStatusCode() : null,
        ]);
    }
}
