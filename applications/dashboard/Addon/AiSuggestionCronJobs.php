<?php

namespace Vanilla\Dashboard\Addon;

use AiSuggestionJob;
use Vanilla\AddonCronJobs;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;

/**
 * Cron jobs for AI Suggestion
 */
class AiSuggestionCronJobs extends AddonCronJobs
{
    /**
     * @inheritDoc
     */
    protected function getCronJobDescriptors(): array
    {
        return [new CronJobDescriptor(AiSuggestionJob::class, AiSuggestionJob::getCronExpression())];
    }
}
