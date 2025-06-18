<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Digest;

use Vanilla\CurrentTimeStamp;
use Vanilla\Logger;
use Vanilla\Scheduler\Job\JobExecutionStatus;

// FIXME: this is not using the value of Garden.Digest.Monthly.DayOfWeek: it's using the day of week configured for "weekly" digest.
/**
 * Schedule the monthly digest job
 */
class ScheduleMonthlyDigestJob extends DigestJob
{
    protected const DIGEST_TYPE = "monthly";

    protected const LOG_CONTEXT = [
        Logger::FIELD_TAGS => ["digest", "monthlyDigest"],
        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
    ];

    /**
     * @inheritdoc
     */
    public function run(): JobExecutionStatus
    {
        if (!$this->validateBeforeRun()) {
            return JobExecutionStatus::abandoned();
        }
        $scheduleDateTime = $this->getScheduledDateTime();
        if ($scheduleDateTime === null) {
            return JobExecutionStatus::abandoned();
        }
        $digestGenerationTime = $scheduleDateTime->modify($this->digestOffset);
        $existingDigests = $this->getExistingDigest(DigestModel::DIGEST_TYPE_MONTHLY);

        if (count($existingDigests) > 0) {
            $this->logger->info(
                "Monthly digest was not generated for upcoming scheduled date {$scheduleDateTime->format(
                    DATE_ATOM
                )} because it was already generated at {$existingDigests[0]["dateInserted"]->format(DATE_ATOM)}.",
                self::LOG_CONTEXT + [
                    "nextGenerationDate" => $digestGenerationTime,
                    "nextScheduleDate" => $scheduleDateTime,
                    "alreadyGeneratedAtDate" => $existingDigests[0]["dateInserted"],
                    Logger::FIELD_EVENT => "monthly_digest_skip",
                ]
            );
            return JobExecutionStatus::abandoned();
        }
        //Clear the existing permissions. Let it recreate based on current Session
        \DiscussionModel::clearCategoryPermissions();
        // Time to actually generate the digest now.
        $this->digestGenerator->prepareMonthlyDigest($scheduleDateTime);
        $this->logger->info(
            "Weekly digest has been scheduled for generation and will be delivered at {$scheduleDateTime->format(
                DATE_ATOM
            )}",
            self::LOG_CONTEXT + [
                "scheduleDate" => $digestGenerationTime,
                Logger::FIELD_EVENT => "monthly_digest_scheduled",
            ]
        );
        return JobExecutionStatus::success();
    }

    /**
     * Get the next schedule datetime for digest generation
     *
     * @return \DateTimeImmutable
     */
    public function getNextScheduledDate(): \DateTimeImmutable
    {
        [$scheduleHour, $scheduleMinutes] = $this->getScheduledTimePieces();
        $scheduleWeekFrame = $this->config->get(
            DigestModel::MONTHLY_DIGEST_WEEK_KEY,
            DigestModel::DEFAULT_MONTHLY_DIGEST_WEEK
        );
        $timezone = new \DateTimeZone($this->scheduleTimeZone);
        $utcTimeZone = new \DateTimeZone("UTC");
        $now = CurrentTimeStamp::getDateTime();
        $scheduleDay = $this->getScheduleDigestDay();
        $scheduleWeek = $scheduleWeekFrame === DigestModel::DEFAULT_MONTHLY_DIGEST_WEEK ? "first" : "last";
        $modifier = "$scheduleWeek $scheduleDay of this month";
        $scheduleTime = $now
            ->setTimezone($timezone)
            ->modify($modifier)
            ->setTime($scheduleHour, $scheduleMinutes)
            ->setTimezone($utcTimeZone);
        // If the schedule time is in the past, we need to schedule it for the next month
        if ($now->getTimestamp() > $scheduleTime->getTimestamp()) {
            $utcHour = $scheduleTime->format("H");
            $utcMinutes = $scheduleTime->format("i");
            return $scheduleTime->modify("$scheduleWeek $scheduleDay of next month")->setTime($utcHour, $utcMinutes);
        }
        // We need to see there was scheduled with in the last 24 hours
        $mostRecentDigestScheduledDate =
            $this->digestModel->getRecentDigestScheduleDatesByType(DigestModel::DIGEST_TYPE_MONTHLY, 1)[0] ?? null;
        if (!empty($mostRecentDigestScheduledDate)) {
            try {
                $mostRecentDigestScheduledDate = new \DateTimeImmutable($mostRecentDigestScheduledDate);
                $interval = $scheduleTime->diff($mostRecentDigestScheduledDate, true);
                $hours = $interval->h;
                $hours = $hours + $interval->days * 24;
                if ($hours <= 24) {
                    // We need at least 24 hours between processing each digest
                    $utcHour = $scheduleTime->format("H");
                    $utcMinutes = $scheduleTime->format("i");
                    return $scheduleTime
                        ->modify("$scheduleWeek $scheduleDay of next month")
                        ->setTime($utcHour, $utcMinutes);
                }
            } catch (\Exception $e) {
                // Do nothing
            }
        }
        return $scheduleTime;
    }

    /**
     * Get the scheduled string day
     *
     * @return string
     */
    private function getScheduleDigestDay(): string
    {
        return match ((int) $this->scheduleDayOfWeek) {
            2 => "tuesday",
            3 => "wednesday",
            4 => "thursday",
            5 => "friday",
            6 => "saturday",
            7 => "sunday",
            default => "monday",
        };
    }
}
