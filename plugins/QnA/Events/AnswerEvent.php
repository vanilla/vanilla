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

    public function __construct(string $action, array $payload, $sender = null)
    {
        parent::__construct($action, $payload, $sender);
        $this->addApiParams(["expand" => "crawl,roles,vectorize"]);
        $this->type = "answer";
        $this->payload["recordType"] = "comment";
        $this->payload["recordID"] = $payload["answer"]["commentID"];
    }

    /**
     * @return string
     */
    public function getBaseAction(): string
    {
        return ResourceEvent::ACTION_UPDATE;
    }

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
        $trackingData["answer"]["body"] = null;
        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.
        if (isset($this->payload["answer"]["siteSectionIDs"])) {
            $trackingData["siteSectionID"] = $this->payload["answer"]["siteSectionIDs"][0];
        }
        return $trackingData;
    }

    /**
     * {@inheritdoc}
     */
    public function getTrackableAction(): string
    {
        return "answer_{$this->getAction()}";
    }

    /**
     * {@inheritdoc}
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        if (isset($this->payload["answer"]["siteSectionIDs"])) {
            return $this->payload["answer"]["siteSectionIDs"][0];
        }
        return null;
    }

    /**
     * Create a normalized variation of the record's payload.
     *
     * @return array A tuple of [string, int]
     */
    public function getRecordTypeAndID(): array
    {
        $recordType = "comment";

        if ($idKey = $this->payload["documentIdField"] ?? false) {
            $idKey = $this->$idKey;
        } else {
            $idKey = $recordType . "ID";
        }

        $payloadRecord = $this->payload[$this->type] ?? $this->payload;
        $recordID = $payloadRecord["recordID"] ?? ($payloadRecord[$idKey] ?? null);

        return [$recordType, $recordID];
    }
}
