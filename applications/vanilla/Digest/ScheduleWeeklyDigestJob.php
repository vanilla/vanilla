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

class ScheduleWeeklyDigestJob extends DigestJob
{
    protected const DIGEST_TYPE = "weekly";

    protected const LOG_CONTEXT = [
        Logger::FIELD_TAGS => ["digest", "weeklyDigest"],
        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
    ];

    /**
     * Run the weekly digest job.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        if (!$this->validateBeforeRun()) {
            return JobExecutionStatus::abandoned();
        }
        $scheduleDate = $this->getScheduledDateTime();
        if ($scheduleDate === null) {
            return JobExecutionStatus::abandoned();
        }
        $nextGenerationDate = $scheduleDate->modify($this->digestOffset);

        // Now we know it's time to start generating the digest.
        // First we check if was already generated (or another process is already generating it).
        $existingDigests = $this->getExistingDigest(DigestModel::DIGEST_TYPE_WEEKLY);
        if (count($existingDigests) > 0) {
            $this->logger->info(
                "Weekly digest was not generated for upcoming scheduled date {$scheduleDate->format(
                    DATE_ATOM
                )} because it was already generated at {$existingDigests[0]["dateInserted"]->format(DATE_ATOM)}.",
                self::LOG_CONTEXT + [
                    "nextGenerationDate" => $nextGenerationDate,
                    "nextScheduleDate" => $scheduleDate,
                    "alreadyGeneratedAtDate" => $existingDigests[0]["dateInserted"],
                    Logger::FIELD_EVENT => "weekly_digest_skip",
                ]
            );
            return JobExecutionStatus::abandoned();
        }
        //Clear the existing permissions. Let it recreate based on current Session
        \DiscussionModel::clearCategoryPermissions();
        // Time to actually generate the digest now.
        $this->digestGenerator->prepareWeeklyDigest($scheduleDate);
        $this->logger->info(
            "Weekly digest has been scheduled for generation and will be delivered at {$scheduleDate->format(
                DATE_ATOM
            )}",
            self::LOG_CONTEXT + [
                "scheduleDate" => $nextGenerationDate,
                Logger::FIELD_EVENT => "weekly_digest_scheduled",
            ]
        );
        return JobExecutionStatus::success();
    }

    /**
     * Get the next digest schedule datetime
     *
     * @param int|null $dayOfWeek
     * @return \DateTimeImmutable
     */
    public function getNextScheduledDate(?int $dayOfWeek = null): \DateTimeImmutable
    {
        if ($dayOfWeek) {
            $this->scheduleDayOfWeek = $dayOfWeek;
        }
        [$scheduleHour, $scheduleMinutes] = $this->getScheduledTimePieces();
        $timezone = new \DateTimeZone($this->scheduleTimeZone);
        $utcTimeZone = new \DateTimeZone("UTC");
        $now = CurrentTimeStamp::getDateTime();
        $year = (int) $now->format("Y");
        $week = (int) $now->format("W");
        //Special case for the first week towards the start of a year we need to consider the next year
        if ($week == 1 && $now->format("n") == 12) {
            $year = (int) $year + 1;
            $currentDayOfWeek = $now->format("N");
            if ($currentDayOfWeek > $this->scheduleDayOfWeek) {
                $week = 2;
            }
        }
        $scheduleDateTime = $now
            // Put ourselves in that timezone for construction.
            ->setTimezone($timezone)
            ->setISODate(
                $year, // Year
                $week, // ISO week of the year.
                $this->scheduleDayOfWeek
            )
            ->setTime($scheduleHour, $scheduleMinutes)
            // Make sure we are back in UTC before passing the date anywhere else.
            ->setTimezone($utcTimeZone);

        if ($scheduleDateTime->getTimestamp() <= $now->getTimestamp()) {
            // If we are scheduled in the past we need to move to the future. We should consider daylight savings time changes
            $scheduleDateTime = $scheduleDateTime
                ->setTimezone($timezone)
                ->modify("+1 week")
                ->setTimezone($utcTimeZone);
        }

        //We also need to verify that we do not sent one if we have already sent one with in last 24 hours,
        $mostRecentScheduledDate = $this->digestModel->getRecentWeeklyDigestScheduledDates(1)[0] ?? null;
        if (!empty($mostRecentScheduledDate)) {
            try {
                $mostRecentScheduledDate = new \DateTimeImmutable($mostRecentScheduledDate);
                $interval = $scheduleDateTime->diff($mostRecentScheduledDate, true);
                $hours = $interval->h;
                $hours = $hours + $interval->days * 24;
                //We need at least 24 hours between processing each digest
                if ($hours <= 24) {
                    //We currently don't have  24 hours difference between the last sent digest postpone the next digest generation to a week after
                    $scheduleDateTime = $scheduleDateTime
                        ->setTimezone($timezone)
                        ->modify("+1 week")
                        ->setTimezone($utcTimeZone);
                }
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return $scheduleDateTime;
    }
}
