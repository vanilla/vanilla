<?php
/**
 * @author Andrew Keller <akeller@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Garden\PsrEventHandlersInterface;
use Gdn;
use Gdn_Session;
use UserDiscussionModel;
use Vanilla\Community\Events\PageViewEvent;

class PageViewEventHandler implements PsrEventHandlersInterface
{
    /**
     * DI.
     */
    public function __construct(
        private \DiscussionModel $discussionModel,
        private UserDiscussionModel $userDiscussionModel,
        private Gdn_Session $session
    ) {
    }

    /**
     * @inheritdoc
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
            // Record a userDiscussion row if the user is logged in.
            if ($this->session->isValid()) {
                $this->userDiscussionModel->setWatch($discussionID);
            }
        }
        return $event;
    }
}
