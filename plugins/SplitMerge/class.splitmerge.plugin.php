<?php
/**
 * SplitMerge plugin.
 *
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package SplitMerge
 */

use Vanilla\Community\Events\DiscussionEvent;
use Vanilla\Forum\Models\DiscussionMergeModel;
use Vanilla\Forum\Models\DiscussionSplitModel;
use Vanilla\Forum\Models\ForumAggregateModel;
use Vanilla\Scheduler\LongRunnerFailedID;
use Vanilla\Scheduler\LongRunnerSuccessID;
use Vanilla\Utility\ModelUtils;

/**
 * Class SplitMergePlugin
 */
class SplitMergePlugin extends Gdn_Plugin
{
    /**
     * @return ForumAggregateModel
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     */
    private function aggregateModel(): ForumAggregateModel
    {
        $aggregateModel = \Gdn::getContainer()->get(ForumAggregateModel::class);
        return $aggregateModel;
    }

    /**
     * Add "split" action link.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCheckComments_handler($sender)
    {
        $actionMessage = &$sender->EventArguments["ActionMessage"];
        $discussion = $sender->EventArguments["Discussion"];
        if (
            Gdn::session()->checkPermission(
                "Vanilla.Discussions.Edit",
                true,
                "Category",
                $discussion->PermissionCategoryID
            )
        ) {
            $actionMessage .=
                " " . anchor(t("Split"), "moderation/splitcomments/" . $discussion->DiscussionID . "/", "Split Popup");
        }
    }

    /**
     * Add "merge" action link.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCheckDiscussions_handler($sender)
    {
        $actionMessage = &$sender->EventArguments["ActionMessage"];
        if (Gdn::session()->checkPermission("Vanilla.Discussions.Edit", true, "Category", "any")) {
            $actionMessage .= " " . anchor(t("Merge"), "moderation/mergediscussions/", "Merge Popup");
        }
    }

    /**
     * Add a method to the ModerationController to handle splitting comments out to a new discussion.
     *
     * @param moderationController $controller
     */
    public function moderationController_splitComments_create(ModerationController $controller)
    {
        $session = Gdn::session();
        $controller->Form = new Gdn_Form();
        $controller->title(t("Split Comments"));
        $controller->Category = false;

        $discussionID = val("0", $controller->RequestArgs, "");
        if (!is_numeric($discussionID)) {
            return;
        }

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);
        if (!$discussion) {
            return;
        }

        // Verify that the user has permission to perform the split
        $controller->permission("Vanilla.Discussions.Edit", true, "Category", $discussion->PermissionCategoryID);

        $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedComments", []);
        if (!is_array($checkedComments)) {
            $checkedComments = [];
        }

        $commentIDs = [];
        foreach ($checkedComments as $discID => $comments) {
            foreach ($comments as $comment) {
                if ($discID == $discussionID) {
                    $commentIDs[] = str_replace("Comment_", "", $comment);
                }
            }
        }

        // Load category data.
        $countCheckedComments = count($commentIDs);
        $controller->setData("CountCheckedComments", $countCheckedComments);
        // Perform the split
        if ($controller->Form->authenticatedPostBack()) {
            $discussionSplitModel = \Gdn::getContainer()->get(DiscussionSplitModel::class);
            // Create a new discussion record
            $data = $controller->Form->formValues();
            $data["Body"] = sprintf(
                t("This discussion was created from comments split from: %s."),
                anchor(
                    Gdn_Format::text($discussion->Name),
                    "discussion/" . $discussion->DiscussionID . "/" . Gdn_Format::url($discussion->Name) . "/"
                )
            );
            $data["Format"] = "Html";
            $data["Type"] = "Discussion";

            // Use the System user as the author.
            $data["InsertUserID"] = Gdn::userModel()->getSystemUserID();

            // Pass a forced input formatter around this exception.
            if (c("Garden.ForceInputFormatter")) {
                $inputFormat = c("Garden.InputFormatter");
                saveToConfig("Garden.InputFormatter", "Html", false);
            }

            $newDiscussionID = $discussionModel->save($data);

            // Reset the input formatter
            if (c("Garden.ForceInputFormatter")) {
                saveToConfig("Garden.InputFormatter", $inputFormat, false);
            }

            $controller->Form->setValidationResults($discussionModel->validationResults());

            if ($controller->Form->errorCount() == 0 && $newDiscussionID > 0) {
                // Re-assign the comments to the new discussion record
                $iterator = $discussionSplitModel->splitDiscussionsIterator(
                    $commentIDs,
                    $newDiscussionID,
                    $discussionID
                );
                $errorMessages = "";

                foreach ($iterator as $result) {
                    if ($result instanceof LongRunnerSuccessID) {
                        $successCommentID = (int) $result->getRecordID();
                        $checkedComments[$discussionID] = $newCheckedIDs = array_filter(
                            $checkedComments[$discussionID],
                            fn($id) => $id != $successCommentID
                        );
                        Gdn::userModel()->saveAttribute($session->UserID, "CheckedComments", [
                            $discussionID => $newCheckedIDs,
                        ]);
                    } elseif ($result instanceof LongRunnerFailedID) {
                        $errorMessages .= $result->getException()->getMessage() . "\n";
                    }
                }

                ModerationController::informCheckedComments($controller);
                // Clear selections

                if (empty($errorMessages)) {
                    unset($checkedComments[$discussionID]);
                    Gdn::userModel()->saveAttribute($session->UserID, "CheckedComments", $checkedComments);
                    $controller->jsonTarget("", "", "Refresh");
                } else {
                    $controller->informMessage($errorMessages);
                }

                $controller->setRedirectTo("discussion/" . $newDiscussionID . "/" . Gdn_Format::url($data["Name"]));
            }
        } else {
            $controller->Form->setValue("CategoryID", val("CategoryID", $discussion));
        }

