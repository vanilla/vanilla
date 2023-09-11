<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Digest;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\FeatureFlagHelper;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

class ScheduleWeeklyDigestJob extends LocalApiJob implements LoggerAwareInterface
{
    /** @var string Config key representing how long before the digest is sent that it should be generated. */
    public const CONF_DIGEST_GENERATION_OFFSET = "Garden.Digest.GenerationOffset";
    /** @var string Config holding an ISO day of the week. */
    public const CONF_SCHEDULE_DAY_OF_WEEK = "Garden.Digest.DayOfWeek";

    /** @var string Config for a 24 hour time of day to schedule the digest. */
    public const CONF_SCHEDULE_TIME = "Garden.Digest.ScheduleTime";
    public const CONF_SCHEDULE_TIME_ZONE = "Garden.Digest.ScheduleTimeZone";

    private const LOG_CONTEXT = [
        Logger::FIELD_TAGS => ["digest", "weeklyDigest"],
        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
    ];

    use LoggerAwareTrait;

    private EmailDigestGenerator $digestGenerator;
    private ConfigurationInterface $config;
    private DigestModel $digestModel;

    /**
     * @param EmailDigestGenerator $digestGenerator
     * @param ConfigurationInterface $config
     * @param DigestModel $digestModel
     */
    public function __construct(
        EmailDigestGenerator $digestGenerator,
        ConfigurationInterface $config,
        DigestModel $digestModel
    ) {
        $this->digestGenerator = $digestGenerator;
        $this->config = $config;
        $this->digestModel = $digestModel;
    }

    /**
     * No message needed.
     *
     * @param array $message
     */
    public function setMessage(array $message)
    {
        // No message needed.
    }

    /**
     * Rotate the System token from the config file.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        $isDigestEnabled =
            FeatureFlagHelper::featureEnabled(DigestEmail::FEATURE_FLAG) &&
            $this->config->get("Garden.Digest.Enabled", false);
        if (!$isDigestEnabled) {
            $this->logger->debug(
                "Weekly digest was not generated because digest is disabled.",
                self::LOG_CONTEXT + [
                    Logger::FIELD_EVENT => "weekly_digest_skip",
                ]
            );
            return JobExecutionStatus::abandoned();
        }

        if ($this->config->get(\Gdn_Email::CONF_DISABLED, false)) {
            $this->logger->debug(
                "Weekly digest was not generated because email is disabled.",
                self::LOG_CONTEXT + [
                    Logger::FIELD_EVENT => "weekly_digest_skip",
                ]
            );
            return JobExecutionStatus::abandoned();
        }

        $scheduleDate = $this->getNextScheduledDate();

        $offset = $this->config->get(self::CONF_DIGEST_GENERATION_OFFSET, "-3 hours");
        $nextGenerationDate = $scheduleDate->modify($offset);
        $currentTime = CurrentTimeStamp::getDateTime();

        if ($currentTime->getTimestamp() < $nextGenerationDate->getTimestamp()) {
            $this->logger->info(
                "Weekly digest was not generated because it is too early. The next weekly digest will be generated at {$nextGenerationDate->format(
                    DATE_ATOM
                )} for delivery at {$scheduleDate->format(DATE_ATOM)}",
                self::LOG_CONTEXT + [
                    "nextGenerationDate" => $nextGenerationDate,
                    "nextScheduleDate" => $scheduleDate,
                    Logger::FIELD_EVENT => "weekly_digest_skip",
                ]
            );
            // It's not time to generate the digest yet.
            return JobExecutionStatus::abandoned();
        }

        // Now we know it's time to start generating the digest.
        // First we check if was already generated (or another process is already generating it).
        $existingDigests = $this->digestModel->select([
            "digestType" => DigestModel::DIGEST_TYPE_WEEKLY,
            "dateScheduled >" => $currentTime,
        ]);

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
        // Alrighty time to actually generate the digest now.
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
     * Get a cron expression to run this job.
     *
     * @return string
     */
    public static function getCronExpression(): string
    {
        // Hourly.
        // We don't actually send digests every hour.
        // We just check if we should start generation every hour.
        // The check is cheap and this allows us to work around the random cron offset.
        return "0 * * * *";
    }

    public function getNextScheduledDate(): \DateTimeImmutable
    {
        $scheduleDayOfWeek = $this->config->get(self::CONF_SCHEDULE_DAY_OF_WEEK, 1); // Monday
        $scheduleTime = $this->config->get(self::CONF_SCHEDULE_TIME, "09:00");
        $scheduleTimeZone = $this->config->get(self::CONF_SCHEDULE_TIME_ZONE, "America/New_York");
        $timePieces = explode(":", $scheduleTime);
        if (count($timePieces) === 2) {
            $scheduleHour = (int) $timePieces[0];
            $scheduleMinutes = (int) $timePieces[1];
        } else {
            $scheduleHour = 9;
            $scheduleMinutes = 0;
            ErrorLogger::warning(
                "Digest scheduled time is invalid",
                ["digest"],
                [
                    "schedule" => [
                        "time" => $scheduleTime,
                        "dayOfWeek" => $scheduleDayOfWeek,
                        "timeZone" => $scheduleTimeZone,
                    ],
                ]
            );
        }

        $timezone = new \DateTimeZone($scheduleTimeZone);
        $now = CurrentTimeStamp::getDateTime();
        $scheduleDateTime = $now
            // Put ourselves in that timezone for construction.
            ->setTimezone($timezone)
            ->setISODate(
                $now->format("Y"), // Year
                $now->format("W"), // ISO week of the year.
                $scheduleDayOfWeek
            )
            ->setTime($scheduleHour, $scheduleMinutes)
            // Make sure we are back in UTC before passing the date anywhere else.
            ->setTimezone(new \DateTimeZone("UTC"));

        if ($scheduleDateTime->getTimestamp() <= $now->getTimestamp()) {
            // If we are scheduled in the past we need to move to the future.
            $scheduleDateTime = $scheduleDateTime->modify("+1 week");
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
                    $scheduleDateTime = $scheduleDateTime->modify("+1 week");
                }
            } catch (\Exception $e) {
                // Do nothing
            }
        }

        return $scheduleDateTime;
    }
}
