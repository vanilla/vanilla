<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace VanillaTests;

use Garden\Web\Data;
use PHPUnit\Framework\TestCase;
use Vanilla\Scheduler\Job\JobStatusModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\SchedulerInterface;
use Vanilla\Web\SystemTokenUtils;
use VanillaTests\Fixtures\Scheduler\InstantScheduler;

/**
 * Trait for testing jobs with the scheduler.
 */
trait SchedulerTestTrait {

    /**
     * Make sure we have a clean scheduler for every test.
     */
    public function setupSchedulerTestTrait() {
        $this->getScheduler()->reset();
        $this->getLongRunner()->reset();
    }

    /**
     * @return InstantScheduler
     */
    protected function getScheduler(): InstantScheduler {
        return \Gdn::getContainer()->get(SchedulerInterface::class);
    }

    /**
     * @return LongRunner
     */
    protected function getLongRunner(): LongRunner {
        return \Gdn::getContainer()->get(LongRunner::class);
    }

    /**
     * @return JobStatusModel
     */
    protected function getJobStatusModel(): JobStatusModel {
        return \Gdn::getContainer()->get(JobStatusModel::class);
    }

    /**
     * Assert that there are a certain number of jobs with a particular status.
     *
     * @param int $expectedCount
     * @param array $where
     *
     * @return array The results.
     */
    protected function assertTrackedJobCount(int $expectedCount, array $where): array {
        $where += [
            'trackingUserID' => \Gdn::session()->UserID,
        ];
        $results = $this->getJobStatusModel()->select($where);
        TestCase::assertCount($expectedCount, $results);
        return $results;
    }
}
