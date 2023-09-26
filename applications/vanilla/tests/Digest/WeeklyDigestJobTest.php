<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Forum\Digest;

use Vanilla\CurrentTimeStamp;
use Vanilla\Forum\Digest\EmailDigestGenerator;
use Vanilla\Forum\Digest\UserDigestModel;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;
use Vanilla\Logger;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use VanillaTests\DatabaseTestTrait;
use VanillaTests\SchedulerTestTrait;
use VanillaTests\SiteTestCase;

/**
 * Tests for scheduling of the weekly digest job.
 */
class WeeklyDigestJobTest extends SiteTestCase
{
    use SchedulerTestTrait;
    use DatabaseTestTrait;

    private UserDigestModel $userDigestModel;
    private EmailDigestGenerator $emailDigestGenerator;
    private \CategoryModel $categoryModel;

    public function setUp(): void
    {
        parent::setUp();
        $this->userDigestModel = \Gdn::getContainer()->get(UserDigestModel::class);
        $this->categoryModel = \Gdn::getContainer()->get(\CategoryModel::class);
        $this->emailDigestGenerator = \Gdn::getContainer()->get(EmailDigestGenerator::class);
        $config = [
            "Garden.Email.Disabled" => false,
            "Feature.Digest.Enabled" => true,
            "Garden.Digest.Enabled" => true,
        ];
        \Gdn::config()->saveToConfig($config);
        $this->resetTable("digest");
        $this->resetTable("digestContent");
        $this->resetTable("userDigest");
    }

    public function provideConfigDisabled(): iterable
    {
        yield "Email disabled" => [
            [
                "Garden.Digest.Enabled" => true,
                "Feature.Digest.Enabled" => true,
                "Garden.Email.Disabled" => true,
            ],
        ];

        yield "Digest config disabled" => [
            [
                "Garden.Digest.Enabled" => true,
                "Feature.Digest.Enabled" => false,
                "Garden.Email.Disabled" => false,
            ],
        ];

        yield "Feature disabled" => [
            [
                "Garden.Digest.Enabled" => false,
                "Feature.Digest.Enabled" => true,
                "Garden.Email.Disabled" => false,
            ],
        ];
    }

    /**
     * Test configuration conditions that cause a digest not to be scheduled.
     *
     * @param array $config Configuration to run with.
     *
     * @dataProvider provideConfigDisabled
     */
    public function testConfigDisabledAbandonsDigestScheduling(array $config): void
    {
        $this->runWithConfig($config, function () {
            $trackingSlip = $this->getScheduler()->addJob(ScheduleWeeklyDigestJob::class);
            $this->assertEquals(JobExecutionStatus::abandoned(), $trackingSlip->getStatus());
            $this->assertLog([
                Logger::FIELD_EVENT => "weekly_digest_skip",
            ]);
            $this->assertNoRecordsFound("digest", []);
        });
    }

    /**
     * Test scheduling of the weekly digest emails.
     *
     * Notably this job runs at randomized 15 cron intervals
     * and we want to schedule emails for a precise time. This necessitates some our own time checking mechanism.
     */
    public function testDigestScheduling(): void
    {
        $this->resetTable("digest");
        $this->runWithConfig(
            [
                ScheduleWeeklyDigestJob::CONF_DIGEST_GENERATION_OFFSET => "-1 hours",
                ScheduleWeeklyDigestJob::CONF_SCHEDULE_DAY_OF_WEEK => 3, // Wednesday,
                ScheduleWeeklyDigestJob::CONF_SCHEDULE_TIME => "13:00",
                ScheduleWeeklyDigestJob::CONF_SCHEDULE_TIME_ZONE => "America/New_York",
            ],
            function () {
                CurrentTimeStamp::mockTime("Jan 1 2022"); // This is a saturday.
                $this->assertDigestNotScheduled("it is too early");
                $this->assertRecordsFound("digest", [], 0);

                $oneMinuteBeforeStart = new \DateTimeImmutable(
                    "2022-01-05 11:59",
                    new \DateTimeZone("America/New_York")
                );

                // Digest generation will start 1 hour before the scheduled time.
                CurrentTimeStamp::mockTime($oneMinuteBeforeStart);
                $this->assertDigestNotScheduled("it is too early");
                $this->assertRecordsFound("digest", [], 0);

                $dateStart = $oneMinuteBeforeStart->modify("+1 minute");

                // If we're on the wrong the day of the week it doesn't work.
                CurrentTimeStamp::mockTime($dateStart->modify("+1 day"));
                $this->assertDigestNotScheduled("it is too early");
                $this->assertRecordsFound("digest", [], 0);
                CurrentTimeStamp::mockTime($dateStart->modify("-1 day"));
                $this->assertDigestNotScheduled("it is too early");
                $this->assertRecordsFound("digest", [], 0);

                // Now we can schedule ourself.
                CurrentTimeStamp::mockTime($dateStart);
                $this->assertDigestScheduled();
                $this->assertRecordsFound("digest", [], 1);

                // At the hour we are now waiting for the next week.
                CurrentTimeStamp::mockTime($oneMinuteBeforeStart->modify("+1 hour")->modify("+1 minute"));
                $this->assertDigestNotScheduled("it is too early");
                $this->assertRecordsFound("digest", [], 1);
            }
        );
    }

    /**
     * Assert that a weekly digest was not scheduled.
     *
     * @param string $expectedLogMessage A log message to expect with the skipped generation.
     */
    private function assertDigestNotScheduled(string $expectedLogMessage = "")
    {
        $this->getTestLogger()->clear();
        $job = $this->container()->get(ScheduleWeeklyDigestJob::class);
        $result = $job->run();
        $this->assertEquals(JobExecutionStatus::abandoned()->getStatus(), $result->getStatus());
        $this->assertLog([
            Logger::FIELD_EVENT => "weekly_digest_skip",
        ]);
        if (!empty($expectedLogMessage)) {
            $this->assertLogMessage($expectedLogMessage);
        }
    }

    /**
     * Assert that a weekly digest was scheduled.
     */
    private function assertDigestScheduled(): void
    {
        $this->getTestLogger()->clear();
        $job = $this->container()->get(ScheduleWeeklyDigestJob::class);
        $result = $job->run();
        $this->assertEquals(JobExecutionStatus::success()->getStatus(), $result->getStatus());
        $this->assertLog([
            Logger::FIELD_EVENT => "weekly_digest_scheduled",
        ]);
        $this->assertLogMessage("Weekly digest has been scheduled");
    }
}
