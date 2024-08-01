<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Http\HttpResponse;
use Garden\Web\Data;
use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerResult;
use Vanilla\Scheduler\SchedulerInterface;
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
            [
                "content-type" => "application/system+jwt",
            ],
            [
                "timeout" => 25,
                "throw" => false,
            ]
        );
        return $response;
    }
}
