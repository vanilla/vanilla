<?php
/**
 * @author Eduardo Garcia Julia <eduardo.garciajulia@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Interface JobTrackingIdAwareInterface.
 */
interface JobTrackingIdAwareInterface {

    /**
     * Set the job tracking Id
     *
     * @param string $trackingId
     * @return JobTrackingIdAwareInterface
     */
    public function setTrackingId(string $trackingId): JobTrackingIdAwareInterface;
}
