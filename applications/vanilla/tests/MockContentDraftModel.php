<?php
namespace VanillaTests\Forum;

use Garden\Web\Exception\ClientException;
use Vanilla\Models\ContentDraftModel;
use Vanilla\Scheduler\TrackingSlipInterface;

class MockContentDraftModel extends ContentDraftModel
{
    /**
     * @param int $scheduleID
     * @return TrackingSlipInterface
     */
    public function publishScheduledDraftsAction(int $scheduleID): TrackingSlipInterface
    {
        throw new ClientException("Something went wrong"); // This is a mock method
    }
}
