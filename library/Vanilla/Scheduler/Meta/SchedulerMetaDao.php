<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Meta;

use DateTime;
use Exception;
use Garden\EventManager;
use Gdn;
use Throwable;
use Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class CronMetaDao
 */
class SchedulerMetaDao {

    protected const SCHEDULER_JOB_DETAILS_PRUNE_AGE = 24 * 60 * 60;
    protected const SCHEDULER_CONTROL_META_NAME = "SCHEDULER_CONTROL";
    protected const SCHEDULER_JOB_META_PREFIX = "SCHEDULER_JOB_";

    /** @var Gdn */
    protected $gdn;

    /** @var EventManager */
    protected $eventManager;

    /**
     * CronMetaDao constructor.
     *
     * @param Gdn $gdn
     * @param EventManager $eventManager
     */
    public function __construct(Gdn $gdn, EventManager $eventManager) {
        $this->gdn = $gdn;
        $this->eventManager = $eventManager;
    }

    /**
     * Get JobMeta
     *
     * @param string $key
     * @return SchedulerJobMeta
     */
    public function getJob($key): ?SchedulerJobMeta {
        // Previously this method logged to the UserMeta table, but this wrecked performance on large sites.
        // This logging can be added back in the future, but will be more carefully considered));
        return null;
    }

    /**
     * PutJob
     *
     * @param SchedulerJobMeta $schedulerJobMeta
     * @return bool
     */
    public function putJob(SchedulerJobMeta $schedulerJobMeta): bool {
        $values = [
            'jobId' => $schedulerJobMeta->getJobId(),
            'received' => $schedulerJobMeta->getReceived(),
            'status' => $schedulerJobMeta->getStatus()->getStatus(),
            'errorMessage' => $schedulerJobMeta->getErrorMessage(),
        ];

        // Previously this method logged to the UserMeta table, but this wrecked performance on large sites.
        // This logging can be added back in the future, but will be more carefully considered.

        return true;
    }

    /**
     * @return SchedulerControlMeta|null
     */
    public function getControl(): ?SchedulerControlMeta {
        $values = json_decode($this->gdn::get(self::SCHEDULER_CONTROL_META_NAME, null), true);
        if ($values === null) {
            return null;
        } else {
            try {
                return new SchedulerControlMeta($values['lockTime'] ?? 0, $values['hostname'] ?? 'unknown');
            } catch (Throwable $t) {
                // If somehow we cannot instantiate a SchedulerControlMeta, we want to act as it doesn't exists
                return null;
            }
        }
    }

    /**
     * PutJob
     *
     * @param SchedulerControlMeta $schedulerControlMeta
     * @return bool
     */
    public function putControl(SchedulerControlMeta $schedulerControlMeta): bool {
        $values = [
            'lockTime' => $schedulerControlMeta->getLockTime(),
            'hostname' => $schedulerControlMeta->getHostname(),
        ];

        // Previously this method logged to the UserMeta table, but this wrecked performance on large sites.
        // This logging can be added back in the future, but will be more carefully considered.

        return true;
    }

    /**
     * Get Details
     *
     * @return array
     * @throws Exception In case of DateTime conversion error.
     */
    public function getDetails(): array {
        // Previously this method logged to the UserMeta table, but this wrecked performance on large sites.
        // This logging can be added back in the future, but will be more carefully considered));

        return [];
    }

    /**
     * Prune Details
     *
     * @param int|null $age
     * @return SchedulerJobMeta[]
     */
    public function pruneDetails(int $age = null): array {
        // Previously this method logged to the UserMeta table, but this wrecked performance on large sites.
        // This logging can be added back in the future, but will be more carefully considered));

        return [];
    }


    /**
     * Hydrate a JobMeta from raw data
     *
     * @param string $key
     * @param array $raw
     * @return SchedulerJobMeta|null
     */
    protected function hydrateJobMeta(string $key, array $raw) {
        if ($raw === null) {
            return null;
        } else {
            $schedulerJobMeta = new SchedulerJobMeta();
            $schedulerJobMeta->setKey($key);
            $schedulerJobMeta->setJobId($raw['jobId']);
            $schedulerJobMeta->setReceived($raw['received'] ?? 0);
            $schedulerJobMeta->setStatus(
                $raw['status'] ?
                    JobExecutionStatus::looseStatus($raw['status']) :
                    JobExecutionStatus::unknown()
            );
            $schedulerJobMeta->setErrorMessage($raw['errorMessage']);

            return $schedulerJobMeta;
        }
    }
}
