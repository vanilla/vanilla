<?php
/**
 * @copyright 2009-2022 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\Models;

use Garden\EventManager;
use Garden\Events\ResourceEvent;
use Garden\Events\EventFromRowInterface;
use Garden\Schema\Schema;
use Vanilla\QnA\Events\AnswerEvent;
use Vanilla\Formatting\DateTimeFormatter;
use Gdn;
use CategoryModel;
use ReactionModel;
use CommentModel;

/**
 * Class AnswerModel
 */
class AnswerModel implements EventFromRowInterface
{
    /** @var CommentModel */
    private $commentModel;

    /** @var bool | array  */
    private $Reactions = false;

    /** @var EventManager */
    private $eventManager;

    /**
     * AnswerModel constructor.
     *
     * @param CommentModel $commentModel
     * @param EventManager $eventManager
     */
    public function __construct(CommentModel $commentModel, EventManager $eventManager)
    {
        $this->commentModel = $commentModel;
        $this->eventManager = $eventManager;
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
