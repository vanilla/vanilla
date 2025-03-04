<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Digest;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\DigestJob;
use Vanilla\Forum\Digest\DigestModel;
use Vanilla\Forum\Digest\ScheduleDailyDigestJob;
use Vanilla\Forum\Digest\ScheduleMonthlyDigestJob;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;
use Vanilla\Logger;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

class DigestJobTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use DatabaseTestTrait;
    private array $jobs;

    public function setUp(): void
    {
        parent::setUp();
        $this->jobs = [
            DigestModel::DIGEST_TYPE_DAILY => ScheduleDailyDigestJob::class,
            DigestModel::DIGEST_TYPE_WEEKLY => ScheduleWeeklyDigestJob::class,
            DigestModel::DIGEST_TYPE_MONTHLY => ScheduleMonthlyDigestJob::class,
        ];
    }
    public function provideConfigDisabled(): iterable
    {
        yield "Email disabled" => [
            [
                "Garden.Digest.Enabled" => true,
                "Garden.Email.Disabled" => true,
            ],
            " digest was not generated because email is disabled.",
        ];

        yield "Feature disabled" => [
            [
                "Garden.Digest.Enabled" => false,
                "Garden.Email.Disabled" => false,
            ],
            " digest was not generated because digest is disabled.",
        ];
    }

    /**
     * Test configuration conditions that cause a digest not to be scheduled.
     *
     * @param array $config Configuration to run with.
     *
     * @dataProvider provideConfigDisabled
     */
    public function testWhenConfigDisabledAbandonsDigestScheduling(array $config, string $logMessage): void
    {
        foreach ($this->jobs as $jobType => $job) {
            $this->runWithConfig($config, function () use ($job, $jobType, $logMessage) {
                $trackingSlip = $this->getScheduler()->addJob($job);
                $this->assertEquals(JobExecutionStatus::abandoned(), $trackingSlip->getStatus());
                $this->assertLog([
                    Logger::FIELD_EVENT => "{$jobType}_digest_skip",
                ]);
                $this->assertLogMessage(ucfirst($jobType) . $logMessage);
            });
        }
    }

    /**
     * Test that the digest is not generated when the scheduled time is greater than the current time.
     *
     * @return void
     * @throws \Exception
     */
    public function testJobIsAbandonedWhenScheduledTimeIsGreaterThanCurrentTime()
    {
        foreach ($this->jobs as $jobType => $job) {
            $this->runWithConfig(
                [
                    "Garden.Digest.Enabled" => true,
                    "Garden.Email.Disabled" => false,
                    DigestJob::CONF_SCHEDULE_DAY_OF_WEEK => 2, // Tuesday
                ],
                function () use ($job, $jobType) {
                    CurrentTimeStamp::mockTime("2024-12-01 22:00:00");
                    $trackingSlip = $this->getScheduler()->addJob($job);
                    $this->assertEquals(JobExecutionStatus::abandoned(), $trackingSlip->getStatus());
                    $log = $this->assertLog([
                        Logger::FIELD_EVENT => "{$jobType}_digest_skip",
                    ]);
                    $this->assertStringContainsString(
                        ucfirst($jobType) . " digest was not generated because it is too early.",
                        $log["message"]
                    );
                    $this->assertNoRecordsFound("digest", []);
                }
            );
        }
    }

    /**
     * @return void
     */
    public function testScheduledTimePieces()
    {
        // Try  with an invalid schedule time.
        $this->runWithConfig(
            [
                DigestJob::CONF_SCHEDULE_TIME => "13",
            ],
            function () {
                $expected = [DigestJob::DEFAULT_DELIVERY_HOUR, DigestJob::DEFAULT_DELIVERY_MINUTE];
                $job = $this->container()->get(ScheduleDailyDigestJob::class);
                $this->assertEquals($expected, $job->getScheduledTimePieces());
                $this->assertErrorLogMessage("Digest scheduled time is invalid");
            }
        );

        // Try with a valid schedule time.
        $this->runWithConfig(
            [
                DigestJob::CONF_SCHEDULE_TIME => "13:00",
            ],
            function () {
                $expected = [13, 0];
                $job = $this->container()->get(ScheduleDailyDigestJob::class);
                $this->assertEquals($expected, $job->getScheduledTimePieces());
            }
        );
    }
}
