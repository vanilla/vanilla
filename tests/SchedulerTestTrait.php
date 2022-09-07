<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Container\Reference;
use Garden\Http\HttpResponse;
use Garden\Web\Data;
use Garden\Web\Dispatcher;
use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\Job\JobStatusModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerResult;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\Middleware\SystemTokenMiddleware;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;

/**
 * Trait for testing jobs with the scheduler.
 */
trait SchedulerTestTrait
{
    /**
     * Make sure we have a clean scheduler for every test.
     */
    public function setupSchedulerTestTrait()
    {
        $this->getScheduler()->reset();
        $this->getLongRunner()->reset();
        $this->getLongRunner()->setTimeout(-1);
    }

    /**
     * @return InstantScheduler
     */
    protected function getScheduler(): InstantScheduler
    {
        return \Gdn::getContainer()->get(SchedulerInterface::class);
    }

    /**
     * @return LongRunner
     */
    protected function getLongRunner(): LongRunner
    {
        return \Gdn::getContainer()->get(LongRunner::class);
    }

    /**
     * @return JobStatusModel
     */
    protected function getJobStatusModel(): JobStatusModel
    {
        return \Gdn::getContainer()->get(JobStatusModel::class);
    }

    /**
     * Assert that there are a certain number of jobs with a particular status.
     *
     * @param int $expectedCount
     * @param array $where
     *
     * @return array The results.
     */
    protected function assertTrackedJobCount(int $expectedCount, array $where): array
    {
        $where += [
            "trackingUserID" => \Gdn::session()->UserID,
        ];
        $results = $this->getJobStatusModel()->select($where);
        TestCase::assertCount($expectedCount, $results);
        return $results;
    }

    /**
     * Make subsequent call requests based on a longRunner callbackPayload.
     *
     * @param string|HttpResponse|array|LongRunnerResult|Data $callbackPayload The payload from the long runner result.
     *
     * @return HttpResponse
     */
    protected function resumeLongRunner($callbackPayload): HttpResponse
    {
        if ($callbackPayload instanceof HttpResponse) {
            $callbackPayload = $callbackPayload->getBody();
        }

        if ($callbackPayload instanceof Data) {
            $callbackPayload = $callbackPayload->getData();
        }

        if (is_array($callbackPayload)) {
            $callbackPayload = $callbackPayload["callbackPayload"];
        }

        if ($callbackPayload instanceof LongRunnerResult) {
            $callbackPayload = $callbackPayload->getCallbackPayload();
        }

        if (!is_string($callbackPayload)) {
            TestCase::fail(
                "Cannot resume a longRunner without callback payload. Received: " .
                    json_encode($callbackPayload, JSON_PRETTY_PRINT)
            );
        }

        $response = $this->api()->post(
            "/calls/run",
            $callbackPayload,
            ["Content-Type" => SystemTokenMiddleware::AUTH_CONTENT_TYPE],
            ["throw" => false]
        );
        return $response;
    }
}
