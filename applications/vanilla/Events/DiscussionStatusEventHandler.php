<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Schema\ValidationException;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logging\ErrorLogger;

/**
 * Handle events dispatched when a discussion status is updated
 */
class DiscussionStatusEventHandler {

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var EventManager $eventManager */
    private $eventManager;

    /** @var \DiscussionModel $discussionModel */
    private $discussionModel;

    /** @var RecordStatusModel $recordStatusModel */
    private $recordStatusModel;

    /**
     * DI constructor
     *
     * @param EventManager $eventManager
     * @param \DiscussionModel $discussionModel
     * @param RecordStatusModel $recordStatusModel
     * @param LoggerInterface $logger
     */
    public function __construct(
        EventManager $eventManager,
        \DiscussionModel $discussionModel,
        RecordStatusModel $recordStatusModel,
        LoggerInterface $logger
    ) {
        $this->eventManager = $eventManager;
        $this->discussionModel = $discussionModel;
        $this->recordStatusModel = $recordStatusModel;
        $this->logger = $logger;
    }

    /**
     * Handle an event dispatched when a discussion's status is updated.
     *
     * @param DiscussionStatusEvent $discussionStatusEvent
     */
    public function handleDiscussionStatusEvent(DiscussionStatusEvent $discussionStatusEvent): void {
        $discussion = $this->discussionModel->getID($discussionStatusEvent->getDiscussionID(), DATASET_TYPE_ARRAY);
        if (empty($discussion)) {
            ErrorLogger::error("Discussion Not Found", ['recordStatus'], ['event' => $discussionStatusEvent]);
            return;
        }
        $event = $this->discussionModel->eventFromRow(
            $discussion,
            ResourceEvent::ACTION_UPDATE
        );

        $statusFragmentSchema = RecordStatusModel::getSchemaFragment();
        $status = [];
        try {
            $status = $this->recordStatusModel->selectSingle(['statusID' => $discussionStatusEvent->getStatusID()]);
            $statusFragment = $statusFragmentSchema->validate($status);

            $updatedPayload = $event->getPayload();
            $updatedPayload['status'] = (array)$statusFragment;

            $statusEvent = new DiscussionEvent($event->getAction(), $updatedPayload, $event->getSender());
            $this->eventManager->dispatch($statusEvent);
        } catch (NoResultsException $nre) {
            ErrorLogger::error("Discussion Status Not Found", ['recordStatus'], ['event' => $discussionStatusEvent]);
            return;
        } catch (ValidationException $e) {
            $context = ['event' => $discussionStatusEvent, 'status' => $status];
            ErrorLogger::error("Discussion Status Validation Failure", ['recordStatus'], $context);
        }
    }
}
