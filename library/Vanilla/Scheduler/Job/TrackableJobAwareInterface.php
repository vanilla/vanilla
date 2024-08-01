<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * For tracking a job.
 */
interface TrackableJobAwareInterface
{
    /**
     * Set the userID that the job is being tracked for.
     *
     * @param int $userID The userID to track the job for.
     *
     * @return void
     */
    public function setTrackingUserID(int $userID): void;

    /**
     * Get a userID that the job is being tracked for.
     *
     * @return int|null
     */
    public function getTrackingUserID(): ?int;

    /**
     * Get trackingID for the job.
     *
     * @return string|null
     */
    public function getTrackingID(): ?string;

    /**
     * Set trackingID for the job.
     *
     * @param string $trackingID
     */
    public function setTrackingID(string $trackingID): void;
}
