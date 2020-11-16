<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Descriptor;

use Vanilla\Scheduler\Job\JobExecutionType;

/**
 * Class CronJobDescriptor
 */
class CronJobDescriptor extends NormalJobDescriptor implements CronJobDescriptorInterface {

    /** @var string */
    protected $schedule;

    /**
     * CronJobDescriptor constructor
     *
     * @param string $jobType
     * @param string $schedule
     */
    public function __construct(string $jobType, string $schedule) {
        parent::__construct($jobType);
        $this->schedule = $schedule;
    }

    /**
     * @return string
     */
    public function getSchedule(): string {
        return $this->schedule;
    }

    /**
     * @return JobExecutionType
     */
    public function getExecutionType(): JobExecutionType {
        return JobExecutionType::cron();
    }
}
