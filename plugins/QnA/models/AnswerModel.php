<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Models;

use Exception;
use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Vanilla\Logging\ErrorLogger;
use Vanilla\Models\CommunityNotificationGenerator;
use Vanilla\QnA\Activity\MyQuestionAnswerAcceptedActivity;
use Vanilla\QnA\Events\AnswerEvent;
use Vanilla\Formatting\DateTimeFormatter;
use Gdn;
use CategoryModel;
use ReactionModel;
use CommentModel;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Ramsey\Uuid\Uuid;
use Vanilla\QnA\Activity\BookmarkedAnswerAcceptedActivity;
use DiscussionModel;
use UserModel;
use ActivityModel;
use Vanilla\Contracts\ConfigurationInterface;
use Vanilla\Scheduler\LongRunnerMultiAction;

/**
 * Class AnswerModel
 */
class AnswerModel implements EventFromRowInterface
{
    /** @var bool | array  */
    private $Reactions = false;

    /**
     * AnswerModel constructor.
     *
     * @param CommentModel $commentModel
     * @param EventManager $eventManager
     * @param CommunityNotificationGenerator $notificationGenerator
     * @param LongRunner $LongRunner
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     * @param ActivityModel $activityModel
     * @param ConfigurationInterface $configuration
     */
    public function __construct(
        private CommentModel $commentModel,
        private EventManager $eventManager,
        private CommunityNotificationGenerator $notificationGenerator,
        private LongRunner $LongRunner,
        private DiscussionModel $discussionModel,
        private UserModel $userModel,
        private ActivityModel $activityModel,
        private ConfigurationInterface $configuration
    ) {
    }

    /**
     * Given a database row, massage the data into a more externally-useful format.
     *
     * @param array $row
     * @return array
     */
    public function normalizeRow(array $row): array
    {
        $out = $this->commentModel->schema()->merge(Schema::parse(["qnA:s?", "dateAccepted:s?", "acceptedUserID:s?"]));
        // Preserve original attributes
        $attributes = $row["attributes"] ?? [];

        $validatedRow = $out->validate($row);

        $attributes["answer"] = [
            "status" => !empty($validatedRow["qnA"]) ? strtolower($validatedRow["qnA"]) : "pending",
            "dateAccepted" => $validatedRow["dateAccepted"] ?? null,
            "acceptUserID" => $validatedRow["acceptedUserID"] ?? null,
        ];
        $validatedRow["attributes"] = $attributes;

        return $validatedRow;
    }

    /**
     * Generate an event based on a database row, including an optional sender.
     *
     * @param array $row
     * @param string $action
     * @param array|object|null $sender
     * @return ResourceEvent
     */
    public function eventFromRow(array $row, string $action, $sender = null): ResourceEvent
    {
        $row = $this->commentModel->normalizeRow($row);
        $row = $this->normalizeRow($row);

        return new AnswerEvent($action, ["answer" => $row], $sender);
    }

    /**
     * Recount the amount of accepted answers for a user.
     *
     * @param string|int $userID User identifier
     */
    public function recalculateUserQnA($userID)
    {
        $countAcceptedAnswers = Gdn::sql()->getCount("Comment", ["InsertUserID" => $userID, "QnA" => "Accepted"]);
        Gdn::userModel()->setField($userID, "CountAcceptedAnswers", $countAcceptedAnswers);
    }

    /**
     * Notify users about an accepted answer.
     *
     * @param int $commentID
     * @throws Exception
     */
    private function notifyAnswerAccepted(int $commentID): void
    {
        // Create the notification action - the notification system will handle finding bookmarked users
        $action = $this->createAnswerAcceptedLongRunnerAction($commentID);

        if ($action) {
            $this->LongRunner->runDeferred($action);
        }
    }

