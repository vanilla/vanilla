<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Jobs;

use AccessTokenModel;
use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Job to rotate the System token from the config.
 */
class SystemTokenRotationJob extends LocalApiJob
{
    private AccessTokenModel $accessTokenModel;

    public function __construct(AccessTokenModel $accessTokenModel)
    {
        $this->accessTokenModel = $accessTokenModel;
    }

    /**
     * No message needed.
     *
     * @param array $message
     */
    public function setMessage(array $message)
    {
        return;
    }

    /**
     * Rotate the System token from the config file.
     *
     * @return JobExecutionStatus
     */
    public function run(): JobExecutionStatus
    {
        $this->accessTokenModel->ensureSingleSystemToken();
        return JobExecutionStatus::complete();
    }
}
