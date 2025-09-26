<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2025 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Addon;

use Vanilla\AddonCronJobs;
use Vanilla\Forum\Draft\ScheduledDraftJob;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Cron jobs for scheduled drafts
 */
class ScheduledDraftCronJobs extends AddonCronJobs
{
    /**
     * @inheritdoc
     */
    public function getCronJobDescriptors(): array
    {
        return [new CronJobDescriptor(ScheduledDraftJob::class, ScheduledDraftJob::getCronExpression())];
    }
}