    /**
     * Create a long runner action to notify users about the accepted answer.
     *
     * @param int $commentID The ID of the comment that is the accepted answer
     * @return LongRunnerAction|null
     * @throws Exception
     */
    public function createAnswerAcceptedLongRunnerAction(int $commentID): ?LongRunnerAction
    {
        // Get the comment details
        $comment = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

        // Error handling for missing comment
        if (!$comment) {
            ErrorLogger::warning(
                "Attempted to send notification for an accepted answer, but answer did not exist.",
                ["notifications"],
                [
                    "commentID" => $commentID,
                ]
            );
            return null;
        }

        // Check if the comment is _still_ an accepted answer
        if (empty($comment["QnA"]) || $comment["QnA"] !== "Accepted") {
            ErrorLogger::warning(
                "Attempted to send notification for an accepted answer, but comment was not marked as accepted.",
                ["notifications"],
                [
                    "commentID" => $commentID,
                    "comment" => $comment,
                ]
            );
            return null;
        }

        // Get the discussion details
        $discussionID = $comment["DiscussionID"];
        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
        $acceptedByUserID = $comment["AcceptedUserID"];

        // Error handling for missing discussion
        if (!$discussion) {
            ErrorLogger::warning(
                "Attempted to send notification for an accepted answer, but discussion did not exist.",
                ["notifications"],
                [
                    "discussionID" => $discussionID,
                    "answer" => $comment,
                ]
            );
            return null;
        }

        $discussionName = $discussion["Name"];

        $bookmarkActivity = [
            "ActivityType" => BookmarkedAnswerAcceptedActivity::getActivityTypeID(),
            "ActivityEventID" => str_replace("-", "", Uuid::uuid1()->toString()),
            "ActivityUserID" => $acceptedByUserID,
            "HeadlineFormat" => t(BookmarkedAnswerAcceptedActivity::getFullHeadline()),
            "RecordType" => "Discussion",
            "RecordID" => $discussionID,
            "Route" => "/discussion/comment/{$commentID}#Comment_{$commentID}",
            "Data" => [
                "Name" => $discussionName,
                "AcceptedBy" => $this->userModel->getFragmentByID($acceptedByUserID),
            ],
            "Ext" => [
                "Email" => [
                    "Format" => $comment["Format"] ?? ($comment["format"] ?? "Text"),
                    "Story" => $comment["Body"] ?? ($comment["body"] ?? ""),
                ],
            ],
        ];

        if (!$this->configuration->get("Vanilla.Email.FullPost")) {
            $bookmarkActivity["Ext"]["Email"] = $this->activityModel->setStoryExcerpt(
                $bookmarkActivity["Ext"]["Email"]
            );
        }

        $authorActivity = $bookmarkActivity;
        $authorActivity["ActivityType"] = MyQuestionAnswerAcceptedActivity::getActivityTypeID();
        $authorActivity["HeadlineFormat"] = MyQuestionAnswerAcceptedActivity::getFullHeadline();

        $discussionUserID = $discussion["InsertUserID"] ?? null;
        $groupData = [
            "notifyUserIDs" => [$discussionUserID],
            "preference" => MyQuestionAnswerAcceptedActivity::getPreference(),
        ];

        $actions = [
            new LongRunnerAction(CommunityNotificationGenerator::class, "processNotifications", [
                $authorActivity,
                MyQuestionAnswerAcceptedActivity::getActivityReason(),
                $groupData,
                $discussion["DiscussionID"],
            ]),
        ];

        // Send to all users who have bookmarked this question, using existing bookmark notification system
        $groupData = [
            "notifyUsersWhere" => [
                "Bookmarked" => true,
            ],
            "preference" => BookmarkedAnswerAcceptedActivity::getPreference(),
        ];

        $actions[] = new LongRunnerAction(CommunityNotificationGenerator::class, "processExpensiveNotifications", [
            $bookmarkActivity,
            BookmarkedAnswerAcceptedActivity::getActivityReason(),
            $groupData,
            $discussionID,
        ]);

        return new LongRunnerMultiAction($actions);
    }

