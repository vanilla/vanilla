<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Library\Jobs;

use Gdn_Configuration;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalJobInterface;
use Vanilla\Scheduler\Meta\SchedulerMetaDao;

/**
 * Class TidySchedulerMetaJob
 */
class TidySchedulerMetaJob implements LocalJobInterface {

    protected $config;
    protected $schedulerMetaDao;
    protected $message;

    /**
     * TidySchedulerMetaJob constructor
     *
     * @param Gdn_Configuration $config
     * @param SchedulerMetaDao $schedulerMetaDao
     */
    public function __construct(Gdn_Configuration $config, SchedulerMetaDao $schedulerMetaDao) {
        $this->config = $config;
        $this->schedulerMetaDao = $schedulerMetaDao;
    }

    /**
     * @param array $message
     */
    public function setMessage(array $message) {
        $this->message = $message;
    }

    /**
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus {
        $age = $this->message['age'] ?? $this->config->get("Garden.Scheduler.JobDetailsPruneAge", null);
        $this->schedulerMetaDao->pruneDetails($age);

        return JobExecutionStatus::complete();
    }
}
