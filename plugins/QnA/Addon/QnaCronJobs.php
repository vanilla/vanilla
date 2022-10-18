<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license gpl-2.0-only
 */

namespace Vanilla\QnA\Addon;

use Vanilla\AddonCronJobs;
use Vanilla\QnA\Job\QnaFollowupJob;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Cron jobs for the QnA addon.
 */
class QnaCronJobs extends AddonCronJobs
{
    /**
     * @inheritdoc
     */
    public function getCronJobDescriptors(): array
    {
        return [new CronJobDescriptor(QnaFollowupJob::class, "30 1,13 * * *")];
    }
}
