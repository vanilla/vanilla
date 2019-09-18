<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * JobPriority
 */
class JobPriority {
    /**
     * @var string
     */
    protected $myPriority;

    /**
     * JobPriority constructor.
     *
     * @param string $priority
     */
    protected function __construct(string $priority) {
        $this->myPriority = $priority;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->myPriority;
    }

    /**
     * Is that JobExecutionStatus
     *
     * @param JobPriority $jpr
     * @return bool
     */
    public function is(JobPriority $jpr): bool {
        return $this->myPriority == $jpr->getValue();
    }

    /**
     * @return JobPriority
     */
    public static function high() {
        return new JobPriority('high');
    }

    /**
     * @return JobPriority
     */
    public static function normal() {
        return new JobPriority('normal');
    }

    /**
     * @return JobPriority
     */
    public static function low() {
        return new JobPriority('low');
    }

    /**
     * Set a loose priority A.K.A set your own priority
     *
     * @param string $priority
     * @return JobPriority
     */
    public static function loosePriority(string $priority) {
        return new JobPriority($priority);
    }
}
