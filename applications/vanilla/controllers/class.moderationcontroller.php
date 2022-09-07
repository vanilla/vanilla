<?php
/**
 * Moderation controller
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Garden\Web\Exception\PartialCompletionException;
use Garden\Web\Exception\ServerException;
use Vanilla\Scheduler\LongRunner;
use Vanilla\Scheduler\LongRunnerAction;
use Webmozart\Assert\Assert;

/**
 * Handles content moderation via /moderation endpoint.
 */
class ModerationController extends VanillaController
{
    use \Vanilla\Web\TwigRenderTrait;

    /** @var \Garden\EventManager */
    private $eventManager;

    /**
     * ModerationController constructor.
     *
     * @param \Garden\EventManager $eventManager
     */
    public function __construct(\Garden\EventManager $eventManager)
    {
        $this->eventManager = $eventManager;
        parent::__construct();
    }

    /**
     * Looks at the user's attributes and form postback to see if any comments
     * have been checked for administration, and if so, puts an inform message on
     * the screen to take action.
     */
    public function checkedComments()
    {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        ModerationController::informCheckedComments($this);
        $this->render();
    }

    /**
     * Looks at the user's attributes and form postback to see if any discussions
     * have been checked for administration, and if so, puts an inform message on
     * the screen to take action.
     */
    public function checkedDiscussions()
    {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        ModerationController::informCheckedDiscussions($this);
        $this->render();
    }

