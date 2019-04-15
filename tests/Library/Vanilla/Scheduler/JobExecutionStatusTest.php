<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare(strict_types=1);

use \Vanilla\Scheduler\Job\JobExecutionStatus;

/**
 * Class JobExecutionStatusTest.
 */
final class JobExecutionStatusTest extends \PHPUnit\Framework\TestCase {

    public function test_StatusAbandoned_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::abandoned()->is(JobExecutionStatus::abandoned()));
    }

    public function test_StatusComplete_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::complete()->is(JobExecutionStatus::complete()));
    }

    public function test_StatusError_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::error()->is(JobExecutionStatus::error()));
    }

    public function test_StatusFailed_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::failed()->is(JobExecutionStatus::failed()));
    }

    public function test_StatusInvalid_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::invalid()->is(JobExecutionStatus::invalid()));
    }

    public function test_StatusMismatch_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::mismatch()->is(JobExecutionStatus::mismatch()));
    }

    public function test_StatusProgress_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::progress()->is(JobExecutionStatus::progress()));
    }

    public function test_StatusReceived_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::received()->is(JobExecutionStatus::received()));
    }

    public function test_StatusRetry_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::retry()->is(JobExecutionStatus::retry()));
    }

    public function test_StatusStackExecutionError_Expect_Pass() {
        $this->assertTrue(JobExecutionStatus::stackExecutionError()->is(JobExecutionStatus::stackExecutionError()));
    }

    public function test_StatusLooseStatus_Expect_Pass() {
        $status = JobExecutionStatus::retry()->getStatus();
        $this->assertTrue(JobExecutionStatus::looseStatus($status)->is(JobExecutionStatus::retry()));
    }

    public function test_Status_WithLooseStatus_Expect_Fail() {
        $status = JobExecutionStatus::retry()->getStatus();
        $this->assertFalse(JobExecutionStatus::looseStatus($status)->is(JobExecutionStatus::complete()));
    }
}
