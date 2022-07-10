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
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Scheduler\TrackingSlip;

/**
 * Class CallbackTest
 *
 * @package VanillaTests\Library\Vanilla\Scheduler
 */
class CallbackTest extends SchedulerTestCase {

    /**
     * Test callback job
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage(
            [
                "callback" => function () {
                    return true;
                },
            ]
        );

        $dummyScheduler->addJobDescriptor($callbackJobDescriptor);
    }

    /**
     * Test callback job with No-Callback functions
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJobWithWrongCallback() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid parameter configuration for Vanilla\Scheduler\Job\CallbackJob');

        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage(
            [
                "callback" => function () {
                    return true;
                },
                "parameters" => true,
            ]
        );

        $dummyScheduler->addJobDescriptor($callbackJobDescriptor);
    }

    /**
     * Test callback job with No-Callback functions
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testCallbackJobWithWrongParameters() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid callback configuration for Vanilla\Scheduler\Job\CallbackJob');

        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage(
            [
                "callback" => true,
            ]
        );

        $dummyScheduler->addJobDescriptor($callbackJobDescriptor);
    }

    /**
     * TestDispatchedCallbackJob
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedCallbackJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage(
            [
                "callback" => function () {
                    return true;
                },
            ]
        );

        $dummyScheduler->addJobDescriptor($callbackJobDescriptor);

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);
        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $complete = JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test callback job
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testFailedCallbackJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $callbackJobDescriptor = new NormalJobDescriptor(CallbackJob::class);
        $callbackJobDescriptor->setMessage(
            [
                "callback" => function () {
                    /** @noinspection PhpUndefinedFunctionInspection */
                    /** @psalm-suppress UndefinedFunction */
                    NonExistentFunction();
                },
            ]
        );

        $dummyScheduler->addJobDescriptor($callbackJobDescriptor);

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);
        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::stackExecutionError()));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }
}
