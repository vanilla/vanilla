<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Digest;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\ScheduleDailyDigestJob;
use Vanilla\Forum\Digest\ScheduleMonthlyDigestJob;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test Daily Digest job
 */
class MonthlyDigestJobTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    protected DigestModel $digestModel;
    protected ScheduleMonthlyDigestJob $scheduleMonthlyDigestJob;

    /**
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->digestModel = $this->container()->get(DigestModel::class);
        $this->scheduleMonthlyDigestJob = $this->container()->get(ScheduleMonthlyDigestJob::class);
    }

    /**
     * Test the next schedule date for the daily digest job.
     *
     * @return void
     */
    public function testNextScheduleDate(): void
    {
        $config = [
            "Garden.Digest.Enabled" => true,
            "Garden.Digest.ScheduleTime" => "08:00",
            "Garden.Digest.ScheduleTimeZone" => "America/New_York",
            "Garden.Digest.DayOfWeek" => 3, // Wednesday
            "Garden.Digest.ScheduleOffset" => "-3 hours",
            DigestModel::MONTHLY_DIGEST_WEEK_KEY => "first",
        ];
        CurrentTimeStamp::mockTime("2024-12-04 13:05:00");

        $this->runWithConfig($config, function () {
            $this->scheduleMonthlyDigestJob->initializeConfig();
            //if the current time is past the schedule time we should get the next month schedule
            $nextScheduleDate = $this->scheduleMonthlyDigestJob->getNextScheduledDate();
            $this->assertEquals("2025-01-01 13:00:00", $nextScheduleDate->format("Y-m-d H:i:s"));
            $this->assertEquals("Wednesday", $nextScheduleDate->format("l"));

            // If there is not 24 hours in between previous schedule and current schedule set the schedule to the next month
            $this->digestModel->insert([
                "digestType" => "monthly",
                "dateScheduled" => "2024-12-04 06:00:00",
            ]);
            CurrentTimeStamp::mockTime("2024-12-04 12:00:00");
            $nextScheduleDate = $this->scheduleMonthlyDigestJob->getNextScheduledDate();
            $this->assertEquals("2025-01-01 13:00:00", $nextScheduleDate->format("Y-m-d H:i:s"));
        });

        $config[DigestModel::MONTHLY_DIGEST_WEEK_KEY] = "last";
        $this->runWithConfig($config, function () {
            $this->scheduleMonthlyDigestJob->initializeConfig();
            // WE should get the last scheduled week day of the month
            $nextScheduleDate = $this->scheduleMonthlyDigestJob->getNextScheduledDate();
            $this->assertEquals("2024-12-25 13:00:00", $nextScheduleDate->format("Y-m-d H:i:s"));
            $this->assertEquals("Wednesday", $nextScheduleDate->format("l"));
        });
    }

    /**
     *  Test monthly digest job schedule
     */
    public function testMonthlyDigestJobSchedule()
    {
        $this->resetTable("digest");
        $config = [
            "Garden.Digest.Enabled" => true,
            "Garden.Digest.ScheduleTime" => "08:00",
            "Garden.Digest.ScheduleTimeZone" => "America/New_York",
            "Garden.Digest.DayOfWeek" => 1,
            "Garden.Digest.ScheduleOffset" => "-3 hours",
        ];
        $this->runWithConfig($config, function () {
            CurrentTimeStamp::mockTime("2024-12-02 10:00:00");
            $trackingSlip = $this->getScheduler()->addJob(ScheduleMonthlyDigestJob::class);
            $this->assertEquals(JobExecutionStatus::success(), $trackingSlip->getStatus());
            $this->assertRecordsFound("digest", ["digestType" => "monthly"], 1);
        });
    }
}
