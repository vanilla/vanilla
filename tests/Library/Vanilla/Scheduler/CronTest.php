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
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        \Gdn::config()->saveToConfig([CronModel::CONF_OFFSET_SECONDS => 0]);
        \Gdn::cache()->flush();
    }

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

        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
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

        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());
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

        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());
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

        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
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
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());
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
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());

        // Move forwards 15 minutes and try again.
        CurrentTimeStamp::mockTime($lastRunTime->modify("+15 minutes"));
        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
    }

    /**
     * Test that crons run at the right time with a configured offset.
     *
     * @return void
     */
    public function testCronSchedulingWithOffset()
    {
        // 3h30m offset.
        \Gdn::config()->saveToConfig([CronModel::CONF_OFFSET_SECONDS => 60 * 60 * 3 + 60 * 30]);
        // Job scheduled every 6 hours
        $job = new CronJobDescriptor(EchoJob::class, "0 */6 * * *");
        $lastRunTime = $this->mockLastRunTime("2022-12-01 00:01:00");
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());
        $deferredScheduler->addJobDescriptor($job);
        // A bunch of time has passed, but we due to the offset our next expected run time is 022-12-01 03:30:00
        CurrentTimeStamp::mockTime("2022-12-01 03:29:00");
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());

        // Now it's time to run.
        CurrentTimeStamp::mockTime("2022-12-01 03:30:00");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::complete());

        // Next run time should be 09:30
        // Couple attempts should abandon.
        CurrentTimeStamp::mockTime("2022-12-01 06:29:00");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());
        CurrentTimeStamp::mockTime("2022-12-01 07:29:00");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());

        // Now it should run again.
        CurrentTimeStamp::mockTime("2022-12-01 09:30:00");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
    }

    /**
     * Test that crons should run at the correct time with second based offsets.
     *
     * @return void
     */
    public function testCronSchedulingWithSecondsOffset()
    {
        // 3h30m offset.
        \Gdn::config()->saveToConfig([CronModel::CONF_OFFSET_SECONDS => 43]);
        // Job scheduled every 15 minutes
        $job = new CronJobDescriptor(EchoJob::class, "*/15 * * * *");
        $lastRunTime = $this->mockLastRunTime("2022-12-01 00:02:00");
        $deferredScheduler = $this->getDeferredScheduler();
        $deferredScheduler->setExecutionType(JobExecutionType::cron());
        $deferredScheduler->addJobDescriptor($job);
        // Next expected runtime should be 00:15:43
        CurrentTimeStamp::mockTime("2022-12-01 00:15:40");
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());

        // Now it's time to run.
        CurrentTimeStamp::mockTime("2022-12-01 00:15:50");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::complete());

        // Next run time should be 00:30:43
        // Couple attempts should abandon.
        CurrentTimeStamp::mockTime("2022-12-01 00:30:39");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());
        CurrentTimeStamp::mockTime("2022-12-01 00:30:40");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::abandoned());

        // Now it should run again.
        CurrentTimeStamp::mockTime("2022-12-01 00:30:43");
        $deferredScheduler->addJobDescriptor($job);
        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
    }

    /**
     * Assert that we dispatch a job
     *
     * @param JobExecutionStatus $expectedStatus
     */
    private function assertDispatchesToStatus(JobExecutionStatus $expectedStatus)
    {
        $deferredScheduler = $this->getDeferredScheduler();
        $trackingSlips = $deferredScheduler->dispatchJobs();
        $this->assertCount(1, $trackingSlips);
        // Our tracking slip should be abandoned.
        $this->assertEquals((string) $expectedStatus, (string) $trackingSlips[0]->getStatus());
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
        $cronModel->trackRun(true);
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

        $this->assertDispatchesToStatus(JobExecutionStatus::complete());
    }
}
