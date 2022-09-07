<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Exception;
use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\EventManager;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\CallbackJob;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class CallbackTest
 *
 * @package VanillaTests\Library\Vanilla\Scheduler
 */
class CallbackTest extends SchedulerTestCase
{
    /**
     * Test callback job
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJob()
    {
        $deferredScheduler = $this->getDeferredScheduler();

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $result = false;
        $callbackJobDescriptor->setMessage([
            "callback" => function () use (&$result) {
                $result = true;
                return true;
            },
        ]);

        $deferredScheduler->addJobDescriptor($callbackJobDescriptor);
        [$trackingSlip] = $deferredScheduler->dispatchJobs();
        $this->assertTrue($result);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::complete())); // No exceptions were thrown.
    }

    /**
     * Test callback job with No-Callback functions
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJobWithWrongCallback()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid parameter configuration for Vanilla\Scheduler\Job\CallbackJob");

        $deferredScheduler = $this->getDeferredScheduler();

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage([
            "callback" => function () {
                return true;
            },
            "parameters" => true,
        ]);

        $deferredScheduler->addJobDescriptor($callbackJobDescriptor);
    }

    /**
     * Test callback job with No-Callback functions
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJobWithWrongParameters()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Invalid callback configuration for Vanilla\Scheduler\Job\CallbackJob");

        $deferredScheduler = $this->getDeferredScheduler();

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage([
            "callback" => true,
        ]);

        $deferredScheduler->addJobDescriptor($callbackJobDescriptor);
    }

    /**
     * TestDispatchedCallbackJob
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedCallbackJob()
    {
        $deferredScheduler = $this->getDeferredScheduler();

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage([
            "callback" => function () {
                return true;
            },
        ]);

        $deferredScheduler->addJobDescriptor($callbackJobDescriptor);
        /** @var $eventManager EventManager */
        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertTrue(count($trackingSlips) == 1);
        $complete = JobExecutionStatus::complete();
        $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
    }

    /**
     * Test callback job
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testFailedCallbackJob()
    {
        $deferredScheduler = $this->getDeferredScheduler();

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage([
            "callback" => function () {
                /** @noinspection PhpUndefinedFunctionInspection */
                /** @psalm-suppress UndefinedFunction */
                NonExistentFunction();
            },
        ]);

        $deferredScheduler->addJobDescriptor($callbackJobDescriptor);

        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertTrue(count($trackingSlips) == 1);
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::stackExecutionError()));
    }
}
