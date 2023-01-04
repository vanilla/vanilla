<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

use Exception;
use Garden\Events\TrackingEventInterface;
use Gdn;
use Vanilla\Analytics\TrackableDateUtils;
use Vanilla\Analytics\TrackableUserModel;

/**
 * Represent a userPoint event. We consider this a type of user event, so this subclasses the UserEvent class.
 */
class UserPointEvent extends UserEvent implements TrackingEventInterface
{
    const ACTION_USERPOINT_ADD = "pointsGiven";
    const ACTION_USERPOINT_SUB = "pointsSubtracted";
    const COLLECTION_NAME = "point";

    /** @var string */
    public $collectionName;

    /**
     * Construct the UserBadgeEvent based on a UserEvent.
     *
     * @param UserEvent $userEvent
     * @param array $pointData
     * @throws Exception If no point value is set.
     */
    public function __construct(UserEvent $userEvent, array $pointData)
    {
        $payload = $userEvent->getPayload();

        if (!isset($pointData["value"])) {
            throw new Exception("Missing points value.");
        }

        if ($pointData["value"] >= 0) {
            $action = self::ACTION_USERPOINT_ADD;
        } else {
            $action = self::ACTION_USERPOINT_SUB;
        }
        $payload["point"] = $pointData;
        parent::__construct($action, $payload, $userEvent->getSender());
        $this->type = "user";
    }

    /**
     * Get the name of the collection this resource event belongs to.
     *
     * @return string
     */
    public function getTrackableCollection(): string
    {
        return self::COLLECTION_NAME;
    }

    /**
     * {@inhertiDoc}
     *
     * @return string
     */
    public function getTrackableAction(): string
    {
        switch ($this->getAction()) {
            case UserPointEvent::ACTION_USERPOINT_ADD:
                return "user_point_add";
            case UserPointEvent::ACTION_USERPOINT_SUB:
                return "user_point_remove";
            default:
                return $this->getAction();
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return array
     */
    public function getTrackablePayload(): array
    {
        $trackableUserModel = Gdn::getContainer()->get(TrackableUserModel::class);
        $trackingData = [
            "point" => [
                "categoryID" => $this->payload["point"]["categoryID"],
                "source" => $this->payload["point"]["source"],
                "user" => $trackableUserModel->getTrackableUser($this->payload["user"]["userID"]),
                "given" => [
                    "points" => $this->payload["point"]["value"],
                    "date" => TrackableDateUtils::getDateTime($this->payload["point"]["dateUpdated"]),
                ],
            ],
        ];

        // If the siteSectionID is set, we add it to the payload. We only send the first canonical one to keen.
        if (isset($this->payload["point"]["siteSectionIDs"])) {
            $trackingData["siteSectionID"] = $this->payload["point"]["siteSectionIDs"][0];
        }

        return $trackingData;
    }

    /**
     * {@inheritDoc}
     */
    protected function makeLogMessage(string $action, ?string $targetName, ?string $username): string
    {
        switch ($action) {
            case UserPointEvent::ACTION_USERPOINT_ADD:
                return "User {$username} score increased.";
            case UserPointEvent::ACTION_USERPOINT_SUB:
                return "User {$username} score decreased.";
            default:
                return "";
        }
    }

    /**
     * @inheritDoc
     */
    public function getSiteSectionID(): ?string
    {
        if (isset($this->payload["point"]["siteSectionIDs"])) {
            return $this->payload["point"]["siteSectionIDs"][0] ?? null;
        }
        return null;
    }
}
