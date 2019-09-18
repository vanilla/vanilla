<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Scheduler;

use \Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class JobExecutionStatusTest.
 */
final class JobExecutionStatusTest extends \PHPUnit\Framework\TestCase {

    /**
     * Verifying positive assertion of "abandoned" status.
     */
    public function testStatusAbandoned() {
        $this->assertTrue(JobExecutionStatus::abandoned()->is(JobExecutionStatus::abandoned()));
    }

    /**
     * Verifying positive assertion of "complete" status.
     */
    public function testStatusComplete() {
        $this->assertTrue(JobExecutionStatus::complete()->is(JobExecutionStatus::complete()));
    }

    /**
     * Verifying positive assertion of "error" status.
     */
    public function testStatusError() {
        $this->assertTrue(JobExecutionStatus::error()->is(JobExecutionStatus::error()));
    }

    /**
     * Verifying positive assertion of "failed" status.
     */
    public function testStatusFailed() {
        $this->assertTrue(JobExecutionStatus::failed()->is(JobExecutionStatus::failed()));
    }

    /**
     * Verifying positive assertion of "invalid" status.
     */
    public function testStatusInvalid() {
        $this->assertTrue(JobExecutionStatus::invalid()->is(JobExecutionStatus::invalid()));
    }

    /**
     * Verifying positive assertion of "mismatch" status.
     */
    public function testStatusMismatch() {
        $this->assertTrue(JobExecutionStatus::mismatch()->is(JobExecutionStatus::mismatch()));
    }

    /**
     * Verifying positive assertion of "progress" status.
     */
    public function testStatusProgress() {
        $this->assertTrue(JobExecutionStatus::progress()->is(JobExecutionStatus::progress()));
    }

    /**
     * Verifying positive assertion of "received" status.
     */
    public function testStatusReceived() {
        $this->assertTrue(JobExecutionStatus::received()->is(JobExecutionStatus::received()));
    }

    /**
     * Verifying positive assertion of "retry" status.
     */
    public function testStatusRetry() {
        $this->assertTrue(JobExecutionStatus::retry()->is(JobExecutionStatus::retry()));
    }

    /**
     * Verifying positive assertion of "execution error" status.
     */
    public function testStatusStackExecutionError() {
        $this->assertTrue(JobExecutionStatus::stackExecutionError()->is(JobExecutionStatus::stackExecutionError()));
    }

    /**
     * Verifying positive assertion of custom status.
     */
    public function testValidLooseStatusComparison() {
        $status = JobExecutionStatus::retry()->getStatus();
        $this->assertTrue(JobExecutionStatus::looseStatus($status)->is(JobExecutionStatus::retry()));
    }

    /**
     * Verifying negative assertion of custom status.
     */
    public function testInvalidLooseStatusComparison() {
        $status = JobExecutionStatus::retry()->getStatus();
        $this->assertFalse(JobExecutionStatus::looseStatus($status)->is(JobExecutionStatus::complete()));
    }
}
