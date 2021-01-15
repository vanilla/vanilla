<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Garden\EventManager;
use Gdn_Cache;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobExecutionType;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Scheduler\TrackingSlip;
use VanillaTests\Fixtures\OfflineNullCache;
use VanillaTests\Fixtures\Scheduler\EchoJob;
use VanillaTests\Fixtures\Scheduler\ThrowableEchoJob;

/**
 * Class CronTest
 */
class CronTest extends SchedulerTestCase {

    /**
     * Test adding a simple Cron job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddCronEchoJob() {
        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $this->getConfiguredContainer()->get(SchedulerInterface::class);

        $trackingSlip = $dummyScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, '* * * * *'));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));
    }

    /**
     * Test adding a simple Normal job with CronExecutionType
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddNormalEchoJobWithCronExecutionType() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $dummyScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $dummyScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertStringContainsString('localDriverId', $trackingSlips[0]->getId());
            $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test dispatching a single job, resulting in failure.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testSkippedCronJob() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $dummyScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $dummyScheduler->addJobDescriptor(new CronJobDescriptor(ThrowableEchoJob::class, "#"));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $this->assertStringContainsString('localDriverId', $trackingSlips[0]->getId());
            $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }

    /**
     * Test adding a simple Cron job with CronExecutionType
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddCronEchoJobWithCronExecutionType() {
        $container = $this->getConfiguredContainer();

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $dummyScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $dummyScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, '* * * * *'));

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
     * Test adding a simple Cron job with CronExecutionType with offline cache
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddCronEchoJobWithCronExecutionTypeOfflineCache() {
        $container = $this->getConfiguredContainer();

        $offlineCache = $container->get(OfflineNullCache::class);
        $container->setInstance(Gdn_Cache::class, $offlineCache);

        /** @var $eventManager EventManager */
        $eventManager = $container->get(EventManager::class);

        /* @var $dummyScheduler SchedulerInterface */
        $dummyScheduler = $container->get(SchedulerInterface::class);
        $dummyScheduler->setExecutionType(JobExecutionType::cron());

        $dummyScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, '* * * * *'));

        $eventManager->bind(self::DISPATCHED_EVENT, function ($trackingSlips) {
            /** @var $trackingSlips TrackingSlip[] */
            $this->assertTrue(count($trackingSlips) == 1);
            $complete = JobExecutionStatus::complete();
            $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
        });

        $eventManager->fire(self::DISPATCH_EVENT);
    }
}
