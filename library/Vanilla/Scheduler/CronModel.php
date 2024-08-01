<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Cron\CronExpression;
use Garden\Web\Exception\ServerException;
use Symfony\Component\Lock\LockInterface;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\CurrentTimeStamp;
use Vanilla\Models\LockService;
use Vanilla\Scheduler\Descriptor\CronJobDescriptorInterface;

final class CronModel
{
    public const CONF_MINIMUM_TIMESPAN = "Garden.Scheduler.CronMinimumTimeSpan";
    public const CONF_OFFSET_SECONDS = "Garden.Scheduler.CronOffsetSeconds";

    private const CACHE_KEY_LOCK = "cron_lock";

    private const CACHE_KEY_LAST_RUN = "cron_time";

    private const CRON_MINIMUM_TIMESPAN = 60 * 10; // 10 minutes.

    /** @var \Gdn_Cache */
    private $cache;

    /** @var LockService */
    private $lockService;

    /** @var ConfigurationInterface */
    private $config;

    /**
     * DI.
     *
     * @param \Gdn_Cache $cache
     * @param LockService $lockService
     * @param ConfigurationInterface $config
     */
    public function __construct(\Gdn_Cache $cache, LockService $lockService, ConfigurationInterface $config)
    {
        $this->cache = $cache;
        $this->lockService = $lockService;
        $this->config = $config;
    }

    /**
     * Create a lock for running a cron.
     *
     * @return LockInterface
     */
    public function createLock(): LockInterface
    {
        $lockDuration = $this->config->get("Garden.Scheduler.CronMinimumTimeSpan", self::CRON_MINIMUM_TIMESPAN);
        return $this->lockService->createLock(self::CACHE_KEY_LOCK, $lockDuration);
    }

    /**
     * Track the time of the last successful cron run.
     *
     * @param bool $useOffset Use the random cron offset from the site when tracking the last run time.
     *
     * @return void
     * @throws ServerException If we failed to track the run.
     */
    public function trackRun()
    {
        $runDate = CurrentTimeStamp::getDateTime();
        $result = $this->cache->store(self::CACHE_KEY_LAST_RUN, $runDate);
        if ($result !== \Gdn_Cache::CACHEOP_SUCCESS) {
            throw new ServerException("Failed to track scheduled cron run.");
        }
    }

    /**
     * Determine if the cron should run.
     *
     * - Get the last time we ran. This defaults to a day ago in case our cache is empty.
     * - Add a random offset for the current site to the last run date. This can push crons into the future for certain sites.
     * - Calculate the next expected run date for the cron.
     * - If we have passed that date, we should run.
     *
     * @param CronJobDescriptorInterface $jobDescriptor
     *
     * @return bool
     */
    public function shouldRun(CronJobDescriptorInterface $jobDescriptor): bool
    {
        // A "commented" cron schedule will be skipped
        if (strpos($jobDescriptor->getSchedule(), "#") === 0) {
            return false;
        }

        $cron = new CronExpression($jobDescriptor->getSchedule());

        $offsetSeconds = $this->config->get(self::CONF_OFFSET_SECONDS, 0);
        $lastRunDate = $this->lastRunDate();
        $lastRunDate = $lastRunDate->modify("-{$offsetSeconds} seconds");

        // See when the next run date is based off our last runtime.
        $nextRunDate = $cron->getNextRunDate($lastRunDate);
        $nextRunDate = $nextRunDate->modify("+{$offsetSeconds} seconds");

        // Return if we've passed the next runtime.
        $currentDate = CurrentTimeStamp::getDateTime();

        $result = $currentDate >= $nextRunDate;
        return $result;
    }

    /**
     * Get our cron's last run time.
     *
     * @return \DateTimeImmutable
     */
    private function lastRunDate(): \DateTimeImmutable
    {
        $cached = $this->cache->get(self::CACHE_KEY_LAST_RUN);
        if ($cached instanceof \DateTimeImmutable) {
            return $cached;
        } else {
            return (new \DateTimeImmutable())->modify("-1 day");
        }
    }
}
