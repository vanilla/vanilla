<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\Addon;

use CommentModel;
use DiscussionModel;
use Garden\Events\ResourceEvent;
use Garden\PsrEventHandlersInterface;
use Vanilla\Community\Events\CommentEvent;
use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Dashboard\Models\UserMentionsModel;

/**
 * Process UserMention when a post is Inserted/Updated/Deleted.
 */
class UserMentionsEventHandler implements PsrEventHandlersInterface
{
    /** @var UserMentionsModel */
    private $userMentionModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CommentModel */
    private $commentModel;

    /**
     * UserMentionEventHandler Constructor.
     *
     * @param UserMentionsModel $userMentionModel
     * @param DiscussionModel $discussionModel
     * @param CommentModel $commentModel
     */
    public function __construct(
        UserMentionsModel $userMentionModel,
        DiscussionModel $discussionModel,
        CommentModel $commentModel
    ) {
        $this->userMentionModel = $userMentionModel;
        $this->discussionModel = $discussionModel;
        $this->commentModel = $commentModel;
    }

    /**
     * {@inheritDoc}.
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleDiscussionEvent", "handleCommentEvent"];
    }

    /**
     * Remove existing UserMentions when a Discussion is deleted/updated.
     * Add userMentions when a Discussion is inserted/updated.
     *
     * @param DiscussionEvent $event
     * @return DiscussionEvent
     */
    public function handleDiscussionEvent(DiscussionEvent $event): DiscussionEvent
    {
        $action = $event->getAction();
        $discussion = $event->getPayload()["discussion"];
        if ($action === ResourceEvent::ACTION_DELETE || $action === ResourceEvent::ACTION_UPDATE) {
            $this->userMentionModel->deleteByRecordID($discussion["discussionID"], $this->userMentionModel::DISCUSSION);
        }

        if ($action === ResourceEvent::ACTION_INSERT || $action === ResourceEvent::ACTION_UPDATE) {
            $discussionBody = $this->discussionModel->getID($discussion["discussionID"], DATASET_TYPE_ARRAY)["Body"];
            $userMentions = $this->userMentionModel->parseMentions($discussionBody, $discussion["format"]);

            foreach ($userMentions as $userMention) {
                $this->discussionModel->insertUserMentions($userMention, $discussion);
            }
        }

        return $event;
    }

    /**
     * Remove existing UserMentions when a Comment is deleted/updated.
     * Add userMentions when a Comment is inserted/updated.
     *
     * @param CommentEvent $event
     * @return CommentEvent
     */
    public function handleCommentEvent(CommentEvent $event): CommentEvent
    {
        $action = $event->getAction();
        $comment = $event->getPayload()["comment"];
        if ($action === ResourceEvent::ACTION_DELETE || $action === ResourceEvent::ACTION_UPDATE) {
            $this->userMentionModel->deleteByRecordID($comment["commentID"], $this->userMentionModel::COMMENT);
        }

        if ($action === ResourceEvent::ACTION_INSERT || $action === ResourceEvent::ACTION_UPDATE) {
            $commentBody = $this->commentModel->getID($comment["commentID"], DATASET_TYPE_ARRAY)["Body"];
            $userMentions = $this->userMentionModel->parseMentions($commentBody, $comment["format"]);

            foreach ($userMentions as $userMention) {
                $this->commentModel->insertUserMentions($userMention, $comment);
            }
        }

        return $event;
    }
}
