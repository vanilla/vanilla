<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

use JsonSerializable;

/**
 * Job Status
 */
class JobExecutionStatus implements JsonSerializable {

    /**
     * @var string
     */
    protected $myStatus;

    /**
     * JobExecutionStatus constructor
     *
     * @param string $status
     */
    protected function __construct(string $status) {
        $this->myStatus = $status;
    }

    /**
     * @return string
     */
    public function getStatus(): string {
        return $this->myStatus;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize() {
        return $this->myStatus;
    }

    /**
     * Is that JobExecutionStatus
     *
     * @param JobExecutionStatus $jes
     * @return bool
     */
    public function is(JobExecutionStatus $jes): bool {
        return $this->myStatus == $jes->getStatus();
    }

    /**
     * @return JobExecutionStatus
     */
    public static function abandoned() {
        return new JobExecutionStatus('abandoned');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function complete() {
        return new JobExecutionStatus('complete');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function error() {
        return new JobExecutionStatus('error');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function failed() {
        return new JobExecutionStatus('failed');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function invalid() {
        return new JobExecutionStatus('invalid');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function mismatch() {
        return new JobExecutionStatus('mismatch');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function progress() {
        return new JobExecutionStatus('progress');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function received() {
        return new JobExecutionStatus('received');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function retry() {
        return new JobExecutionStatus('retry');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function stackExecutionError() {
        return new JobExecutionStatus('stackError');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function intended() {
        return new JobExecutionStatus('intended');
    }

    /**
     * @return JobExecutionStatus
     */
    public static function unknown() {
        return new JobExecutionStatus('unknown');
    }

    /**
     * Get a list of incomplete statuses.
     *
     * @return string[]
     */
    public static function incompleteStatuses(): array {
        return [
            self::received()->getStatus(),
            self::progress()->getStatus(),
            self::intended()->getStatus(),
        ];
    }

    /**
     * Set a loose status A.K.A set your own status
     *
     * @param string $status
     * @return JobExecutionStatus
     */
    public static function looseStatus(string $status) {
        return new JobExecutionStatus($status);
    }
}
