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
use Vanilla\Scheduler\DummyScheduler;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Scheduler\TrackingSlip;
use VanillaTests\Fixtures\Scheduler\EchoAwareJob;
use VanillaTests\Fixtures\Scheduler\EchoJob;
use VanillaTests\Fixtures\Scheduler\NonCompliantDriver;
use VanillaTests\Fixtures\Scheduler\NonCompliantJob;
use VanillaTests\Fixtures\Scheduler\NonDroveJob;
use VanillaTests\Fixtures\Scheduler\ParentJob;
use VanillaTests\Fixtures\Scheduler\ThrowableDriver;
use VanillaTests\Fixtures\Scheduler\ThrowableEchoJob;
use VanillaTests\Fixtures\Scheduler\VoidDriver;

/**
 * Class SchedulerTest
 */
final class SchedulerTest extends SchedulerTestCase {

    /**
     * Test get fully-configured scheduler from container.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testGetFullyConfiguredSchedulerFromContainer() {
        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);

        $this->assertTrue(get_class($dummyScheduler) == DummyScheduler::class);
        $this->assertEquals(self::DISPATCH_EVENT, $dummyScheduler->getDispatchEventName());
        $this->assertEquals(self::DISPATCHED_EVENT, $dummyScheduler->getDispatchedEventName());
        // $dummyScheduler will have NoopDriver and LocalDriver
        $this->assertEquals(1, count($dummyScheduler->getDrivers()));
    }

    /**
     * Test adding unknown driver.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddUnknownDriver() {
        $this->expectException(Exception::class);
        $msg = 'The class `VanillaTests\Library\Vanilla\Scheduler\UnknownDriver` cannot be found.';
        $this->expectExceptionMessage($msg);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);

        /** @noinspection PhpUndefinedClassInspection */
        /** @psalm-suppress UndefinedClass */
        $dummyScheduler->addDriver(UnknownDriver::class);
    }

    /**
     * Test adding a non-compliant driver.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddNonCompliantDriver() {
        $this->expectException(Exception::class);
        $msg = 'The class `VanillaTests\Fixtures\Scheduler\NonCompliantDriver` doesn\'t implement DriverInterface.';
        $this->expectExceptionMessage($msg);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);
        $dummyScheduler->addDriver(NonCompliantDriver::class);
    }

    /**
     * Test adding a driver that does not specify any supported job interfaces.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddVoidDriver() {
        $this->expectException(Exception::class);
        $msg = 'The class `VanillaTests\Fixtures\Scheduler\VoidDriver` doesn\'t support any Job implementation.';
        $this->expectExceptionMessage($msg);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);
        $dummyScheduler->addDriver(VoidDriver::class);
    }

    /**
     * Test adding a simple Normal job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddNormalEchoJob() {
        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);


        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));
    }

    /**
     * Test adding a simple job that is aware of types.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddEchoAwareJob() {
        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoAwareJob::class));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));
    }

    /**
     * Test adding an unknown job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddUnknownJob() {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The class `VanillaTests\Library\Vanilla\Scheduler\UnknownJob` cannot be found.');

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);

        /** @noinspection PhpUndefinedClassInspection */
        /** @psalm-suppress UndefinedClass */
        $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(UnknownJob::class));
    }

    /**
     * Test adding a job that does not adhere to the proper interface.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddNonCompliantJob() {
        $this->expectException(Exception::class);
        $msg = 'The job class `VanillaTests\Fixtures\Scheduler\NonCompliantJob` doesn\'t implement JobInterface.';
        $this->expectExceptionMessage($msg);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);
        $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(NonCompliantJob::class));
    }

    /**
     * Test adding a job that does not implement a job type interface.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddNonDroveJob() {
        $this->expectException(Exception::class);
        $msg = "Missing driver to handle the job class `VanillaTests\Fixtures\Scheduler\NonDroveJob`.";
        $this->expectExceptionMessage($msg);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);
        $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(NonDroveJob::class));
    }

    /**
     * Test performing a dispatch with no queued jobs.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchWithNoJob() {
        /** @var $eventManager EventManager */
        $eventManager = $this->getConfiguredContainer()->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            $this->assertTrue(count($trackingSlips) == 0);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching with a single job in the queue.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedWithOneJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertStringContainsString('localDriverId', $trackingSlips[0]->getId());
            $complete = JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching with a single job in the queue that would create a children
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedWithOneJobOneChildren() {
        $container = $this->getConfiguredContainer();

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(ParentJob::class));

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 2);
            $complete = JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
            $this->assertTrue($trackingSlips[1]->getStatus()->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching with a single job in the queue that would create a children
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedWithOneJobOneChildrenUsingDeprecatedMethod() {
        $container = $this->getConfiguredContainer();

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        /** @noinspection PhpDeprecationInspection */
        $dummyScheduler->addJob(ParentJob::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 2);
            $complete = JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
            $this->assertTrue($trackingSlips[1]->getStatus()->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching a single job, resulting in failure.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDispatchedWithOneFailedJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(ThrowableEchoJob::class));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertStringContainsString('localDriverId', $trackingSlips[0]->getId());
            $stackExecutionError = JobExecutionStatus::stackExecutionError();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($stackExecutionError));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($stackExecutionError));
            $this->assertNotNull($trackingSlips[0]->getExtendedStatus()['error']);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test tracking slip is reference of tracking slips.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testTrackingSlipIsReferenceOfTrackingSlips() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) use ($trackingSlip) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue($trackingSlips[0] === $trackingSlip);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test driver not handling errors.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDriverNotHandlingError() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $bool = $dummyScheduler->addDriver(ThrowableDriver::class);
        $this->assertTrue($bool);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(ThrowableEchoJob::class));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertStringContainsString('localDriverId', $trackingSlips[0]->getId());
            $stackExecutionError = JobExecutionStatus::stackExecutionError();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($stackExecutionError));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($stackExecutionError));
            $this->assertNotNull($trackingSlips[0]->getExtendedStatus()['error']);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching 3 Jobs, 2 of them are duplicates
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testDuplicatedJob() {
        $container = $this->getConfiguredContainer();

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);

        $dummyScheduler->addJobDescriptor((new NormalJobDescriptor(EchoJob::class))->setMessage(['a' => 'a']));
        $dummyScheduler->addJobDescriptor((new NormalJobDescriptor(EchoJob::class))->setMessage(['b' => 'b']));
        $dummyScheduler->addJobDescriptor((new NormalJobDescriptor(EchoJob::class))->setMessage(['a' => 'a']));

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertEquals(2, count($trackingSlips));
            $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::complete()));
            $this->assertEquals(1, $trackingSlips[0]->getDuplication());
            $this->assertTrue($trackingSlips[1]->getStatus()->is(JobExecutionStatus::complete()));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }
}
