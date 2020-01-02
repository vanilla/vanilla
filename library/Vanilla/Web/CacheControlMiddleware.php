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
class CacheControlMiddleware {

    /** @var string Standard Cache-Control header string for public, cacheable content. */
    const PUBLIC_CACHE = 'public, max-age=120';

    /** @var string Standard Cache-Control header for content that should not be cached. */
    const NO_CACHE = 'private, no-cache, max-age=0, must-revalidate';

    /** @var string Standard vary header when using public cache control based on session. */
    const VARY_COOKIE = 'Accept-Encoding, Cookie';

    /** @var SessionInterface An instance of the current user session. */
    private $session;

    /**
     * CacheControlMiddleware constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session) {
        $this->session = $session;
    }

    /**
     * Translate a Cache-Control header into HTTP/1.0 Expires and Pragma headers.
     *
     * @param string $cacheControl A valid Cache-Control header value.
     * @return array
     */
    public static function getHttp10Headers(string $cacheControl): array {
        $result = [];

        if (preg_match('`max-age=(\d+)`', $cacheControl, $m)) {
            if ($m[1] === '0') {
                $result['Expires'] = 'Sat, 01 Jan 2000 00:00:00 GMT';
                $result['Pragma'] = 'no-cache';
            } else {
                $result['Expires'] = gmdate('D, d M Y H:i:s T', time() + $m[1]);
            }
        }

        return $result;
    }

    /**
     * Invoke the cache control middleware on a request.
     *
     * @param RequestInterface $request The incoming request.
     * @param callable $next The next middleware.
     * @return mixed Returns the response of the inner middleware.
     */
    public function __invoke(RequestInterface $request, callable $next) {
        $response = Data::box($next($request));

        if (!$response->hasHeader('Cache-Control')) {
            $response->setHeader(
                'Cache-Control',
                $this->session->isValid() || $request->getMethod() !== 'GET' ?  self::NO_CACHE : self::PUBLIC_CACHE
            );
        }

        if ($response->getHeader('Cache-Control') !== self::NO_CACHE) {
            // Unless we have NO_CACHE set make sure to set the vary header.
            $response->setHeader('Vary', self::VARY_COOKIE);
        }

        foreach (static::getHttp10Headers($response->getHeader('Cache-Control')) as $key => $value) {
            $response->setHeader($key, $value);
        }

        return $response;
    }

    /**
     * A convenience method for sending cache control headers directly to the response with the `header()` function.
     *
     * @param string $cacheControl The value of the cache control header.
     */
    public static function sendCacheControlHeaders(string $cacheControl) {
        safeHeader("Cache-Control: $cacheControl");
        foreach (static::getHttp10Headers($cacheControl) as $key => $value) {
            safeHeader("$key: $value");
        }
        if ($cacheControl === self::NO_CACHE) {
            header('Vary: '.self::VARY_COOKIE);
        }
    }
}
