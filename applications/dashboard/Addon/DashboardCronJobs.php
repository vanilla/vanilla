<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Dashboard\Addon;
use AiSuggestionJob;
use Vanilla\AddonCronJobs;
use Vanilla\Dashboard\AutomationRules\Jobs\AutomationRuleJob;
use Vanilla\Dashboard\Jobs\SyncProductMessagesJob;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Cron jobs for the dashboard.
 */
class DashboardCronJobs extends AddonCronJobs
{
    /**
     * @inheritdoc
     */
    protected function getCronJobDescriptors(): array
    {
        return [
            new CronJobDescriptor(AutomationRuleJob::class, AutomationRuleJob::getCronExpression()),
            new CronJobDescriptor(AiSuggestionJob::class, AiSuggestionJob::getCronExpression()),
            new CronJobDescriptor(SyncProductMessagesJob::class, SyncProductMessagesJob::getCronExpression()),
        ];
    }
}
