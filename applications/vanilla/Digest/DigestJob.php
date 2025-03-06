<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Digest;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Logger;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Scheduler\Job\LocalApiJob;

abstract class DigestJob extends LocalApiJob implements LoggerAwareInterface
{
    /** @var string Config key representing how long before the digest is sent that it should be generated. */
    public const CONF_DIGEST_GENERATION_OFFSET = "Garden.Digest.GenerationOffset";
    /** @var string Config holding an ISO day of the week. */
    public const CONF_SCHEDULE_DAY_OF_WEEK = "Garden.Digest.DayOfWeek";

    /** @var string Config for a 24 hour time of day to schedule the digest. */
    public const CONF_SCHEDULE_TIME = "Garden.Digest.ScheduleTime";
    public const CONF_SCHEDULE_TIME_ZONE = "Garden.Digest.ScheduleTimeZone";

    public const DEFAULT_DELIVERY_HOUR = 9;

    public const DEFAULT_DELIVERY_MINUTE = 0;

    protected const DEFAULT_SCHEDULE_OFFSET = "-3 hours";

    protected const DIGEST_TYPE = "digest";

    protected const LOG_CONTEXT = [
        Logger::FIELD_TAGS => ["digest"],
        Logger::FIELD_CHANNEL => Logger::CHANNEL_SYSTEM,
    ];

    use LoggerAwareTrait;

    protected EmailDigestGenerator $digestGenerator;
    protected ConfigurationInterface $config;
    protected DigestModel $digestModel;

    protected bool $isDigestEnabled = false;
    protected int $scheduleDayOfWeek = 1;
    protected string $scheduleTime;
    protected string $scheduleTimeZone = "America/New_York";
    protected string $digestOffset;

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
        $this->initializeConfig();
    }

    /**
     * initialize the configurations for the digest job.
     */
    public function initializeConfig(): void
    {
        $this->isDigestEnabled = $this->config->get("Garden.Digest.Enabled", false);
        $this->scheduleDayOfWeek = $this->config->get(self::CONF_SCHEDULE_DAY_OF_WEEK, 1);
        $defaultTime =
            sprintf("%02d", self::DEFAULT_DELIVERY_HOUR) . ":" . sprintf("%02d", self::DEFAULT_DELIVERY_MINUTE);
        $this->scheduleTime = $this->config->get(self::CONF_SCHEDULE_TIME, $defaultTime);
        $this->scheduleTimeZone = $this->config->get(self::CONF_SCHEDULE_TIME_ZONE, "America/New_York");
        $this->digestOffset = $this->config->get(self::CONF_DIGEST_GENERATION_OFFSET, self::DEFAULT_SCHEDULE_OFFSET);
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

    /**
     * pre-run checks for the digest job.
     *
     * @return bool
     */
    protected function validateBeforeRun(): bool
    {
        if (!$this->isDigestEnabled) {
            $this->logger->debug(
                ucfirst(static::DIGEST_TYPE) . " digest was not generated because digest is disabled.",
                static::LOG_CONTEXT + [
                    Logger::FIELD_EVENT => static::DIGEST_TYPE . "_digest_skip",
                ]
            );
            return false;
        }

        if ($this->config->get(\Gdn_Email::CONF_DISABLED, false)) {
            $this->logger->debug(
                ucfirst(static::DIGEST_TYPE) . " digest was not generated because email is disabled.",
                static::LOG_CONTEXT + [
                    Logger::FIELD_EVENT => static::DIGEST_TYPE . "_digest_skip",
                ]
            );
            return false;
        }
        $allowedDigestFrequencies = DigestModel::DIGEST_FREQUENCY_OPTIONS;

        if (!in_array(static::DIGEST_TYPE, $allowedDigestFrequencies, true)) {
            $this->logger->debug(
                ucfirst(static::DIGEST_TYPE) .
                    " digest was not generated because the option is not set under enabled digest frequency.",
                static::LOG_CONTEXT + [
                    Logger::FIELD_EVENT => static::DIGEST_TYPE . "_digest_skip",
                ]
            );
            return false;
        }

        return true;
    }

    /**
     * Compare the current time with the scheduled time to see if it is time to generate the digest.
     * If it is not time to generate the digest, log the event and return null.
     *
     * @return \DateTimeImmutable|null
     */
    protected function getScheduledDateTime(): ?\DateTimeImmutable
    {
        $scheduleDateTime = static::getNextScheduledDate(); // We get the utc time here
        $digestGenerationTime = $scheduleDateTime->modify($this->digestOffset);
        $currentTime = CurrentTimeStamp::getDateTime();
        if ($currentTime->getTimestamp() < $digestGenerationTime->getTimestamp()) {
            $this->logger->info(
                ucfirst(static::DIGEST_TYPE) .
                    " digest was not generated because it is too early. The next " .
                    static::DIGEST_TYPE .
                    " digest will be generated at {$digestGenerationTime->format(
                        DATE_ATOM
                    )} for delivery at {$scheduleDateTime->format(DATE_ATOM)}",
                static::LOG_CONTEXT + [
                    "nextGenerationDate" => $digestGenerationTime,
                    "nextScheduleDate" => $scheduleDateTime,
                    Logger::FIELD_EVENT => static::DIGEST_TYPE . "_digest_skip",
                ]
            );
            return null;
        }
        return $scheduleDateTime;
    }

    /**
     * check if a digest is already scheduled.
     *
     * @return bool
     */
    protected function getExistingDigest(string $digestType): array
    {
        $currentTime = CurrentTimeStamp::getDateTime();
        $existingDigests = $this->digestModel->select([
            "digestType" => $digestType,
            "dateScheduled >" => $currentTime,
        ]);
        return $existingDigests;
    }

    /**
     * Get the scheduled time seperated as hour and minute.
     *
     * @return array|int[]
     */
    public function getScheduledTimePieces(): array
    {
        $timePieces = explode(":", $this->scheduleTime);
        if (count($timePieces) === 2) {
            return [(int) $timePieces[0], (int) $timePieces[1]];
        }

        ErrorLogger::warning(
            "Digest scheduled time is invalid",
            ["digest"],
            [
                "schedule" => [
                    "time" => $this->scheduleTime,
                    "dayOfWeek" => $this->scheduleDayOfWeek,
                    "timeZone" => $this->scheduleTimeZone,
                ],
            ]
        );
        return [self::DEFAULT_DELIVERY_HOUR, self::DEFAULT_DELIVERY_MINUTE];
    }

    abstract public function getNextScheduledDate(): \DateTimeImmutable;
}
