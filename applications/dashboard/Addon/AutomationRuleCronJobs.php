<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license MIT
 */

namespace Vanilla\Dashboard\Addon;
use Vanilla\AddonCronJobs;
use Vanilla\Dashboard\AutomationRules\Jobs\AutomationRuleJob;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Automation rule cron job
 */
class AutomationRuleCronJobs extends AddonCronJobs
{
    /**
     * @inheritdoc
     */
    protected function getCronJobDescriptors(): array
    {
        return [new CronJobDescriptor(AutomationRuleJob::class, AutomationRuleJob::getCronExpression())];
    }
}
