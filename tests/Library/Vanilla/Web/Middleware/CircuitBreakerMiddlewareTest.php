<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license Proprietary
 */

namespace VanillaTests\Library\Vanilla\Web\Middleware;

use Garden\Http\HttpResponse;
use Garden\Web\Exception\ServerException;
use Gdn_Cache;
use Vanilla\CurrentTimeStamp;
use Vanilla\Web\Middleware\CircuitBreakerMiddleware;
use VanillaTests\Fixtures\Request;
use VanillaTests\SiteTestCase;

/**
 * Test for the CircuitBreakerMiddleware.
 */
class CircuitBreakerMiddlewareTest extends SiteTestCase
{
    /** @var Gdn_Cache */
    protected $cache;

    /** @var CircuitBreakerMiddleware */
    protected CircuitBreakerMiddleware $circuitBreakerMiddleware;

    /**
     * @inheritDoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->cache = $this->enableCaching();
        $this->circuitBreakerMiddleware = new CircuitBreakerMiddleware($this->cache);
    }

    /**
     * Test when the circuit breaker is already in place.
     *
     * @return void
     */
    public function testCircuitBreakerOnBeforeNextAttempt(): void
    {
        $this->expectException(ServerException::class);
        $request = new Request();
        $cacheKey = CircuitBreakerMiddleware::getCacheKey($request);
        $this->setFailedAttemptCache($cacheKey, 6);

        $nextAttempt = CurrentTimeStamp::get() + 3600;
        $this->setNextAttemptAtCache($cacheKey, $nextAttempt);

        $this->invokeCircuitBreaker($request);
    }

    /**
     * Test when the circuit breaker is already in place and the next attempt is successful.
     *
     * @return void
     */
    public function testCircuitBreakerOnAfterNextAttemptRequestSuccessful(): void
    {
        $request = new Request();
        $cacheKey = CircuitBreakerMiddleware::getCacheKey($request);
        $this->setFailedAttemptCache($cacheKey, 6);

        $nextAttempt = CurrentTimeStamp::get() - 3600;
        $this->setNextAttemptAtCache($cacheKey, $nextAttempt);

        $response = $this->invokeCircuitBreaker($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->cache->exists($cacheKey . CircuitBreakerMiddleware::NEXT_ATTEMPTS_AT));
    }

    /**
     * Test when the circuit breaker is not in place and the next attempt is successful. We expect nothing to happen.
     *
     * @return void
     */
    public function testCircuitBreakerOnSuccessfulRequest(): void
    {
        $request = new Request();
        $cacheKey = CircuitBreakerMiddleware::getCacheKey($request);
        $response = $this->invokeCircuitBreaker($request);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertFalse($this->cache->exists($cacheKey . CircuitBreakerMiddleware::NEXT_ATTEMPTS_AT));
    }

    /**
     * Test when the circuit breaker is already in place and the next attempt is fails.
     *
     * @param int|null $failedAttempts
     * @param bool $circuitOn
     * @dataProvider provideAttemptData
     * @return void
     */
    public function testCircuitBreakerOnAfterNextAttemptRequestFails(?int $failedAttempts, bool $circuitOn): void
    {
        $request = new Request();
        $cacheKey = CircuitBreakerMiddleware::getCacheKey($request);
        $this->setFailedAttemptCache($cacheKey, $failedAttempts);

        $nextAttempt = CurrentTimeStamp::get() - 3600;
        $this->setNextAttemptAtCache($cacheKey, $nextAttempt);

        $response = $this->invokeCircuitBreaker($request, 404);

        $expectedAttemptsCount = $failedAttempts ?? 0;
        $expectedAttemptsCount++;

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals(
            $expectedAttemptsCount,
            $this->cache->get($cacheKey . CircuitBreakerMiddleware::FAILED_ATTEMPTS)
        );

        if ($circuitOn) {
            $this->assertGreaterThan(
                $nextAttempt,
                $this->cache->get($cacheKey . CircuitBreakerMiddleware::NEXT_ATTEMPTS_AT)
            );
        }
    }

    /**
     * Test the escalation of the circuit breaker.
     *
     * @param int $failedAttempts
     * @param int $expected
     * @dataProvider provideEscalationData
     * @return void
     */
    public function testCircuitBreakerEscalation(int $failedAttempts, int $expected): void
    {
        $addedTime = CircuitBreakerMiddleware::getNextAttemptAt($failedAttempts);
        $this->assertEquals($expected, $addedTime);
    }

    /**
     * Invoke the CircuitBreakerMiddle ware.
     *
     * @param Request $request
     * @param int $responseCode
     * @return HttpResponse
     */
    protected function invokeCircuitBreaker(Request $request, int $responseCode = 200): HttpResponse
    {
        $circuitBreakerMiddleware = new CircuitBreakerMiddleware($this->cache);
        $response = $circuitBreakerMiddleware($request, function () use ($responseCode) {
            return new HttpResponse($responseCode);
        });
        return $response;
    }

    /**
     * Data Provider for testCircuitBreakerOnAfterNextAttemptRequestFails().
     *
     * @return array[]
     */
    public static function provideAttemptData(): array
    {
        $r = [
            "noFailedResponse" => [null, false],
            "oneFailedResponse" => [1, false],
            "limitFailedResponse" => [CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD, false],
            "overThresholdFailedResponse" => [CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD + 1, true],
        ];
        return $r;
    }

    /**
     * Data Provider for invokeCircuitBreaker().
     *
     * @return array
     */
    public function provideEscalationData(): array
    {
        $r = [
            "noFailedResponse" => [0, 0],
            "oneFailedResponse" => [1, 0],
            "limitFailedResponse" => [CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD, 0],
            "overThresholdFailedResponse" => [
                CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD + 1,
                CircuitBreakerMiddleware::BASE_WAIT_TIME * 2,
            ],
            "overThresholdFailedResponse+1" => [
                CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD + 2,
                CircuitBreakerMiddleware::BASE_WAIT_TIME * 4,
            ],
            "overThresholdFailedResponse+2" => [
                CircuitBreakerMiddleware::ATTEMPTS_THRESHOLD + 3,
                CircuitBreakerMiddleware::BASE_WAIT_TIME * 8,
            ],
        ];
        return $r;
    }

    /**
     * Set the cache value of for the CircuitBreaker failedAttempt.
     *
     * @param string $cacheKey
     * @param int|null $failedAttempt
     * @return void
     */
    protected function setFailedAttemptCache(string $cacheKey, ?int $failedAttempt): void
    {
        $this->cache->store($cacheKey . CircuitBreakerMiddleware::FAILED_ATTEMPTS, $failedAttempt);
    }

    /**
     * Set the cache value of for the CircuitBreaker nextAttemptAt.
     *
     * @param string $cacheKey
     * @param int $nextAttemptAt
     * @return void
     */
    protected function setNextAttemptAtCache(string $cacheKey, int $nextAttemptAt): void
    {
        $this->cache->store($cacheKey . CircuitBreakerMiddleware::NEXT_ATTEMPTS_AT, $nextAttemptAt);
    }
}
