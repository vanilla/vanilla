<?php
/**
 * @author Richard Flynn <rflynn@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Garden\EventManager;
use Garden\PsrEventHandlersInterface;
use Gdn;
use TagModel;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Dashboard\Events\TagEvent;

/**
 * Dispatch tag events when handling other events.
 */
class TagEventHandlers implements PsrEventHandlersInterface
{
    private TagModel $tagModel;

    private EventManager $eventManager;

    /**
     * D.I.
     *
     * @param TagModel $tagModel
     * @param EventManager $eventManager
     */
    public function __construct(TagModel $tagModel, EventManager $eventManager)
    {
        $this->tagModel = $tagModel;
        $this->eventManager = $eventManager;
    }

    /**
     * @inheritdoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleDiscussionEvent"];
    }

    /**
     * If tags have been applied to a discussion, create and dispatch a TagEvent for each tag.
     *
     * @param DiscussionEvent $event
     * @return DiscussionEvent
     */
    public function handleDiscussionEvent(DiscussionEvent $event): DiscussionEvent
    {
        $payload = $event->getPayload();
        $tagIDs = $payload["tags"]["tagIDs"] ?? [];
        foreach ($tagIDs as $tagID) {
            $recordTag = [
                "tagID" => $tagID,
                "recordID" => $payload["discussion"]["discussionID"],
                "recordType" => $payload["discussion"]["type"],
                "dateInserted" =>
                    $payload["discussion"]["dateUpdated"] ??
                    ($payload["discussion"]["dateInserted"] ??
                        Gdn::getContainer()
                            ->get(\Vanilla\CurrentTimeStamp::class)
                            ->get()),
                "insertUserID" => Gdn::session()->UserID,
            ];

            $tag = (array) $this->tagModel->getID($tagID);
            $normalizedTag = $this->tagModel->normalizeOutput([$tag])[0];
            $tagEvent = new TagEvent(
                $normalizedTag,
                $recordTag,
                $payload["discussion"]["discussionID"],
                $payload["discussion"]["type"],
                $payload["discussion"],
                $event->getSender()
            );
            $this->eventManager->dispatch($tagEvent);
        }

        return $event;
    }
}
