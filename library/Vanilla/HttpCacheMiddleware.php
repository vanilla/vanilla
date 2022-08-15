<?php
/**
 * @author Todd Burry <todd@vanillaforums.com>, Pavel Gonncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

/**
 * Class HttpCacheMiddleware
 * @package Vanilla
 */
class HttpCacheMiddleware
{
    const NO_CACHE = "-1";

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * HttpCacheMiddleware constructor.
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Make cache key
     *
     * @param HttpRequest $request
     * @return string
     */
    private function makeCacheKey(HttpRequest $request): string
    {
        $cacheKey = $request->getUrl();
        $cacheKey = sha1($cacheKey);

        return $cacheKey;
    }

    /**
     * Invoke cache middleware to http Request.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     */
    public function __invoke(HttpRequest $request, callable $next): HttpResponse
    {
        $ttl = (int) $request->getHeader("X-Cache");

        // Only cache GET requests.
        if ($request->getMethod() !== HttpRequest::METHOD_GET || $ttl < 0) {
            return $next($request);
        }

        $cacheKey = $this->makeCacheKey($request);
        try {
            $hasCache = $this->cache->has($cacheKey);
            if (!$hasCache) {
                /* @var HttpResponse $response */
                $response = $next($request);

                if ($ttl === 1 || $ttl === 0) {
                    $ttl = strtotime("12 hours", 0);
                }

                if ($response->isSuccessful()) {
                    $this->cache->set($cacheKey, $response, $ttl);
                }
            } else {
                $response = $this->cache->get($cacheKey);
            }
        } catch (InvalidArgumentException $e) {
            $response = null;
        }
        return $response;
    }
}