    /**
     * Update a comment QnA data.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @param string|null $newQnA
     * @param \Gdn_Form|null $form
     *
     * @internal
     */
    public function updateCommentQnA($discussion, $comment, $newQnA, \Gdn_Form $form = null)
    {
        $currentQnA = val("QnA", $comment);

        if ($currentQnA != $newQnA) {
            $set = ["QnA" => $newQnA];

            if ($newQnA == "Accepted") {
                $set["DateAccepted"] = DateTimeFormatter::getCurrentDateTime();
                $set["AcceptedUserID"] = Gdn::session()->UserID;
            } else {
                $set["DateAccepted"] = null;
                $set["AcceptedUserID"] = null;
            }

            $commentID = $comment["CommentID"] ?? ($comment["commentID"] ?? false);

            $this->commentModel->setField($commentID, $set);
            $updatedAnswer = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

            if ($form) {
                $form->setValidationResults($this->commentModel->validationResults());
            }

            $this->applyCommentQnAChange($discussion, $updatedAnswer, $currentQnA, $newQnA);
        }
    }

    /**
     * Update a comment QnA data.
     *
     * @param array|object $discussion
     * @param array|object $updatedAnswer
     * @param string|null $currentQnAStatus
     * @param string|null $newQnAStatus
     *
     * @internal
     */
    public function applyCommentQnAChange($discussion, $updatedAnswer, $currentQnAStatus, $newQnAStatus)
    {
        // Determine QnA change
        $change = 0;
        if ($currentQnAStatus != $newQnAStatus) {
            $eventAction = AnswerEvent::ACTION_UPDATE;
            switch ($newQnAStatus) {
                case "Rejected":
                    $eventAction = AnswerEvent::ACTION_ANSWER_REJECTED;
                    $change = -1;
                    if ($currentQnAStatus != "Accepted") {
                        $change = 0;
                    }
                    break;

                case "Accepted":
                    $eventAction = AnswerEvent::ACTION_ANSWER_ACCEPTED;
                    $change = 1;

                    // Send notifications to users who have bookmarked this question
                    $this->notifyAnswerAccepted($updatedAnswer["commentID"] ?? $updatedAnswer["CommentID"]);
                    break;

                default:
                    if ($currentQnAStatus == "Rejected") {
                        $change = 0;
                    }
                    if ($currentQnAStatus == "Accepted") {
                        $change = -1;
                    }
                    break;
            }

            //Trigger the chosen answer event
            $answerEvent = $this->eventFromRow($updatedAnswer, $eventAction);
            $this->eventManager->dispatch($answerEvent);
        }

        $discussionInsertUserID = $discussion["InsertUserID"] ?? ($discussion["insertUserID"] ?? null);
        $updatedAnswerUserID = $updatedAnswer["InsertUserID"] ?? ($updatedAnswer["insertUserID"] ?? null);

        // Apply change effects
        if ($change && $discussionInsertUserID != null && $discussionInsertUserID != $updatedAnswerUserID) {
            // Update the user
            $userID = val("InsertUserID", $updatedAnswer);
            $this->recalculateUserQnA($userID);

            // Update reactions
            if ($this->Reactions) {
                include_once Gdn::controller()->fetchViewLocation("reaction_functions", "reactions", "dashboard");
                $reactionModel = new ReactionModel();

                // Assume that the reaction is done by the question's owner
                $questionOwner = $discussionInsertUserID;
                // If there's change, reactions will take care of it
                $reactionModel->react("Comment", $updatedAnswer["CommentID"], "AcceptAnswer", $questionOwner, true);
            } else {
                $nbsPoint = $change * (int) c("QnA.Points.AcceptedAnswer", 1);
                $categoryID = $discussion["CategoryID"] ?? ($discussion["categoryID"] ?? 0);
                if ($nbsPoint && c("QnA.Points.Enabled", false)) {
                    CategoryModel::givePoints($updatedAnswer["InsertUserID"], $nbsPoint, "QnA", $categoryID);
                }
            }
        }
    }
}
