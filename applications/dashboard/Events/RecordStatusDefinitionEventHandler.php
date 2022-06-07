<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Models;

use Garden\EventManager;
use Garden\Web\Exception\NotFoundException;
use Vanilla\Dashboard\Events\DiscussionStatusDefinitionEvent;
use Vanilla\Dashboard\Events\IdeaStatusDefinitionEvent;
use Vanilla\Events\EventAction;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Http\InternalClient;

/**
 * Handle events related to defining a record status, that is, a status assigned to a resource record.
 */
class RecordStatusDefinitionEventHandler
{
    //region Fields
    /** @var EventManager $eventManager */
    private $eventManager;

    /** @var InternalClient $apiClient */
    private $apiClient;
    //endregion

    //region Constructor
    /**
     * DI Constructor
     *
     * @param EventManager $eventManager
     * @param InternalClient $apiClient
     */
    public function __construct(EventManager $eventManager, InternalClient $apiClient)
    {
        $this->eventManager = $eventManager;
        $this->apiClient = $apiClient;
    }
    //endregion

    //region Public Methods
    /**
     * Handle events triggered when creating, updating or deleting an ideation-specific status
     *
     * @param IdeaStatusDefinitionEvent $event Event to handle
     */
    public function handleIdeaStatusDefinitionEvent(IdeaStatusDefinitionEvent $event): void
    {
        switch ($event->getAction()) {
            case EventAction::ADD:
                $this->handleIdeaStatusAddition($event);
                break;
            case EventAction::UPDATE:
                $this->handleIdeaStatusUpdate($event);
                break;
            case EventAction::DELETE:
                $this->handleIdeaStatusDelete($event);
                break;
            default:
                break;
        }
    }
    //endregion

    //region Non-Public Methods

    /**
     * Handle event triggered when creating/inserting/adding an ideation-specific status.
     *
     * @param IdeaStatusDefinitionEvent $event Idea Status Added Event to handle
     */
    private function handleIdeaStatusAddition(IdeaStatusDefinitionEvent $event): void
    {
        $ideaStatus = $event->getPayload();
        if (empty($ideaStatus["recordStatusID"])) {
            // Create the corresponding discussion-specific status that relates to ideation
            $defaults = ["recordType" => "discussion", "recordSubtype" => "ideation", "isSystem" => 0];
            $recordStatus = array_merge($ideaStatus, $defaults);
            $recordStatus["state"] = lcfirst($recordStatus["state"]);

            $this->apiClient->setUserID($event->getUserID());
            $response = $this->apiClient->post("/api/v2/discussions/statuses", $recordStatus);

            if ($response->isSuccessful()) {
                //Fire a new discussion status inserted event that references the added idea status' ID
                $discussionStatus = $response->getBody();
                $discussionStatusID = intval($discussionStatus["statusID"]);
                $ideaStatusID = $event->getID();
                $addEvent = new DiscussionStatusDefinitionEvent(
                    EventAction::ADD,
                    $discussionStatusID,
                    $discussionStatus,
                    $event->getUserID(),
                    $ideaStatusID
                );
                $this->eventManager->dispatch($addEvent);
            }
        }
    }

    /**
     * Handle event triggered when updating/modifiying an ideation-specific status.
     *
     * @param IdeaStatusDefinitionEvent $event Idea Status Update event to handle
     */
    private function handleIdeaStatusUpdate(IdeaStatusDefinitionEvent $event): void
    {
        if (is_numeric($event->getForeignID())) {
            $payload = $event->getPayload();
            if (!empty($payload["name"]) || !empty($payload["state"]) || array_key_exists("isDefault", $payload)) {
                // Ideation states have initial uppercase letter while discussion statuses have initial lowercase letter
                if (!empty($payload["state"])) {
                    $payload["state"] = lcfirst($payload["state"]);
                }
                unset($payload["statusID"]);

                $path = "/api/v2/discussions/statuses/{$event->getForeignID()}";
                $this->apiClient->setUserID($event->getUserID());
                $getResponse = $this->apiClient->get($path);
                if ($getResponse->isSuccessful()) {
                    // Retain only those keys from the payload that are included in an API GET request
                    $current = (array) $getResponse->getBody();
                    $payload = array_intersect_key($payload, $current);
                    // Retain only those keys from the current row that are included in the event payload
                    $current = array_intersect_key($current, $payload);
                    // Determine whether there are any differences in the payload that must be persisted
                    $update = array_diff($payload, $current);
                    if (!empty($update)) {
                        $this->apiClient->patch($path, $update);
                    }
                }
            }
        }
    }

    /**
     * Handle event triggered when deleting an ideation-specific status.
     *
     * @param IdeaStatusDefinitionEvent $event Idea Status Delete event to handle
     */
    private function handleIdeaStatusDelete(IdeaStatusDefinitionEvent $event): void
    {
        if (is_numeric($event->getForeignID())) {
            $path = "/api/v2/discussions/statuses/{$event->getForeignID()}";
            try {
                $this->apiClient->setUserID($event->getUserID());
                $getResponse = $this->apiClient->get($path);
                if ($getResponse->isSuccessful()) {
                    $this->apiClient->delete($path);
                }
            } catch (NoResultsException | NotFoundException $ex) {
                //Don't care - record is gone
            }
        }
    }
    //endregion
}
