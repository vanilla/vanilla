<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Garden\PsrEventHandlersInterface;
use Vanilla\Community\Events\PageViewEvent;

class PageViewEventHandler implements PsrEventHandlersInterface
{
    /** @var \DiscussionModel */
    private $discussionModel;

    /**
     * DI.
     *
     * @param \DiscussionModel $discussionModel
     */
    public function __construct(\DiscussionModel $discussionModel)
    {
        $this->discussionModel = $discussionModel;
    }

    /**
     * @inheritDoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleDiscussionViewEvent"];
    }

    /**
     * Increments discussion view counter if we have a discussionID and the event is a PageViewEvent
     *
     * @param PageViewEvent $event
     * @return PageViewEvent
     */
    public function handleDiscussionViewEvent(PageViewEvent $event): PageViewEvent
    {
        $payload = $event->getPayload();
        $discussionID = $payload["discussionID"] ?? null;
        if (!is_null($discussionID)) {
            $this->discussionModel->addView($discussionID);
        }
        return $event;
    }
}
