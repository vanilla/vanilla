<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\JsonFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;
use Vanilla\Models\PipelineModel;
use Vanilla\Scheduler\Driver\DriverSlipInterface;

/**
 * Model for tracking job statuses.
 */
class JobStatusModel extends PipelineModel
{
    private const TABLE_NAME = "jobStatus";
    private const KEY_COUNT_CHANGED = "jobStatus/countChanged/{userID}";
    private const KEY_LAST_SEEN = "jobStatus/lastSeen/{userID}";

    // 5 minutes.
    private const UPDATE_TIME_SECONDS = 60 * 5;

    /** @var \Gdn_Cache */
    private $cache;

    /**
     * DI and configuration.
     *
     * @param \Gdn_Cache $cache
     */
    public function __construct(\Gdn_Cache $cache)
    {
        parent::__construct(self::TABLE_NAME);

        $this->cache = $cache;
        $dateFields = new CurrentDateFieldProcessor();
        $dateFields->camelCase();
        $this->addPipelineProcessor($dateFields);
        $this->addPipelineProcessor(new JsonFieldProcessor(["message"]));
        $this->addPipelineProcessor(new PruneProcessor("dateUpdated", "3 days"));
        $this->addPipelineProcessor(new Operation\UpdateProcessor([$this, "handleUpdate"]));
    }

    /**
     * Create the database structure for the model.
     *
     * @param \Gdn_DatabaseStructure $structure
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        $structure
            ->table(self::TABLE_NAME)
            ->primaryKey("jobStatusID")
            ->column("jobTrackingID", "varchar(100)", false, ["index.jobTrackingID", "unique"])
            ->column("jobType", "varchar(300)", false)
            ->column("trackingUserID", "int", false, ["index.recentlyChanged"])
            ->column("dateInserted", "datetime", true)
            ->column("dateUpdated", "datetime", true, "index.recentlyChanged")
            ->column("jobExecutionStatus", "varchar(100)", false)
            ->column("errorMessage", "text", true)
            ->column("progressTotalQuantity", "int", true)
            ->column("progressCompleteQuantity", "int", true)
            ->column("progressFailedQuantity", "int", true)
            ->set();
    }

    /**
     * Update an existing job with a driver slip.
     *
     * @param DriverSlipInterface $driverSlip The driver slip.
     */
    public function updateDriverSlip(DriverSlipInterface $driverSlip)
    {
        $set = [
            "jobExecutionStatus" => $driverSlip->getStatus()->getStatus(),
            "errorMessage" => $driverSlip->getErrorMessage(),
        ];
        $this->update($set, [
            "jobTrackingID" => $driverSlip->getTrackingID(),
        ]);
    }

    /**
     * Track progress of a job.
     *
     * @param TrackableJobAwareInterface $job
     * @param JobExecutionProgress $progress
     */
    public function progressJob(TrackableJobAwareInterface $job, JobExecutionProgress $progress)
    {
        $set = [
            "jobExecutionStatus" => $progress->getStatus(),
            "progressTotalQuantity" => $progress->getQuantityTotal(),
            "progressCompleteQuantity" => $progress->getQuantityComplete(),
            "progressFailedQuantity" => $progress->getQuantityFailed(),
        ];
        $this->update($set, [
            "jobTrackingID" => $job->getTrackingID(),
        ]);
    }

    /**
     * Start tracking a job using a DriverSlip.
     *
     * @param DriverSlipInterface $driverSlip The driver slip.
     * @param int $trackingUserID The user to track the job for.
     */
    public function insertDriverSlip(DriverSlipInterface $driverSlip, int $trackingUserID)
    {
        return $this->insert([
            "jobTrackingID" => $driverSlip->getTrackingID(),
            "trackingUserID" => $trackingUserID,
            "jobExecutionStatus" => $driverSlip->getStatus()->getStatus(),
            "jobType" => $driverSlip->getType(),
        ]);
    }

    /**
     * Increment update counts when operations are updated.
     *
     * @param Operation $operation
     * @param callable $stack
     * @return mixed
     */
    public function handleUpdate(Operation $operation, callable $stack)
    {
        $result = $stack($operation);
        $items = $this->createSql()
            ->select("trackingUserID")
            ->getWhere(self::TABLE_NAME)
            ->resultArray();
        $userIDs = array_column($items, "trackingUserID");
        foreach ($userIDs as $userID) {
            $this->incrementUpdateCount($userID);
        }
        return $result;
    }

