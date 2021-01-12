<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web\Middleware;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Web\Cookie;
use Garden\Web\RequestInterface;

/**
 * Class CookiePassMiddleware
 * @package Vanilla\Web\Middleware
 */
class CookiePassMiddleware {

    /**
     * @var RequestInterface
     */
    private $pageRequest;

    /**
     * CookiePassMiddleware constructor.
     * @param RequestInterface $pageRequest
     */
    public function __construct(RequestInterface $pageRequest) {
        $this->pageRequest = $pageRequest;
    }

    /**
     * Invoke the cookie pass middleware on a request.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse {
        $cookie = $this->pageRequest->getHeader('Cookie');
        if ($this->pageRequest->getHost() === parse_url($request->getUrl(), PHP_URL_HOST) && !empty($cookie)) {
            // Pass the cookies from the request.
            $request->setHeader('Cookie', $cookie);
            $result = $next($request);
            // Header set for embedding, internal url should not be cached
            // because they are generated based on the permissions from the current session
            $result->setHeader('X-No-Cache', true);
        } else {
            $result = $next($request);
        }

        return $result;
    }
}
