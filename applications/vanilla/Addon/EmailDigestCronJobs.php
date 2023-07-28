<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2023 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Forum\Addon;

use Vanilla\AddonCronJobs;
use Vanilla\Forum\Jobs\EmailDigestContentJob;
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
        return [new CronJobDescriptor(EmailDigestContentJob::class, $this->getSchedule())];
    }

    /**
     * Get the schedule to generate digest content for the site
     *
     * @return string
     */
    private function getSchedule(): string
    {
        $scheduleDay = (int) \Gdn::config()->get("Garden.Digest.Schedule", false);
        //if set to sunday then the content should be generated on a saturday
        if ($scheduleDay == 0) {
            $digestContentDay = 6;
        } else {
            $digestContentDay = $scheduleDay - 1;
        }

        return "0 23 * * {$digestContentDay}";
    }
}
