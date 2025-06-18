<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Community\Events;

use Garden\Events\TrackingEventInterface;
use Garden\Utils\ArrayUtils;
use Vanilla\Analytics\TrackableCommunityModel;
use Vanilla\Analytics\TrackableDateUtils;
use Vanilla\Analytics\TrackableUserModel;
use Vanilla\Site\SiteSectionModel;

class DownloadEvent implements TrackingEventInterface
{
    const COLLECTION_NAME = "attachmentDownload";

    const ACTION_DOWNLOAD = "download";

    private string $action;

    private array $payload;

    /**
     * @param string $action
     * @param array $payload A normalized media row.
     */
    public function __construct(string $action, array $payload)
    {
        $this->action = $action;
        $this->setPayload(
            ArrayUtils::pluck($payload, [
                "mediaID",
                "name",
                "type",
                "url",
                "dateInserted",
                "insertUserID",
                "foreignType",
                "foreignID",
            ])
        );
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
     * Update the payload array.
     *
     * @param array $payload
     * @return void
     */
    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    /**
     * Update payload with associated record data if exists.
     *
     * @param TrackableCommunityModel $trackableCommunityModel
     * @param TrackableUserModel $trackableUserModel
     * @return array
     */
    public function getTrackablePayload(
        TrackableCommunityModel $trackableCommunityModel,
        TrackableUserModel $trackableUserModel
    ): array {
        $payload = $this->getPayload();
        if (strtolower($payload["foreignType"]) === "discussion") {
            $payload["discussion"] = $trackableCommunityModel->getTrackableDiscussion($payload["foreignID"]);
        }
        if (strtolower($payload["foreignType"]) === "comment") {
            $payload["comment"] = $trackableCommunityModel->getTrackableComment($payload["foreignID"]);
        }

        $payload["uploadUser"] = $trackableUserModel->getTrackableUser($payload["insertUserID"]);

        $payload["dateDownloaded"] = TrackableDateUtils::getDateTime();
        $payload["dateUploaded"] = TrackableDateUtils::getDateTime($payload["dateInserted"]);

        $payload["contentType"] = $payload["type"];

        return $payload;
    }

    /**
     * @inheritdoc
     */
    public function getSiteSectionID(): ?string
    {
        $siteSection = \Gdn::getContainer()
            ->get(SiteSectionModel::class)
            ->getCurrentSiteSection();

        return $siteSection->getSectionID() ?? null;
    }
}
