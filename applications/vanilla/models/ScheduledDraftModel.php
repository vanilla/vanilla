<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Models;

use InvalidArgumentException;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation\CurrentDateFieldProcessor;
use Vanilla\Database\Operation\PruneProcessor;

/**
 * Model to hold the history of scheduled drafts job
 */
class ScheduledDraftModel extends PipelineModel
{
    private const PRUNE_AFTER = "-2 weeks";

    const SCHEDULED = "scheduled";
    const PROCESSING = "processing";
    const PROCESSED = "processed";
    const FAILED = "failed";

    private ModelCache $cache;
    public function __construct()
    {
        parent::__construct("draftScheduled");
        $this->setPrimaryKey("scheduleID");

        $dateProcessor = new CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateScheduled"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->addPipelineProcessor(new PruneProcessor("dateScheduled", self::PRUNE_AFTER, 10));

        $this->cache = new ModelCache("draftScheduled", \Gdn::cache(), [\Gdn_Cache::FEATURE_EXPIRY => 30]);
    }

    /**
     * Invalidate the cache
     *
     * @return void
     */
    public function invalidateCache(): void
    {
        $this->cache->invalidateAll();
    }
    /**
     * @param \Gdn_DatabaseStructure $structure
     * @return void
     */
    public static function structure(\Gdn_DatabaseStructure $structure)
    {
        // create a new table to store the scheduled drafts

        $structure
            ->table("draftScheduled")
            ->primaryKey("scheduleID")
            ->column("jobID", "varchar(255)", false, ["unique.jobID"])
            ->column("totalDrafts", "int", null)
            ->column("dateScheduled", "datetime", null, "index.scheduling")
            ->column("status", "varchar(50)", "scheduled", "index.scheduling")
            ->set();
    }

    /**
     * Add a new schedule job
     *
     * @param int $totalDrafts
     * @return int
     * @throws \Exception
     */
    public function saveScheduled(int $totalDrafts, string $jobID = "tempID"): int
    {
        $data = [
            "totalDrafts" => $totalDrafts,
            "jobID" => $jobID,
        ];
        $scheduleID = $this->insert($data);
        $this->invalidateCache();
        return $scheduleID;
    }

    /**
     * update the status of the scheduled job
     *
     * @param int $scheduleID
     * @param string $status
     * @return bool
     * @throws \Exception
     */
    public function updateStatus(int $scheduleID, string $status): bool
    {
        if (!in_array($status, [self::PROCESSING, self::PROCESSED, self::FAILED])) {
            throw new InvalidArgumentException("Invalid status");
        }
        $this->invalidateCache();
        return $this->update(["status" => $status], ["scheduleID" => $scheduleID]);
    }

    /**
     * Check if there is already scheduled jobs for the current day
     *
     * @return bool
     * @throws \Garden\Schema\ValidationException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function isCurrentlyScheduled(): bool
    {
        $today = CurrentTimeStamp::getDateTime()->format("Y-m-d");

        $count = $this->cache->getCachedOrHydrate([__METHOD__], function () use ($today) {
            $result = $this->selectSingle(
                ["dateScheduled >=" => $today, "status" => [self::SCHEDULED, self::PROCESSING]],
                [self::OPT_SELECT => "COUNT(*) as count"]
            );
            return $result["count"];
        });
        return $count > 0;
    }
}
