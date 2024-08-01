<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler\Job;

/**
 * Trait to implement TrackableJobAwareInterface.
 */
trait TrackableJobAwareTrait // Implements TrackableJobAwareInterface
{
    /** @var int|null */
    protected $trackingUserID = null;

    /** @var string|null */
    protected $trackingID = null;

    /**
     * Set the userID that the job is being tracked for.
     *
     * @param int|null $userID The userID to track the job for. Null makes this mirror the getTrackingUserID method.
     *
     * @return void
     */
    public function setTrackingUserID(?int $userID): void
    {
        $this->trackingUserID = $userID;
    }

    /**
     * Get a userID that the job is being tracked for.
     *
     * @return int|null
     */
    public function getTrackingUserID(): ?int
    {
        return $this->trackingUserID;
    }

    /**
     * Get trackingID for the job.
     *
     * @return string|null
     */
    public function getTrackingID(): ?string
    {
        return $this->trackingID;
    }

    /**
     * Set trackingID for the job.
     *
     * @param string|null $trackingID Null is to mirror the getter.
     */
    public function setTrackingID(?string $trackingID): void
    {
        $this->trackingID = $trackingID;
    }
}
