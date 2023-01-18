<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license MIT
 */

namespace VanillaTests\Jobs;

use SystemTokenRotationJob;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\SchedulerInterface;
use VanillaTests\SiteTestCase;

/**
 * Test for the System token rotation CRON.
 */
class TestSystemTokenRotation extends SiteTestCase
{
    /**
     * @inheritdoc
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->config = \Gdn::config();
        $this->accessTokenModel = $this->container()->get(\AccessTokenModel::class);
    }

    /**
     * Test that the SystemTokenRotationJob proprely rotates the token and that the value inserted in the config is valid.
     */
    public function testCronjobs()
    {
        $initialToken = $this->config->get(\AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $job = $this->container()->get(SystemTokenRotationJob::class);
        $result = $job->run();
        $this->assertEquals(JobExecutionStatus::complete(), $result);

        $newToken = $this->config->get(\AccessTokenModel::CONFIG_SYSTEM_TOKEN);
        $this->assertNotEquals($initialToken, $newToken);
        $this->assertIsArray($this->accessTokenModel->verify($newToken));
    }
}
