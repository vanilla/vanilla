<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Addon;

use Vanilla\AddonCronJobs;
use Vanilla\Forum\Digest\ScheduleWeeklyDigestJob;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Cron jobs for Email Digest
 */
class EmailDigestCronJobs extends AddonCronJobs
{
    /**
     * @inheritdoc
     */
    public function getCronJobDescriptors(): array
    {
        return [new CronJobDescriptor(ScheduleWeeklyDigestJob::class, ScheduleWeeklyDigestJob::getCronExpression())];
    }
}
