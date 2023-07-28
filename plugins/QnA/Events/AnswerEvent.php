<?php
/**
 * @author David Barbier <david.barbier@vanillaforums.com>
 * @copyright 2009-2020 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Events;

use Garden\Events\ResourceEvent;
use Garden\Events\TrackingEventInterface;
use Vanilla\Analytics\TrackableCommunityModel;

/**
 * Represent a Answer resource event.
 */
class AnswerEvent extends ResourceEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "qna";
    const ACTION_ANSWER_ACCEPTED = "accepted";
    const ACTION_ANSWER_REJECTED = "rejected";

    /**
     * Create a payload suitable for tracking.
     *
     * @param TrackableCommunityModel $trackableCommunity
     *
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        $trackingData = $trackableCommunity->getTrackableComment($this->getPayload()["answer"]);

        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.
        if (isset($this->payload["answer"]["siteSectionIDs"])) {
            $trackingData["siteSectionID"] = $this->payload["answer"]["siteSectionIDs"][0];
        }
        return $trackingData;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableAction(): string
    {
        return "answer_{$this->getAction()}";
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        if (isset($this->payload["answer"]["siteSectionIDs"])) {
            return $this->payload["answer"]["siteSectionIDs"][0];
        }
        return null;
    }
}