        $controller->render($controller->fetchViewLocation("splitcomments", "", "plugins/SplitMerge"));
    }

    /**
     * Add a method to the ModerationController to handle merging discussions.
     *
     * @param ModerationController $controller
     *
     * @throws Gdn_UserException
     */
    public function moderationController_mergeDiscussions_create(ModerationController $controller)
    {
        $session = Gdn::session();
        $controller->Form = new Gdn_Form();
        $controller->title(t("Merge Discussions"));

        $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, "CheckedDiscussions", []);
        if (!is_array($checkedDiscussions)) {
            $checkedDiscussions = [];
        }

        $discussionIDs = $checkedDiscussions;
        $countCheckedDiscussions = count($discussionIDs);
        $discussions = \Gdn::database()
            ->createSql()
            ->where("DiscussionID", $discussionIDs)
            ->get("Discussion")
            ->resultArray();

        // Make sure none of the selected discussions are ghost redirects.
        $discussionTypes = array_column($discussions, "Type");
        if (in_array("redirect", $discussionTypes)) {
            throw new Gdn_UserException("You cannot merge redirects.", 400);
        }

        // Check that the user has permission to edit all discussions
        foreach ($discussions as $discussion) {
            if (!DiscussionModel::canEdit($discussion)) {
                throw permissionException(
                    "@" . t("You do not have permission to edit all of the posts you are trying to merge.")
                );
            }
        }

        $controller->setData("DiscussionIDs", $discussionIDs);
        $controller->setData("CountCheckedDiscussions", $countCheckedDiscussions);
        $controller->setData("Discussions", $discussions);

        // Perform the merge
        if ($controller->Form->authenticatedPostBack()) {
            $shouldRedirect = $controller->Form->getFormValue("RedirectLink");
            $discussionMergeModel = \Gdn::getContainer()->get(DiscussionMergeModel::class);
            $mergeDiscussionID = $controller->Form->getFormValue(
                "MergeDiscussionID",
                $discussions[0]["DiscussionID"] ?? null
            );
            if ($mergeDiscussionID === null) {
                throw new Gdn_UserException("No discussion ID provided to merge into.", 400);
            }

            // Because this is a legacy controller we can't actually run this long runner normally.
            // Instead we'll just consume the generator here.
            $iterator = $discussionMergeModel->mergeDiscussionsIterator(
                $discussionIDs,
                $mergeDiscussionID,
                addRedirects: $shouldRedirect
            );
            $errorMessages = "";

            foreach ($iterator as $result) {
                if ($result instanceof LongRunnerSuccessID) {
                    $successDiscussionID = (int) $result->getRecordID();
                    $checkedDiscussions = $newCheckedIDs = array_filter(
                        $checkedDiscussions,
                        fn($id) => $id != $successDiscussionID
                    );
                    Gdn::userModel()->saveAttribute($session->UserID, "CheckedDiscussions", $newCheckedIDs);
                } elseif ($result instanceof LongRunnerFailedID) {
                    $errorMessages .= $result->getException()->getMessage() . "\n";
                }
            }

            ModerationController::informCheckedDiscussions($controller);

            if (empty($errorMessages)) {
                Gdn::userModel()->saveAttribute($session->UserID, "CheckedDiscussions", []);
                $controller->jsonTarget("", "", "Refresh");
            } else {
                $controller->informMessage($errorMessages);
            }
        }

        $controller->render("MergeDiscussions", "", "plugins/SplitMerge");
    }

    /**
     * Add "redirect" to allowed discussion types when moving discussions.
     *
     * @param $destinationCategory
     * @param $allowedDiscussions
     * @return mixed
     */
    public function discussionModel_moveAllowedTypes($allowedDiscussions, $destinationCategory)
    {
        // If the array is empty or null, the default is to allow all types. We don't
        // want to interfere with that.
        if (is_array($allowedDiscussions) && !empty($allowedDiscussions)) {
            $allowedDiscussions[] = "redirect";
        }
        return $allowedDiscussions;
    }

    /**
     * Run once on enable.
     */
    public function setup()
    {
        saveToConfig("Vanilla.AdminCheckboxes.Use", true);
    }
}