    /**
     * Looks at the user's attributes and form postback to see if any comments
     * have been checked for administration, and if so, adds an inform message to
     * $sender to take action.
     *
     * @param Gdn_Controller $sender
     */
    public static function informCheckedComments($sender)
    {
        $session = Gdn::session();
        $hadCheckedComments = false;
        $transientKey = val("TransientKey", $_POST);
        if ($session->isValid() && $session->validateTransientKey($transientKey)) {
            // Form was posted, so accept changes to checked items.
            $discussionID = val("DiscussionID", $_POST, 0);
            $checkIDs = val("CheckIDs", $_POST);
            if (empty($checkIDs)) {
                $checkIDs = [];
            }
            $checkIDs = (array) $checkIDs;

            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedComments", []);
            if (!is_array($checkedComments)) {
                $checkedComments = [];
            }

            if (!array_key_exists($discussionID, $checkedComments)) {
                $checkedComments[$discussionID] = [];
            } else {
                // Were there checked comments in this discussion before the form was posted?
                $hadCheckedComments = count($checkedComments[$discussionID]) > 0;
            }
            foreach ($checkIDs as $check) {
                if (val("checked", $check)) {
                    if (!arrayHasValue($checkedComments, $check["checkId"])) {
                        $checkedComments[$discussionID][] = $check["checkId"];
                    }
                } else {
                    removeValueFromArray($checkedComments[$discussionID], $check["checkId"]);
                }
            }

            if (count($checkedComments[$discussionID]) == 0) {
                unset($checkedComments[$discussionID]);
            }

            Gdn::userModel()->saveAttribute($session->User->UserID, "CheckedComments", $checkedComments);
        } elseif ($session->isValid()) {
            // No form posted, just retrieve checked items for display
            $discussionID = property_exists($sender, "DiscussionID") ? $sender->DiscussionID : 0;
            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedComments", []);
            if (!is_array($checkedComments)) {
                $checkedComments = [];
            }
        }

        // Retrieve some information about the checked items
        $countDiscussions = count($checkedComments);
        $countComments = 0;
        foreach ($checkedComments as $discID => $comments) {
            if ($discID == $discussionID) {
                $countComments += count($comments); // Sum of comments in this discussion
            }
        }
        if ($countComments > 0) {
            $selectionMessage = wrap(
                sprintf(
                    t('You have selected %1$s in this discussion.'),
                    plural($countComments, "%s comment", "%s comments")
                ),
                "div"
            );
            $actionMessage = t("Take Action:");

            // Can the user delete the comment?
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussionID);
            if (CategoryModel::checkPermission(val("CategoryID", $discussion), "Vanilla.Comments.Delete")) {
                $actionMessage .=
                    " " . anchor(t("Delete"), "moderation/confirmcommentdeletes/" . $discussionID, "Delete Popup");
            }

            $sender->EventArguments["SelectionMessage"] = &$selectionMessage;
            $sender->EventArguments["ActionMessage"] = &$actionMessage;
            $sender->EventArguments["Discussion"] = $discussion;
            $sender->fireEvent("BeforeCheckComments");
            $actionMessage .=
                " " .
                anchor(
                    t("Cancel"),
                    "moderation/clearcommentselections/" . $discussionID . "/{TransientKey}/?Target={SelfUrl}",
                    "CancelAction"
                );

            $sender->informMessage($selectionMessage . wrap($actionMessage, "div", ["class" => "Actions"]), [
                "CssClass" => "NoDismiss",
                "id" => "CheckSummary",
            ]);
        } elseif ($hadCheckedComments) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $sender->informMessage("", ["id" => "CheckSummary"]);
        }
    }

    /**
     * Looks at the user's attributes and form postback to see if any discussions
     * have been checked for administration, and if so, adds an inform message to
     * $sender to take action.
     *
     * @var Gdn_Controller $sender
     * @var bool $force
     */
    public static function informCheckedDiscussions($sender, $force = false)
    {
        $session = Gdn::session();
        $hadCheckedDiscussions = $force;
        if ($session->isValid() && Gdn::request()->isAuthenticatedPostBack()) {
            // Form was posted, so accept changes to checked items.
            $checkIDs = val("CheckIDs", $_POST);
            if (empty($checkIDs)) {
                $checkIDs = [];
            }
            $checkIDs = (array) $checkIDs;

            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedDiscussions", []);
            if (!is_array($checkedDiscussions)) {
                $checkedDiscussions = [];
            }

            // Were there checked discussions before the form was posted?
            $hadCheckedDiscussions |= count($checkedDiscussions) > 0;

            foreach ($checkIDs as $check) {
                if (val("checked", $check)) {
                    if (!arrayHasValue($checkedDiscussions, $check["checkId"])) {
                        $checkedDiscussions[] = $check["checkId"];
                    }
                } else {
                    removeValueFromArray($checkedDiscussions, $check["checkId"]);
                }
            }

            Gdn::userModel()->saveAttribute($session->User->UserID, "CheckedDiscussions", $checkedDiscussions);
        } elseif ($session->isValid()) {
            // No form posted, just retrieve checked items for display
            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedDiscussions", []);
            if (!is_array($checkedDiscussions)) {
                $checkedDiscussions = [];
            }
        }

        // Retrieve some information about the checked items
        $countDiscussions = count($checkedDiscussions);
        if ($countDiscussions > 0) {
            $selectionMessage = wrap(
                sprintf(t('You have selected %1$s.'), plural($countDiscussions, "%s discussion", "%s discussions")),
                "div"
            );
            $actionMessage = t("Take Action:");
            $actionMessage .= " " . anchor(t("Delete"), "moderation/confirmdiscussiondeletes/", "Delete Popup");
            $actionMessage .= " " . anchor(t("Move"), "moderation/confirmdiscussionmoves/", "Move Popup");

            $sender->EventArguments["SelectionMessage"] = &$selectionMessage;
            $sender->EventArguments["ActionMessage"] = &$actionMessage;
            $sender->fireEvent("BeforeCheckDiscussions");
            $actionMessage .=
                " " .
                anchor(
                    t("Cancel"),
                    "moderation/cleardiscussionselections/{TransientKey}/?Target={SelfUrl}",
                    "CancelAction"
                );

            // Open a new one.
            $sender->informMessage($selectionMessage . wrap($actionMessage, "div", ["class" => "Actions"]), [
                "CssClass" => "NoDismiss",
                "id" => "CheckSummary",
            ]);
        } elseif ($hadCheckedDiscussions) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $sender->informMessage("", ["id" => "CheckSummary"]);
        }
    }

    /**
     * Remove all comments checked for administration from the user's attributes.
     *
     * @param int $discussionID
     * @param string $transientKey
     */
    public function clearCommentSelections($discussionID, $transientKey = "")
    {
        $session = Gdn::session();
        if ($session->validateTransientKey($transientKey)) {
            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedComments", []);
            unset($checkedComments[$discussionID]);
            Gdn::userModel()->saveAttribute($session->UserID, "CheckedComments", $checkedComments);
        }

        redirectTo(getIncomingValue("Target", "/discussions"));
    }

    /**
     * Remove all discussions checked for administration from the user's attributes.
     */
    public function clearDiscussionSelections($transientKey = "")
    {
        $session = Gdn::session();
        if ($session->validateTransientKey($transientKey)) {
            Gdn::userModel()->saveAttribute($session->UserID, "CheckedDiscussions", false);
        }

        redirectTo(getIncomingValue("Target", "/discussions"));
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * comments (and has permission to do so).
     *
     * @param int $discussionID
     */
    public function confirmCommentDeletes(int $discussionID)
    {
        $session = Gdn::session();
        $this->Form = new Gdn_Form();
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);
        if (!$discussion) {
            return;
        }

        // Verify that the user has permission to perform the delete
        $this->categoryPermission($discussion->CategoryID, "Vanilla.Comments.Delete");
        $this->title(t("Confirm"));

        $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedComments", []);
        if (!is_array($checkedComments)) {
            $checkedComments = [];
        }

        $commentIDs = [];
        $discussionIDs = [];
        foreach ($checkedComments as $discID => $comments) {
            foreach ($comments as $comment) {
                if (substr($comment, 0, 11) == "Discussion_") {
                    $discussionIDs[] = str_replace("Discussion_", "", $comment);
                } elseif ($discID == $discussionID) {
                    $commentIDs[] = str_replace("Comment_", "", $comment);
                }
            }
        }
        $countCheckedComments = count($commentIDs);
        $this->setData("CountCheckedComments", $countCheckedComments);

        if ($this->Form->authenticatedPostBack()) {
            // Delete the selected comments
            $commentModel = new CommentModel();
            foreach ($commentIDs as $commentID) {
                Assert::integerish($commentID);
                $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
                if ((int) $comment["DiscussionID"] === (int) $discussionID) {
                    // Make sure the comment is from the same discussion that was deleted.
                    // The user interface ensures this, but just in case it becomes not true due to a UX error let's not
                    // make this an error that the user may not be able to recover from.
                    $commentModel->deleteID($commentID);
                }
            }

            // Clear selections
            unset($checkedComments[$discussionID]);
            Gdn::userModel()->saveAttribute($session->UserID, "CheckedComments", $checkedComments);
            ModerationController::informCheckedComments($this);
            $this->setRedirectTo(discussionUrl($discussion));
        }

        $this->render();
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * discussions (and has permission to do so).
     */
    public function confirmDiscussionDeletes()
    {
        $session = Gdn::session();
        $this->Form = new Gdn_Form();
        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        $this->title(t("Confirm"));
        $checkedDiscussions = $this->Request->post("discussionIDs", null);

        if ($checkedDiscussions === null) {
            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedDiscussions", []);
        }

        if (!is_array($checkedDiscussions)) {
            $checkedDiscussions = [];
        } else {
            array_walk($checkedDiscussions, [Assert::class, "integerish"]);
        }

        $discussionIDs = [];
        foreach ($checkedDiscussions as $checkedDiscussion) {
            $discussionIDs[] = (int) $checkedDiscussion;
        }
        $countCheckedDiscussions = count($discussionIDs);
        $this->setData("CountCheckedDiscussions", $countCheckedDiscussions);

        $filteredDiscussions = $discussionModel->filterCategoryPermissions(
            $discussionIDs,
            "Vanilla.Discussions.Delete"
        );
        $this->setData("CountAllowed", count($filteredDiscussions));
        $countNotAllowed = $countCheckedDiscussions - count($filteredDiscussions);
        $this->setData("CountNotAllowed", $countNotAllowed);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            /** @var LongRunner $longRunner */
            $longRunner = \Gdn::getContainer()->get(LongRunner::class);
            $result = $longRunner->runImmediately(
                new LongRunnerAction(DiscussionModel::class, "deleteDiscussionsIterator", [$filteredDiscussions])
            );

            $successIDs = $result->getSuccessIDs();

            // Remove the checked ids.
            $remainingIDs = array_diff($filteredDiscussions, $successIDs);
            Gdn::userModel()->saveAttribute(Gdn::session()->UserID, "CheckedDiscussions", array_values($remainingIDs));

            // Delete the discussions from the client.
            foreach ($successIDs as $discussionID) {
                $this->jsonTarget("#Discussion_$discussionID", ["remove" => true], "SlideUp");
            }

            if (!$result->isCompleted()) {
                // We didn't finish. Inform the client to retry.
                $this->jsonTarget(
                    "",
                    [
                        "url" => url("/moderation/confirmdiscussiondeletes"),
                        "reprocess" => true,
                        "data" => [
                            "DeliveryType" => DELIVERY_TYPE_VIEW,
                            "DeliveryMethod" => DELIVERY_METHOD_JSON,
                            "discussionIDs" => array_values($remainingIDs),
                            "fork" => false,
                        ],
                    ],
                    "Ajax"
                );
                $this->title(t("Deleting..."));
                $this->setFormSaved(false);
                $this->jsonTarget(
                    "#Popup .Content",
                    $this->renderTwig("/applications/vanilla/views/moderation/progress.twig", $this->Data),
                    "Html"
                );
                $this->View = "progress";
            } else {
                $this->jsonTarget("!element", "", "closePopup");
                $this->setFormSaved(true);
            }

            ModerationController::informCheckedDiscussions($this, true);

            // Handle errors
            if ($error = $result->getCombinedErrorMessage()) {
                throw new ServerException($error, $result->asData()->getStatus(), $result->getExceptionsByID());
            }
        }
        $this->render();
    }

    /**
     * Form to ask for the destination of the move, confirmation and permission check.
     */
    public function confirmDiscussionMoves($discussionID = null)
    {
        /* @var \Gdn_Session $session */
        $session = Gdn::getContainer()->get(\Gdn_Session::class);
        $this->Form = new Gdn_Form();
        /** @var DiscussionModel $discussionModel */
        $discussionModel = Gdn::getContainer()->get(DiscussionModel::class);
        /** @var CategoryModel $categoryModel */
        $categoryModel = Gdn::getContainer()->get(CategoryModel::class);
        $this->title(t("Confirm"));

        if ($discussionID) {
            $checkedDiscussions = (array) $discussionID;
            $discussion = $discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);
            $this->setData("CategoryID", $discussion["CategoryID"]);
            $this->setData("DiscussionType", $discussion["Type"]);
        } else {
            $checkedDiscussions = $this->Request->post("discussionIDs", null);

            if ($checkedDiscussions === null) {
                $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedDiscussions", []);
            }

            if (!is_array($checkedDiscussions)) {
                $checkedDiscussions = [];
            }
        }

        $discussionIDs = $checkedDiscussions;
        $countCheckedDiscussions = count($discussionIDs);
        $this->setData("CountCheckedDiscussions", $countCheckedDiscussions);

        // fire event
        $this->EventArguments["select"] = [
            "DiscussionID",
            "Name",
            "Type",
            "DateLastComment",
            "CategoryID",
            "CountComments",
        ];
        $this->fireEvent("beforeDiscussionMoveSelect", $this->EventArguments);

        // Check for edit permissions on each discussion
        $allowedDiscussions = [];

        $discussionData = $discussionModel->SQL
            ->select($this->EventArguments["select"])
            ->from("Discussion")
            ->whereIn("DiscussionID", $discussionIDs)
            ->get();

        $discussionData = Gdn_DataSet::index($discussionData->resultArray(), ["DiscussionID"]);
        foreach ($discussionData as $discussionID => $discussion) {
            $category = CategoryModel::categories($discussion["CategoryID"]);
            if (!array_key_exists("DiscussionType", $this->Data) && !is_null($discussion["Type"])) {
                $this->setData("DiscussionType", $discussion["Type"]);
                $this->setData("CategoryID", $category["CategoryID"]);
            }
            if ($category && CategoryModel::checkPermission($category, "Vanilla.Discussions.Edit")) {
                $allowedDiscussions[] = $discussionID;
            }
        }
        $this->setData("CountAllowed", count($allowedDiscussions));
        $countNotAllowed = $countCheckedDiscussions - count($allowedDiscussions);
        $this->setData("CountNotAllowed", $countNotAllowed);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            // Retrieve the category id
            $categoryID = $this->Form->getFormValue("CategoryID");
            $this->Form->validateRule("CategoryID", "function:ValidateRequired", "Category is required");
            $this->Form->setValidationResults($categoryModel->validationResults());
            if ($this->Form->errorCount() === 0) {
                $redirectLink = $this->Form->getFormValue("RedirectLink");
                // Iterate and move.
                /** @var LongRunner $longRunner */
                $longRunner = Gdn::getContainer()->get(LongRunner::class);

                $result = $longRunner->runImmediately(
                    new LongRunnerAction(DiscussionModel::class, "moveDiscussionsIterator", [
                        $allowedDiscussions,
                        $categoryID,
                        (bool) $redirectLink,
                    ])
                );

                $remainingIDs = array_diff($allowedDiscussions, $result->getSuccessIDs(), $result->getFailedIDs());
                // for the old mechanism.
                Gdn::userModel()->saveAttribute($session->UserID, "CheckedDiscussions", array_values($remainingIDs));
                if (!empty($remainingIDs)) {
                    $this->jsonTarget(
                        "",
                        [
                            "url" => url("/moderation/confirmdiscussionmoves"),
                            "reprocess" => true,
                            "data" => [
                                "DeliveryType" => DELIVERY_TYPE_VIEW,
                                "DeliveryMethod" => DELIVERY_METHOD_JSON,
                                "CategoryID" => $categoryID,
                                "RedirectLink" => $this->Form->getFormValue("RedirectLink") ? 1 : 0,
                                "discussionIDs" => array_values($remainingIDs),
                                "fork" => false,
                            ],
                        ],
                        "Ajax"
                    );
                    $this->title(t("Moving..."));
                    $this->setFormSaved(false);
                    $this->jsonTarget(
                        "#Popup .Content",
                        $this->renderTwig("/applications/vanilla/views/moderation/progress.twig", $this->Data),
                        "Html"
                    );
                    $this->View = "progress";

                    // Handle errors
                    if ($error = $result->getCombinedErrorMessage()) {
                        throw new ServerException($error, $result->asData()->getStatus(), $result->getExceptionsByID());
                    }
                } else {
                    ModerationController::informCheckedDiscussions($this);
                    if ($this->Form->errorCount() == 0) {
                        $this->setFormSaved(true);
                        $this->jsonTarget("", "", "Refresh");
                    }
                }
            }
        }
        $this->render();
    }
}
