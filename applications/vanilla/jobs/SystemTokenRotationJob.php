<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license MIT
 */

use Vanilla\Scheduler\Job\JobExecutionStatus;
use Vanilla\Scheduler\Job\LocalApiJob;

/**
 * Job to rotate the System token from the config.
 */
class SystemTokenRotationJob extends LocalApiJob
{
    public function __construct(AccessTokenModel $accessTokenModel)
    {
        $this->accesTokenModel = $accessTokenModel;
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
        $this->accesTokenModel->ensureSingleSystemToken();
        return JobExecutionStatus::complete();
    }
}
