<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2023-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\APIv0;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;

/**
 * Class CookieAwareMiddleware
 * @package Vanilla\Web\Middleware
 */
class CookieAwareMiddleware
{
    /** @var string */
    private string $cookie;

    /**
     * CookiePassMiddleware constructor.
     * @param string $cookie cookie to add to the http calls.
     */
    public function __construct(string $cookie)
    {
        $this->cookie = $cookie;
    }

    /**
     * Adds new cookie to existing.
     *
     * @param string $cookie
     * @return void
     */
    public function setCookie(string $cookie): void
    {
        $this->cookie .= ";" . $cookie;
    }

    /**
     * Invoke the cookie pass middleware on a request.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse
    {
        $cookie = $request->getHeader("Cookie");
        if (empty($cookie)) {
            // Pass the cookies from the request.
            $request->setHeader("Cookie", $this->cookie);
            $result = $next($request);
            // Header set for embedding, internal url should not be cached
            // because they are generated based on the permissions from the current session
            $result->setHeader("X-No-Cache", true);
        } else {
            $result = $next($request);
        }

        return $result;
    }
}
