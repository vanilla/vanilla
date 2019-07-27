<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

declare(strict_types=1);

use \Vanilla\Scheduler\Job\JobPriority;
/**
 * Class JobPriorityTest
 */
final class JobPriorityTest extends \PHPUnit\Framework\TestCase {

    public function test_PriorityHigh_Expect_Pass() {
        $this->assertTrue(JobPriority::high()->is(JobPriority::high()));
    }

    public function test_PriorityNormal_Expect_Pass() {
        $this->assertTrue(JobPriority::normal()->is(JobPriority::normal()));
    }

    public function test_PriorityLow_Expect_Pass() {
        $this->assertTrue(JobPriority::low()->is(JobPriority::low()));
    }

    public function test_PriorityLoose_Expect_Pass() {
        $priority = JobPriority::high()->getValue();
        $this->assertTrue(JobPriority::loosePriority($priority)->is(JobPriority::high()));
    }

    public function test_PriorityLoose_Expect_Fail() {
        $priority = JobPriority::high()->getValue();
        $this->assertFalse(JobPriority::loosePriority($priority)->is(JobPriority::normal()));
    }
}
