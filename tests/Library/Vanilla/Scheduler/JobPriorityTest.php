<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Vanilla\Library\Scheduler;

use \Vanilla\Scheduler\Job\JobPriority;

/**
 * Class JobPriorityTest
 */
final class JobPriorityTest extends \PHPUnit\Framework\TestCase {

    /**
     * Verify positive assertion of high priority.
     */
    public function testPriorityHigh() {
        $this->assertTrue(JobPriority::high()->is(JobPriority::high()));
    }

    /**
     * Verify positive assertion of normal priority.
     */
    public function testPriorityNormal() {
        $this->assertTrue(JobPriority::normal()->is(JobPriority::normal()));
    }

    /**
     * Verify positive assertion of low priority.
     */
    public function testPriorityLow() {
        $this->assertTrue(JobPriority::low()->is(JobPriority::low()));
    }

    /**
     * Verifying positive assertion of custom priority.
     */
    public function testValidLoosePriorityComparison() {
        $priority = JobPriority::high()->getValue();
        $this->assertTrue(JobPriority::loosePriority($priority)->is(JobPriority::high()));
    }

    /**
     * Verifying negative assertion of custom priority.
     */
    public function testInvalidLoosePriorityComparison() {
        $priority = JobPriority::high()->getValue();
        $this->assertFalse(JobPriority::loosePriority($priority)->is(JobPriority::normal()));
    }
}
