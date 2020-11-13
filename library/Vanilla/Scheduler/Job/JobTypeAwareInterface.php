<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Interface JobTypeAwareInterface
 */
interface JobTypeAwareInterface {

    /**
     * Set Job Type
     *
     * @param string $jobType
     * @return mixed
     */
    public function setJobType(string $jobType);
}
