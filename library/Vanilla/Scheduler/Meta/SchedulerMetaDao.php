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
        $raw = json_decode($this->gdn::get(self::SCHEDULER_JOB_META_PREFIX.$key, null), true);

        return $this->hydrateJobMeta($key, $raw);
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

        $this->gdn::set(self::SCHEDULER_JOB_META_PREFIX.$schedulerJobMeta->getKey(), json_encode($values));

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

        $this->gdn::set(self::SCHEDULER_CONTROL_META_NAME, json_encode($values));

        return true;
    }

    /**
     * Get Details
     *
     * @return array
     * @throws Exception In case of DateTime conversion error.
     */
    public function getDetails(): array {
        // We cannot use gdn::get because it return the default value is there are multiples results
        $schedulerJobMetas = $this->gdn::userMetaModel()->getUserMeta(0, self::SCHEDULER_JOB_META_PREFIX.'%', []);

        foreach ($schedulerJobMetas as $key => $schedulerJobMeta) {
            $schedulerJobMetas[$key] = json_decode($schedulerJobMeta, true);
            $schedulerJobMetas[$key]['receivedDate'] = new DateTime("@".($schedulerJobMetas[$key]['received'] ?? 0));
        }

        return $schedulerJobMetas;
    }

    /**
     * Prune Details
     *
     * @param int|null $age
     * @return SchedulerJobMeta[]
     */
    public function pruneDetails(int $age = null): array {
        $age = $age ?? self::SCHEDULER_JOB_DETAILS_PRUNE_AGE;
        $pruned = [];

        // We cannot use gdn::get because it return the default value is there are multiple results
        $schedulerJobMetasRaw = $this->gdn::userMetaModel()->getUserMeta(0, self::SCHEDULER_JOB_META_PREFIX.'%', []);
        foreach ($schedulerJobMetasRaw as $key => $schedulerJobMetaRaw) {
            $schedulerJobMeta = $this->hydrateJobMeta($key, json_decode($schedulerJobMetaRaw, true));
            if (time() - $schedulerJobMeta->getReceived() > $age) {
                $pruned[] = $schedulerJobMeta;
                $this->gdn::set($key, null);
            }
        }

        $this->eventManager->fire(SchedulerJobMetaPruneEvent::class, new SchedulerJobMetaPruneEvent($pruned));

        return $pruned;
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
