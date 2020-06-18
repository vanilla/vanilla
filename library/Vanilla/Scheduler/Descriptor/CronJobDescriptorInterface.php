<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Descriptor;

/**
 * Interface CronDescriptorInterface
 */
interface CronJobDescriptorInterface extends JobDescriptorInterface {

    /**
     * Returns a crontab like schedule definition. EX: "* * * * *"
     *
     * @return string
     */
    public function getSchedule(): string;
}
