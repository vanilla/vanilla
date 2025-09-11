<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Web\Middleware;

use Garden\Http\HttpRequest;
use Garden\Http\HttpResponse;
use Garden\Web\Exception\ServerException;
use Garden\Web\RequestInterface;
use Gdn_Cache;
use Vanilla\CurrentTimeStamp;

/**
 * Apply the Circuit Breaker pattern to limit the number of calls made when an endpoint fails.
 */
class CircuitBreakerMiddleware
{
    const ATTEMPTS_THRESHOLD = 5;
    const BASE_WAIT_TIME = 30;
    const NEXT_ATTEMPTS_AT = ".nextAttemptsAt";
    const FAILED_ATTEMPTS = ".failedAttempts";

    public function __construct(private Gdn_Cache $cache)
    {
    }

    /**
     * Implement the CircuitBreaker pattern to limit the number of calls made when an endpoint fails.
     *
     * @param HttpRequest $request
     * @param callable $next
     * @return HttpResponse
     * @throws ServerException
     */
    public function __invoke(HttpRequest $request, callable $next)
    {
        $cacheKey = $this->getCacheKey($request);
        $nextAttemptsAt = $this->cache->get($cacheKey . self::NEXT_ATTEMPTS_AT);
        if ($nextAttemptsAt && $nextAttemptsAt > CurrentTimeStamp::get()) {
            throw new ServerException("Upstream service {$request->getUri()->getHost()} is having trouble.", 500);
        }

        /** @var HttpResponse $response */
        $response = $next($request);

        if ($response->isSuccessful()) {
            $this->cache->remove($cacheKey . self::FAILED_ATTEMPTS);
            $this->cache->remove($cacheKey . self::NEXT_ATTEMPTS_AT);
        } else {
            $this->setOrHydrateCache($cacheKey);
        }

        return $response;
    }

    /**
     * Calculate the timestamp for the next attempt. Will double the time for each failed attempts past the base threshold.
     *
     * e.g.
     *
     * 1-5 -> No time
     * 6 -> 1 min
     * 7 -> 2 min
     * 8 -> 4 min
     *
     * @param int $failedAttempts
     * @return int
     */
    public static function getNextAttemptAt(int $failedAttempts = 0): int
    {
        if ($failedAttempts > self::ATTEMPTS_THRESHOLD) {
            $time = self::BASE_WAIT_TIME * 2 ** ($failedAttempts - self::ATTEMPTS_THRESHOLD);
        } else {
            $time = 0;
        }

        return $time;
    }

    /**
     * Generate a cacheKey based on the HostName of the request.
     *
     * @param HttpRequest $request
     * @return string
     */
    public static function getCacheKey(HttpRequest $request): string
    {
        $cacheKey = "circuit-breaker" . $request->getUri()->getHost();
        $cacheKey = sha1($cacheKey);
        return $cacheKey;
    }

    /**
     * Set or update the middleware cache values.
     *
     * @param string $cacheKey
     * @return void
     */
    protected function setOrHydrateCache(string $cacheKey): void
    {
        $failedAttempts = $this->cache->increment($cacheKey . self::FAILED_ATTEMPTS, 1, [
            Gdn_Cache::FEATURE_INITIAL => 1,
        ]);
        $nextAttemptAt = CurrentTimeStamp::get() + $this->getNextAttemptAt($failedAttempts);
        $this->cache->store($cacheKey . self::NEXT_ATTEMPTS_AT, $nextAttemptAt);
    }
}
