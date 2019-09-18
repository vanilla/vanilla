<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Scheduler;

/**
 * Class SchedulerTest
 */
final class SchedulerTest extends \PHPUnit\Framework\TestCase {

    const DISPATCH_EVENT = 'dispatchEvent';
    const DISPATCHED_EVENT = 'dispatchedEvent';

    /**
     * Test get fully-configured scheduler from container.
     */
    public function testGetFullyConfiguredSchedulerFromContainer() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $this->assertTrue(get_class($dummyScheduler) == \Vanilla\Scheduler\DummyScheduler::class);
        $this->assertEquals(self::DISPATCH_EVENT, $dummyScheduler->getDispatchEventName());
        $this->assertEquals(self::DISPATCHED_EVENT, $dummyScheduler->getDispatchedEventName());
        $this->assertEquals(1, count($dummyScheduler->getDrivers()));
    }

    /**
     * Test adding unknown driver.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The class `VanillaTests\Fixtures\Scheduler\UnknownDriver` cannot be found.
     */
    public function testAddUnknownDriver() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addDriver(\VanillaTests\Fixtures\Scheduler\UnknownDriver::class);
    }

    /**
     * Test adding a non-compliant driver.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The class `VanillaTests\Fixtures\Scheduler\NonCompliantDriver` doesn't implement DriverInterface.
     */
    public function testAddNonCompliantDriver() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addDriver(\VanillaTests\Fixtures\Scheduler\NonCompliantDriver::class);
    }

    /**
     * Test adding a driver that does not specify any supported job interfaces.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The class `VanillaTests\Fixtures\Scheduler\VoidDriver` doesn't support any Job implementation.
     */
    public function testAddVoidDriver() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addDriver(\VanillaTests\Fixtures\Scheduler\VoidDriver::class);
    }

    /**
     * Test adding a simple job.
     */
    public function testAddEchoJob() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\EchoJob::class);

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(\Vanilla\Scheduler\Job\JobExecutionStatus::received()));
    }

    /**
     * Test adding a simple job that is aware of types.
     */
    public function testAddEchoAwareJob() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\EchoAwareJob::class);

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(\Vanilla\Scheduler\Job\JobExecutionStatus::received()));
    }

    /**
     * Test adding an unknown job.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The class `VanillaTests\Fixtures\Scheduler\UnknownJob` cannot be found.
     */
    public function testAddUnknownJob() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\UnknownJob::class);
    }

    /**
     * Test adding a job that does not adhere to the proper interface.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage The job class `VanillaTests\Fixtures\Scheduler\NonCompliantJob` doesn't implement JobInterface.
     */
    public function testAddNonCompliantJob() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\NonCompliantJob::class);
    }

    /**
     * Test adding a job that does not implement a job type interface.
     *
     * @expectedException \Exception
     * @expectedExceptionMessage DummyScheduler couldn't find an appropriate driver to handle the job class `VanillaTests\Fixtures\Scheduler\NonDroveJob`.
     */
    public function testAddNonDroveJob() {
        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $this->getNewContainer()->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\NonDroveJob::class);
    }

    /**
     * Test performing a dispatch with no queued jobs.
     */
    public function testDispatchWithNoJob() {
        /** @var $eventManager \Garden\EventManager */
        $eventManager = $this->getNewContainer()->get(\Garden\EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            $this->assertTrue(count($trackingSlips) == 0);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching with a single job in the queue.
     */
    public function testDispatchedWithOneJob() {
        /** @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\EchoJob::class);
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(\Vanilla\Scheduler\Job\JobExecutionStatus::received()));

        /** @var $eventManager \Garden\EventManager */
        $eventManager = $container->get(\Garden\EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips \Vanilla\Scheduler\TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertContains('localDriverId', $trackingSlips[0]->getId());
            $complete = \Vanilla\Scheduler\Job\JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching a single job, resulting in failure.
     */
    public function testDispatchedWithOneFailedJob() {
        /** @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\ThrowableEchoJob::class);
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(\Vanilla\Scheduler\Job\JobExecutionStatus::received()));

        /** @var $eventManager \Garden\EventManager */
        $eventManager = $container->get(\Garden\EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips \Vanilla\Scheduler\TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertContains('localDriverId', $trackingSlips[0]->getId());
            $stackExecutionError = \Vanilla\Scheduler\Job\JobExecutionStatus::stackExecutionError();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($stackExecutionError));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($stackExecutionError));
            $this->assertNotNull($trackingSlips[0]->getExtendedStatus()['error']);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test tracking slip is reference of tracking slips.
     */
    public function testTrackingSlipIsReferenceOfTrackingSlips() {
        /** @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\EchoJob::class);

        /** @var $eventManager \Garden\EventManager */
        $eventManager = $container->get(\Garden\EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) use ($trackingSlip) {
            /** @var $trackingSlips \Vanilla\Scheduler\TrackingSlip[] */
            $this->assertTrue($trackingSlips[0] === $trackingSlip);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test driver not handling errors.
     */
    public function testDriverNotHandlingError() {
        /** @var $container \Garden\Container\Container */
        $container = $this->getNewContainer();

        /* @var $dummyScheduler \Vanilla\Scheduler\SchedulerInterface */
        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);

        $bool = $dummyScheduler->addDriver(\VanillaTests\Fixtures\Scheduler\ThrowableDriver::class);
        $this->assertTrue($bool);

        $trackingSlip = $dummyScheduler->addJob(\VanillaTests\Fixtures\Scheduler\ThrowableEchoJob::class);
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(\Vanilla\Scheduler\Job\JobExecutionStatus::received()));

        /** @var $eventManager \Garden\EventManager */
        $eventManager = $container->get(\Garden\EventManager::class);

        /** @var $eventManager \Garden\EventManager */
        $eventManager = $container->get(\Garden\EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips \Vanilla\Scheduler\TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertContains('localDriverId', $trackingSlips[0]->getId());
            $stackExecutionError = \Vanilla\Scheduler\Job\JobExecutionStatus::stackExecutionError();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($stackExecutionError));
            $this->assertTrue($trackingSlips[0]->getExtendedStatus()['status']->is($stackExecutionError));
            $this->assertNotNull($trackingSlips[0]->getExtendedStatus()['error']);
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Get a new, cleanly-configured container.
     *
     * @return \Garden\Container\Container
     */
    private function getNewContainer() {
        $container = new \Garden\Container\Container();
        $container
            ->setInstance(\Psr\Container\ContainerInterface::class, $container)
            //
            ->rule(\Psr\Log\LoggerInterface::class)
            ->setClass(\Vanilla\Logger::class)
            ->setShared(true)
            // Not really needed
            ->rule(\Garden\EventManager::class)
            ->setShared(true)
            ->rule(\Vanilla\Scheduler\SchedulerInterface::class)
            ->setClass(\Vanilla\Scheduler\DummyScheduler::class)
            ->setShared(true)
        ;

        $dummyScheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);
        $this->assertTrue(get_class($dummyScheduler) == \Vanilla\Scheduler\DummyScheduler::class);

        $bool = $dummyScheduler->addDriver(\Vanilla\Scheduler\Driver\LocalDriver::class);
        $this->assertTrue($bool);

        $bool = $dummyScheduler->setDispatchEventName(self::DISPATCH_EVENT);
        $this->assertTrue($bool);

        $bool = $dummyScheduler->setDispatchedEventName(self::DISPATCHED_EVENT);
        $this->assertTrue($bool);

        return $container;
    }
}
