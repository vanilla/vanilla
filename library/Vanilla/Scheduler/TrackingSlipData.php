<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Scheduler;

use Garden\Web\Data;

/**
 * Transform one or more tracking slips into an HTTP response.
 */
class TrackingSlipData extends Data
{
    /**
     * Constructor.
     *
     * @param TrackingSlipInterface[] $slips
     */
    public function __construct(TrackingSlipInterface ...$slips)
    {
        $slipData = [];
        foreach ($slips as $slip) {
            $slipData[] = [
                "jobTrackingID" => $slip->getTrackingID(),
                "jobID" => $slip->getID(),
                "jobExecutionStatus" => $slip->getStatus(),
            ];
        }

        $data = [
            "trackingSlips" => $slipData,
        ];

        parent::__construct($data, ["status" => 202], []);
    }
}
