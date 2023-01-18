<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Addon;

use Gdn_Configuration;
use Vanilla\AddonCronJobs;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Cron jobs trigger to rotate the System token.
 */
class SystemTokenRotationJobs extends AddonCronJobs
{
    /** @var Gdn_Configuration */
    private $config;

    /**
     * DI.
     *
     * @param SchedulerInterface $scheduler
     */
    public function __construct(SchedulerInterface $scheduler, Gdn_Configuration $config)
    {
        parent::__construct($scheduler);
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function getCronJobDescriptors(): array
    {
        $schedule = $this->config->get(\AccessTokenModel::CONFIG_SYSTEM_TOKEN_ROTATION_CRON, "0 */6 * * *");

        return [new CronJobDescriptor(\SystemTokenRotationJob::class, $schedule)];
    }
}
