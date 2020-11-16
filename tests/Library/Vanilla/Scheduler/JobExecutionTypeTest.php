<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests\Library\Vanilla\Scheduler;

use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\Job\JobExecutionType;

/**
 * Class JobExecutionTypeTest.
 */
final class JobExecutionTypeTest extends TestCase {

    /**
     * Verifying positive assertion of "normal" type.
     */
    public function testStatusNormal() {
        $this->assertTrue(JobExecutionType::normal()->is(JobExecutionType::normal()));
    }

    /**
     * Verifying positive assertion of "cron" type.
     */
    public function testStatusCron() {
        $this->assertTrue(JobExecutionType::cron()->is(JobExecutionType::cron()));
    }

    /**
     * Verifying positive assertion of custom status.
     */
    public function testValidLooseStatusComparison() {
        $type = JobExecutionType::normal()->getValue();
        $this->assertTrue(JobExecutionType::looseType($type)->is(JobExecutionType::normal()));
    }

    /**
     * Verifying negative assertion of custom status.
     */
    public function testInvalidLooseStatusComparison() {
        $type = JobExecutionType::normal()->getValue();
        $this->assertFalse(JobExecutionType::looseType($type)->is(JobExecutionType::cron()));
    }
}