    /**
     * Get the current count of incomplete jobs for a user.
     *
     * @param int $trackingUserID The userID.
     * @param array $where Extra parameters to filter.
     * @return int The count.
     */
    public function getIncompleteCountForUser(int $trackingUserID, array $where = []): int
    {
        $count = $this->createSql()->getCount(
            self::TABLE_NAME,
            array_merge(
                [
                    "trackingUserID" => $trackingUserID,
                    "jobExecutionStatus" => JobExecutionStatus::incompleteStatuses(),
                ],
                $where
            )
        );
        return $count;
    }

    /**
     * Update the last time a user has checked changed jobs.
     *
     * @param int $trackingUserID
     */
    public function trackLastSeenTime(int $trackingUserID)
    {
        $cacheKey = formatString(self::KEY_LAST_SEEN, ["userID" => $trackingUserID]);
        $this->cache->store($cacheKey, CurrentTimeStamp::get(), [
            \Gdn_Cache::FEATURE_EXPIRY => self::UPDATE_TIME_SECONDS,
        ]);
        // Also set our count to 0.
        $changedCacheKey = formatString(self::KEY_COUNT_CHANGED, ["userID" => $trackingUserID]);
        $this->cache->store($changedCacheKey, 0, [
            \Gdn_Cache::FEATURE_EXPIRY => self::UPDATE_TIME_SECONDS,
        ]);
    }

    /**
     * Get the last time a user has checked changed jobs.
     *
     * @param int $trackingUserID
     * @return \DateTimeInterface
     */
    public function getLastSeenTime(int $trackingUserID): \DateTimeInterface
    {
        $cacheKey = formatString(self::KEY_LAST_SEEN, ["userID" => $trackingUserID]);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== \Gdn_Cache::CACHEOP_FAILURE) {
            // Coerce into datetime.
            return new \DateTimeImmutable("@" . $cached);
        }

        // Default last seen time.
        return CurrentTimeStamp::getDateTime()->modify(
            formatString("-{seconds} seconds", ["seconds" => self::UPDATE_TIME_SECONDS])
        );
    }

    /**
     * Get update jobs for users.
     *
     * @param int $trackingUserID The user.
     * @param \DateTimeInterface $since Only get jobs updated after this moment.
     * @param array $where Filters to apply.
     * @return array
     */
    public function selectUpdatedForUser(int $trackingUserID, \DateTimeInterface $since, array $where = [])
    {
        $result = $this->select(
            array_merge(
                [
                    "trackingUserID" => $trackingUserID,
                    "dateUpdated >" => $since,
                ],
                $where
            )
        );
        return $result;
    }

    /**
     * Increment a users current count of updated items in the cache.
     *
     * @param int $userID
     */
    public function incrementUpdateCount(int $userID)
    {
        $cacheKey = formatString(self::KEY_COUNT_CHANGED, ["userID" => $userID]);
        $this->cache->incrementFrom($cacheKey, 1, 0);
    }

    /**
     * Check if we have a valid cache for efficiently polling.
     *
     * @return bool
     */
    public function pollingCacheIsActive(): bool
    {
        return $this->cache::activeEnabled();
    }

    /**
     * Get the current count of updated jobs for a user.
     *
     * This is not super
     *
     * @param int $trackingUserID The user.
     * @param \DateTimeInterface $since The time to check since.
     * @return int
     */
    public function getCountUpdatedForUser(int $trackingUserID, \DateTimeInterface $since): int
    {
        $cacheKey = formatString(self::KEY_COUNT_CHANGED, ["userID" => $trackingUserID]);
        $cached = $this->cache->get($cacheKey);
        if ($cached !== \Gdn_Cache::CACHEOP_FAILURE) {
            return $cached;
        }

        $count = $this->createSql()->getCount(self::TABLE_NAME, [
            "trackingUserID" => $trackingUserID,
            "dateUpdated >" => $since,
        ]);
        $this->cache->store($cacheKey, $count, [
            \Gdn_Cache::FEATURE_EXPIRY => self::UPDATE_TIME_SECONDS,
        ]);
        return $count;
    }
}
