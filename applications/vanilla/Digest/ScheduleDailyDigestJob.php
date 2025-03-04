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

/**
 * Daily Digest job
 */
class ScheduleDailyDigestJob extends DigestJob
{
    protected const LOG_CONTEXT = [
        Logger::FIELD_TAGS => ["digest", "dailyDigest"],
        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
    ];

    protected const DIGEST_TYPE = "daily";

    /**
     * Rotate the System token from the config file.
     *
     * @return JobExecutionStatus
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
        $digestGenerationDateTime = $scheduleDateTime->modify($this->digestOffset);
        // Now we know it's time to start generating the digest.
        // First we check if was already generated (or another process is already generating it).
        if ($this->digestModel->checkIfDigestScheduledForDay($scheduleDateTime)) {
            $this->logger->info(
                "Daily digest was not generated for upcoming scheduled date {$scheduleDateTime->format(
                    DATE_ATOM
                )} because it was already generated.",
                self::LOG_CONTEXT + [
                    "nextScheduleDate" => $digestGenerationDateTime,
                    Logger::FIELD_EVENT => "daily_digest_skip",
                ]
            );
            return JobExecutionStatus::abandoned();
        }
        // We can now generate the digest
        $this->digestGenerator->prepareDailyDigest($scheduleDateTime);
        $this->logger->info(
            "Daily digest has been scheduled for generation and will be delivered at {$scheduleDateTime->format(
                DATE_ATOM
            )}",
            self::LOG_CONTEXT + [
                "scheduleDate" => $digestGenerationDateTime,
                Logger::FIELD_EVENT => "daily_digest_scheduled",
            ]
        );
        return JobExecutionStatus::success();
    }

    /**
     * Get the next digest schedule datetime
     *
     * @return \DateTimeImmutable
     */
    public function getNextScheduledDate(): \DateTimeImmutable
    {
        [$scheduleHour, $scheduleMinute] = $this->getScheduledTimePieces();
        $timezone = new \DateTimeZone($this->scheduleTimeZone);
        $utcTimeZone = new \DateTimeZone("UTC");
        $now = CurrentTimeStamp::getDateTime()->setTimezone($timezone);
        //Get the schedule time in utc format
        $scheduledTime = $now->setTime($scheduleHour, $scheduleMinute)->setTimezone($utcTimeZone);
        $nextSchedule = $scheduledTime->modify("+1 day"); // next schedule date
        $currentUTCTime = $now->setTimezone($utcTimeZone);
        // If current time is greater than the scheduled time, current window has been finished , so return the next schedule
        if ($currentUTCTime->getTimestamp() > $scheduledTime->getTimestamp()) {
            return $nextSchedule;
        }
        // Current time is less than the scheduled time, we need to verify if one has already been scheduled for today
        $isOneScheduledForToday = $this->digestModel->checkIfDigestScheduledForDay($scheduledTime);
        if ($isOneScheduledForToday) {
            return $nextSchedule;
        }
        return $scheduledTime;
    }
}
