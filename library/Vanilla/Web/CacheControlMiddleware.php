<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Web;

use Garden\Web\Data;
use Garden\Web\RequestInterface;
use Gdn_Session as SessionInterface;

/**
 * Dispatcher middleware for handling caching headers.
 */
class CacheControlMiddleware implements CacheControlConstantsInterface
{
    use CacheControlTrait;

    /** @var SessionInterface An instance of the current user session. */
    private $session;

    /**
     * CacheControlMiddleware constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Invoke the cache control middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next)
    {
        $response = Data::box($next($request));

        if (!$response->hasHeader(self::HEADER_CACHE_CONTROL)) {
            $response->setHeader(
                self::HEADER_CACHE_CONTROL,
                $this->session->isValid() || $request->getMethod() !== "GET" ? self::NO_CACHE : self::PUBLIC_CACHE
            );
        }

        if (
            $response->getHeader(self::HEADER_CACHE_CONTROL) !== self::NO_CACHE &&
            !$response->getMeta(self::META_NO_VARY)
        ) {
            // Unless we have NO_CACHE set make sure to set the vary header.
            $response->setHeader("Vary", self::VARY_COOKIE);
        }

        foreach (static::getHttp10Headers($response->getHeader(self::HEADER_CACHE_CONTROL)) as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }
}
