<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla;

use Garden\EventHandlersInterface;
use Vanilla\Scheduler\Descriptor\CronJobDescriptor;
use Vanilla\Scheduler\SchedulerInterface;

/**
 * Utility for registering crons jobs for an addon.
 */
abstract class AddonCronJobs implements EventHandlersInterface
{
    /** @var SchedulerInterface */
    private $scheduler;

    /**
     * DI.
     *
     * @param SchedulerInterface $scheduler
     */
    public function __construct(SchedulerInterface $scheduler)
    {
        $this->scheduler = $scheduler;
    }

    /**
     * Get an array of cron job desciptors for the addon.
     *
     * @return CronJobDescriptor[]
     */
    abstract protected function getCronJobDescriptors(): array;

    /**
     * Event handler for registering cron jobs.
     */
    public function cron_trigger_event_handler()
    {
        foreach ($this->getCronJobDescriptors() as $cronJobDescriptor) {
            $this->scheduler->addJobDescriptor($cronJobDescriptor);
        }
    }
}
