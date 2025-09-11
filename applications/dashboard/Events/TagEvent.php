<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Garden\Events\TrackingEventInterface;
use Gdn;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Site\SiteSectionModel;

/**
 * Class representing a Tag event.
 */
class TagEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "tag";

    /** @var string */
    protected $action;

    /** @var array */
    protected $payload;

    /** @var array */
    protected $sender;

    /** @var string */
    protected $type;

    /**
     * Construct event.
     *
     * @param array $tagData
     * @param array $recordTagData
     * @param int $recordID
     * @param string $recordType
     * @param array $record
     * @param null $sender
     */
    public function __construct(
        array $tagData,
        array $recordTagData,
        int $recordID,
        string $recordType,
        array $record,
        $sender = null
    ) {
        $this->action = "add";
        $this->type = self::COLLECTION_NAME;
        $payload["tag"] = $tagData;
        $payload["record"] = $record;
        $payload["recordTag"] = $recordTagData;
        $payload["recordID"] = $recordID;
        $payload["recordType"] = $recordType;

        $this->payload = $payload;
    }

    /**
     * @inheritdoc
     */
    public function getTrackableCollection(): ?string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * @inheritdoc
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @inheritdoc
     */
    public function getPayload(): ?array
    {
        return $this->payload;
    }

    /**
     * Set the event payload.
     *
     * @param array $payload The key => value pairs to set on the payload.
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Get a trackable event payload.
     *
     * @param TrackableCommunityModel $trackableCommunity
     * @return array
     */
    public function getTrackablePayload(TrackableCommunityModel $trackableCommunity): array
    {
        $payload = $this->getPayload();
        $trackingData = [
            "tag" => $trackableCommunity->getTrackableTag($payload["tag"]),
            "recordTag" => $trackableCommunity->getTrackableRecordTag($payload["recordTag"]),
            "recordID" => $payload["recordID"],
            "recordType" => $payload["recordType"],
            "record" => $payload["record"],
        ];

        // We don't want to send the body of a post to the analytics service.
        $trackingData["record"]["body"] = null;

        switch ($trackingData["recordType"]) {
            case "discussion":
            case "idea":
            case "question":
                $trackingData["discussion"] = $trackableCommunity->getTrackableDiscussion(
                    $this->getPayload()["record"]
                );
                $trackingData["category"] =
                    $trackableCommunity->getTrackableCategory($trackingData["discussion"]["categoryID"]) ?? [];

                break;
        }

        return $trackingData;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        $siteSection = Gdn::getContainer()
            ->get(SiteSectionModel::class)
            ->getCurrentSiteSection();

        return $siteSection->getSectionID() ?? null;
    }
}
