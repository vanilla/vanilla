<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Addon;

use CommentModel;
use Garden\PsrEventHandlersInterface;
use Garden\Web\Exception\NotFoundException;
use Gdn;
use DiscussionModel;
use RoleModel;
use UserModel;
use Vanilla\Community\Events\TrackableDiscussionAnalyticsEvent;
use Vanilla\Dashboard\Models\AiSuggestionSourceService;

/**
 * Event handlers for adding zoom meeting info related to events.
 */
class QnaEventHandler implements PsrEventHandlersInterface
{
    private CommentModel $commentModel;

    private UserModel $userModel;

    private AiSuggestionSourceService $aiSuggestionSourceService;

    /**
     * D.I.
     *
     * @param CommentModel $commentModel
     * @param UserModel $userModel
     * @param AiSuggestionSourceService $aiSuggestionSourceService
     */
    public function __construct(
        CommentModel $commentModel,
        UserModel $userModel,
        AiSuggestionSourceService $aiSuggestionSourceService
    ) {
        $this->commentModel = $commentModel;
        $this->userModel = $userModel;
        $this->aiSuggestionSourceService = $aiSuggestionSourceService;
    }

    /**
     * @inheritDoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleTrackableDiscussionAnalyticsEvent"];
    }

    /**
     * Add Qna data to trackable discussion object.
     *
     * @param TrackableDiscussionAnalyticsEvent $event
     * @return void
     * @throws NotFoundException
     */
    public function handleTrackableDiscussionAnalyticsEvent(
        TrackableDiscussionAnalyticsEvent $event
    ): TrackableDiscussionAnalyticsEvent {
        $discussion = $event->getDiscussion();
        $acceptedComments = $this->commentModel
            ->getWhere([
                "DiscussionID" => $discussion["discussionID"],
                "QnA" => "Accepted",
            ])
            ->resultArray();
        if (count($acceptedComments) > 0) {
            $roleIDs = $this->userModel->getRoleIDsByUserIDs(array_column($acceptedComments, "InsertUserID"));
            if (count($roleIDs) > 0) {
                $roleModel = Gdn::getContainer()->get(RoleModel::class);
                $roles = $roleModel->getWhere(["RoleID" => array_column($roleIDs, 0)])->resultArray();
                $discussion["acceptedUserRoles"] = array_column($roles, "Name");
            }
        }
        $event->setDiscussion($discussion);
        return $event;
    }

    /**
     * @param DiscussionModel $sender
     * @param array $args
     * @return void
     */
    public function discussionModel_afterSaveDiscussion_handler(DiscussionModel $sender, array $args)
    {
        if ($args["Insert"] === true && ($args["DiscussionData"]["Type"] ?? "") == "Question") {
            $discussionID = val("DiscussionID", $sender->EventArguments, 0);
            $this->aiSuggestionSourceService->createAttachment($discussionID, (array) $args["Discussion"]);
        }
    }

    /**
     * Mark Comment as accepted answer
     *
     * @param int $commentID
     * @return array
     */
    public function commentModel_markAccepted_handler(int $commentID)
    {
        $qnaPlugin = Gdn::getContainer()->get(\QnAPlugin::class);
        $commentsApiController = Gdn::getContainer()->get(\CommentsApiController::class);
        return $qnaPlugin->commentsApiController_patch_answer($commentsApiController, $commentID, [
            "status" => "accepted",
        ]);
    }
}
