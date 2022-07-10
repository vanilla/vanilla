<?php
/**
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Models;

use Gdn_Cache;
use Vanilla\CurrentTimeStamp;
use Vanilla\Database\Operation;
use Gdn_Session;
use Vanilla\Models\FullRecordCacheModel;
use Vanilla\Models\Model;
use Vanilla\RemoteResource\LocalRemoteResourceJob;
use Vanilla\Scheduler\Descriptor\NormalJobDescriptor;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * A model for managing products.
 */
class RemoteResourceModel extends FullRecordCacheModel {

    /** Cache time to live. */
    const CACHE_TTL = 3600;

    /** Refresh time interval(seconds). */
    const REFRESH_INTERVAL = 3600;

    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * ProductModel constructor.
     *
     * @param \Gdn_Cache $cache
     * @param SchedulerInterface $scheduler
     */
    public function __construct(
        \Gdn_Cache $cache,
        SchedulerInterface $scheduler
    ) {
        parent::__construct("remoteResource", $cache);
        $dateProcessor = new Operation\CurrentDateFieldProcessor();
        $dateProcessor->setInsertFields(["dateInserted", "dateUpdated"])
            ->setUpdateFields(["dateUpdated"]);
        $this->addPipelineProcessor($dateProcessor);
        $this->scheduler = $scheduler;
    }

    /**
     * Get remote resource content by url.
     *
     * Null will be returned if there is no remote resource matching
     * the url in the DB or cache. If the content passed the refresh
     * interval time (default 1hr), it's considered stale, a new job
     * will be triggered and the stale content returned until job is
     * complete.  If there is an error whatever content we have is
     * returned(null|existing|stale).
     *
     * @param string $url
     * @return string|null
     */
    public function getByUrl(string $url): ?string {
        $resource = $this->select(
            ["url" => $url],
            ["cacheOptions" => [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL]]
        );

        $resource = is_array($resource) ? reset($resource) : $resource;
        $isValid = $resource ? !$this->checkIfContentIsStale($resource) : false;

        if (!$isValid) {
            $this->triggerLocalRemoteResourceJob($url);
        }

        $resourceContent = $resource['content'] ?? null;
        return $resourceContent;
    }

    /**
     * Overrides the parent insert to replace if existing.
     *
     * @param array $set
     * @param array $options
     */
    public function insert($set, $options = []) {
        parent::insert($set, [Model::OPT_REPLACE => true, ["cacheOptions" => [Gdn_Cache::FEATURE_EXPIRY => self::CACHE_TTL]]]);
    }

    /**
     * Overrides parent::update
     *
     * @param array $set Field values to set.
     * @param array $where Conditions to restrict the update.
     * @param array $options Update options.
     * @return bool
     * @throws \Exception Only use insert method to update records.
     */
    public function update(array $set, array $where, array $options = []): bool {
        throw new \Exception('use insert method to update records');
    }

    /**
     * Trigger a remote resource job.
     *
     * @param string $url
     * @return string
     */
    public function triggerLocalRemoteResourceJob(string $url): string {
        $jobDescriptor = new NormalJobDescriptor(LocalRemoteResourceJob::class);
        $jobDescriptor->setMessage(["url" => $url]);
        $response = $this->scheduler->addJobDescriptor($jobDescriptor);
        return $response->getStatus()->getStatus();
    }

    /**
     * Check if the content is stale.
     *
     * @param array $resourceContent
     * @return bool
     */
    private function checkIfContentIsStale(array $resourceContent): bool {
        $resourceContentDateUpdated = $resourceContent['dateUpdated'] ?? null;
        $difference = null;
        if ($resourceContentDateUpdated) {
            $difference = CurrentTimeStamp::getCurrentTimeDifference($resourceContentDateUpdated);
        }
        return $difference > self::REFRESH_INTERVAL;
    }
}
