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
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Test Daily Digest job
 */
class DailyDigestJobTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    protected DigestModel $digestModel;

    /**
     * @return void
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->digestModel = $this->container()->get(DigestModel::class);
    }

    /**
     * Test the next schedule date for the daily digest job.
     *
     * @return void
     */
    public function testNextScheduleDate(): void
    {
        // Test the time has passed the current schedule time, It should give back the next day schedule
        $mockTime = "2024-12-12 12:00:00";
        CurrentTimeStamp::mockTime("2024-12-12 17:00:00");
        $dailyDigestJob = $this->container()->get(ScheduleDailyDigestJob::class);
        $timezone = new \DateTimeZone("America/New_York");
        $expectedScheduleDateTime = new \DateTimeImmutable($mockTime);
        $expectedScheduleDateTime = $expectedScheduleDateTime->setTimezone($timezone)->modify("+1 day");
        $scheduleHour = sprintf("%02d", ScheduleDailyDigestJob::DEFAULT_DELIVERY_HOUR);
        $scheduleMinute = sprintf("%02d", ScheduleDailyDigestJob::DEFAULT_DELIVERY_MINUTE);
        $expectedScheduleDateTime = $expectedScheduleDateTime
            ->setTime((int) $scheduleHour, (int) $scheduleMinute)
            ->setTimezone(new \DateTimeZone("UTC"));
        $scheduledDateTime = $dailyDigestJob->getNextScheduledDate();
        $this->assertEquals($expectedScheduleDateTime->getTimestamp(), $scheduledDateTime->getTimestamp());

        //Test the time is before the schedule time, It should give back the current day schedule
        CurrentTimeStamp::mockTime("2024-12-12 07:00:00");
        $expectedScheduleDateTime = new \DateTimeImmutable($mockTime);
        $expectedScheduleDateTime = $expectedScheduleDateTime
            ->setTimezone($timezone)
            ->setTime((int) $scheduleHour, (int) $scheduleMinute);
        $expectedScheduleDateTime = $expectedScheduleDateTime->setTimezone(new \DateTimeZone("UTC"));
        $scheduledDateTime = $dailyDigestJob->getNextScheduledDate();
        $this->assertEquals($expectedScheduleDateTime->getTimestamp(), $scheduledDateTime->getTimestamp());

        //Test if a job is scheduled for the current day, it should return the next day schedule
        $digestID = $this->digestModel->insert([
            "dateScheduled" => $expectedScheduleDateTime->format("Y-m-d H:i:s"),
            "digestType" => DigestModel::DIGEST_TYPE_DAILY,
            "totalSubscribers" => 1,
        ]);
        $isOneScheduledForToday = $this->digestModel->checkIfDigestScheduledForDay($expectedScheduleDateTime);
        $this->assertTrue($isOneScheduledForToday);
        $scheduledDateTime = $dailyDigestJob->getNextScheduledDate();
        $expectedScheduleDateTime = $expectedScheduleDateTime->modify("+1 day");
        $this->assertEquals($expectedScheduleDateTime->getTimestamp(), $scheduledDateTime->getTimestamp());
    }

    /**
     *  Test that a single Digest Job is Scheduled for the day
     */
    public function testSingleDigestJobSchedule()
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
            CurrentTimeStamp::mockTime("2024-12-01 10:00:00");
            $trackingSlip = $this->getScheduler()->addJob(ScheduleDailyDigestJob::class);
            $this->assertEquals(JobExecutionStatus::success(), $trackingSlip->getStatus());
            $this->assertRecordsFound("digest", ["digestType" => "daily"], 1);
        });
    }
}
