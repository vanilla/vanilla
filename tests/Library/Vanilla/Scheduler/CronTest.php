<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Gdn_Cache;
use Vanilla\CurrentTimeStamp;
use Vanilla\Scheduler\CronModel;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\JobExecutionType;
use VanillaTests\Fixtures\OfflineNullCache;
use VanillaTests\Fixtures\Scheduler\EchoJob;
use VanillaTests\Fixtures\Scheduler\ParentJob;
use VanillaTests\Fixtures\Scheduler\ThrowableEchoJob;

/**
 * Class CronTest
 */
class CronTest extends SchedulerTestCase
{
    /**
     * Test adding a simple Cron job.
     *
     * @throws ContainerException On error.
     * @throws NotFoundException On error.
     */
    public function testAddCronEchoJob()
    {
        $deferredScheduler = $this->getDeferredScheduler();

        $trackingSlip = $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "* * * * *"));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));
    }

    /**
     * Test adding a simple Normal job with CronExecutionType
     */
    public function testAddNormalEchoJobWithCronExecutionType()
    {
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $deferredScheduler->addJobDescriptor(new NormalJobDescriptor(EchoJob::class));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        $trackingSlips = $deferredScheduler->dispatchJobs();

        $this->assertCount(1, $trackingSlips);
        $this->assertStringContainsString("localDriverId", $trackingSlips[0]->getID());
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::complete()));
    }

    /**
     * Test dispatching in *normal* mode with a cron job that creates a normal child job.
     * Cron jobs aren't allowed in normal mode so only one slip should be returned with its status set to abandoned.
     *
     * @return void
     * @throws \Exception
     */
    public function testAddCronEchoJobWithNormalExecutionType()
    {
        $deferredScheduler = $this->getDeferredScheduler();

        $deferredScheduler->addJobDescriptor(new CronJobDescriptor(ParentJob::class, "* * * * *"));

        $trackingSlips = $deferredScheduler->dispatchJobs();

        $this->assertCount(1, $trackingSlips);
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));
    }

    /**
     * Test dispatching a single job, resulting in failure.
     */
    public function testSkippedCronJob()
    {
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $deferredScheduler->addJobDescriptor(new CronJobDescriptor(ThrowableEchoJob::class, "#"));
        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertTrue(count($trackingSlips) == 1);
        $this->assertStringContainsString("localDriverId", $trackingSlips[0]->getID());
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));
    }

    /**
     * Test adding a simple Cron job with CronExecutionType
     */
    public function testAddCronEchoJobWithCronExecutionType()
    {
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());

        $trackingSlip = $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "* * * * *"));

        $this->assertNotNull($trackingSlip);
        $this->assertTrue($trackingSlip->getStatus()->is(JobExecutionStatus::received()));

        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertTrue(count($trackingSlips) == 1);
        $this->assertStringContainsString("localDriverId", $trackingSlips[0]->getID());
        $complete = JobExecutionStatus::complete();
        $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
        $this->assertTrue($trackingSlips[0]->getExtendedStatus()["status"]->is($complete));
    }

    /**
     * Test that multiple crons can't be triggered at the same time.
     */
    public function testCronLocking()
    {
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());
        $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "* * * * *"));

        $lock = $this->container()
            ->get(CronModel::class)
            ->createLock();
        $this->assertTrue($lock->acquire());

        // This will not run and the jobs will be abandoned.
        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertCount(1, $trackingSlips);
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));
    }

    /**
     * Test that we properly parse cron expressions and execute crons at the correct time.
     *
     * @return void
     */
    public function testCronScheduling()
    {
        $lastRunTime = $this->mockLastRunTime("2022-12-01 00:01:00");
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());
        // Job scheduled every 15 minutes.
        $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "*/15 * * * *"));
        // Only 14 minutes have passed. Job won't run.
        $lastRunTime = CurrentTimeStamp::mockTime($lastRunTime->modify("+13 minutes"));
        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertCount(1, $trackingSlips);
        // Our tracking slip should be abandoned.
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::abandoned()));

        // Move forwards 15 minutes and try again.
        CurrentTimeStamp::mockTime($lastRunTime->modify("+15 minutes"));
        $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "*/15 * * * *"));
        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertCount(1, $trackingSlips);
        $this->assertTrue($trackingSlips[0]->getStatus()->is(JobExecutionStatus::complete()));
    }

    /**
     * Mock out the last run time.
     *
     * @param $toMock
     * @return \DateTimeImmutable
     */
    private function mockLastRunTime($toMock): \DateTimeImmutable
    {
        $time = CurrentTimeStamp::mockTime($toMock);
        $cronModel = $this->container()->get(CronModel::class);
        // We've tracked our last run time.
        $cronModel->trackRun();
        CurrentTimeStamp::clearMockTime();
        return $time;
    }

    /**
     * Test adding a simple Cron job with CronExecutionType with offline cache
     */
    public function testAddCronEchoJobWithCronExecutionTypeOfflineCache()
    {
        $container = $this->container();

        $offlineCache = $container->get(OfflineNullCache::class);
        $container->setInstance(Gdn_Cache::class, $offlineCache);

        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());

        $deferredScheduler->addJobDescriptor(new CronJobDescriptor(EchoJob::class, "* * * * *"));

        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertTrue(count($trackingSlips) == 1);
        $complete = JobExecutionStatus::complete();
        $this->assertTrue($trackingSlips[0]->getStatus()->is($complete));
    }
}
