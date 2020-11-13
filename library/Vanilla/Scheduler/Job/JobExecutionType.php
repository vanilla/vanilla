<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * JobExecutionType
 */
class JobExecutionType {

    /**
     * @var string
     */
    protected $myType;

    /**
     * JobExecutionType constructor
     *
     * @param string $type
     */
    protected function __construct(string $type) {
        $this->myType = $type;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->myType;
    }

    /**
     * Is that JobExecutionStatus
     *
     * @param JobExecutionType $jet
     * @return bool
     */
    public function is(JobExecutionType $jet): bool {
        return $this->myType == $jet->getValue();
    }

    /**
     * @return JobExecutionType
     */
    public static function normal() {
        return new JobExecutionType('normal');
    }

    /**
     * @return JobExecutionType
     */
    public static function cron() {
        return new JobExecutionType('cron');
    }

    /**
     * Set a loose type A.K.A set your own type
     *
     * @param string $type
     * @return JobExecutionType
     */
    public static function looseType(string $type) {
        return new JobExecutionType($type);
    }
}
