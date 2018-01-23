<?php
/**
 * SplitMerge plugin.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package SplitMerge
 */

/**
 * Class SplitMergePlugin
 */
class SplitMergePlugin extends Gdn_Plugin {

    /**
     * Add "split" action link.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCheckComments_handler($sender) {
        $actionMessage = &$sender->EventArguments['ActionMessage'];
        $discussion = $sender->EventArguments['Discussion'];
        if (Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
            $actionMessage .= ' '.anchor(t('Split'), 'moderation/splitcomments/'.$discussion->DiscussionID.'/', 'Split Popup');
        }
    }

    /**
     * Add "merge" action link.
     *
     * @param Gdn_Controller $sender
     */
    public function base_beforeCheckDiscussions_handler($sender) {
        $actionMessage = &$sender->EventArguments['ActionMessage'];
        if (Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', 'any')) {
            $actionMessage .= ' '.anchor(t('Merge'), 'moderation/mergediscussions/', 'Merge Popup');
        }
    }

    /**
     * Add a method to the ModerationController to handle splitting comments out to a new discussion.
     *
     * @param moderationController $sender
     */
    public function moderationController_splitComments_create($sender) {
        $session = Gdn::session();
        $sender->Form = new Gdn_Form();
        $sender->title(t('Split Comments'));
        $sender->Category = false;

        $discussionID = val('0', $sender->RequestArgs, '');
        if (!is_numeric($discussionID)) {
            return;
        }

        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);
        if (!$discussion) {
            return;
        }

        // Verify that the user has permission to perform the split
        $sender->permission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID);

        $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedComments', []);
        if (!is_array($checkedComments)) {
            $checkedComments = [];
        }

        $commentIDs = [];
        foreach ($checkedComments as $discID => $comments) {
            foreach ($comments as $comment) {
                if ($discID == $discussionID) {
                    $commentIDs[] = str_replace('Comment_', '', $comment);
                }
            }
        }

        // Load category data.
        $sender->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        $countCheckedComments = count($commentIDs);
        $sender->setData('CountCheckedComments', $countCheckedComments);
        // Perform the split
        if ($sender->Form->authenticatedPostBack()) {
            // Create a new discussion record
            $data = $sender->Form->formValues();
            $data['Body'] = sprintf(t('This discussion was created from comments split from: %s.'), anchor(Gdn_Format::text($discussion->Name), 'discussion/'.$discussion->DiscussionID.'/'.Gdn_Format::url($discussion->Name).'/'));
            $data['Format'] = 'Html';
            $data['Type'] = 'Discussion';

            // Use the System user as the author.
            $data['InsertUserID'] = Gdn::userModel()->getSystemUserID();

            // Pass a forced input formatter around this exception.
            if (c('Garden.ForceInputFormatter')) {
                $inputFormat = c('Garden.InputFormatter');
                saveToConfig('Garden.InputFormatter', 'Html', false);
            }

            $newDiscussionID = $discussionModel->save($data);

            // Reset the input formatter
            if (c('Garden.ForceInputFormatter')) {
                saveToConfig('Garden.InputFormatter', $inputFormat, false);
            }

            $sender->Form->setValidationResults($discussionModel->validationResults());

            if ($sender->Form->errorCount() == 0 && $newDiscussionID > 0) {
                // Re-assign the comments to the new discussion record
                $discussionModel->SQL
                    ->update('Comment')
                    ->set('DiscussionID', $newDiscussionID)
                    ->whereIn('CommentID', $commentIDs)
                    ->put();

                // Update counts on both discussions
                $commentModel = new CommentModel();
                $commentModel->updateCommentCount($discussionID);
//            $CommentModel->updateUserCommentCounts($DiscussionID);
                $commentModel->updateCommentCount($newDiscussionID);
                $commentModel->removePageCache($discussionID, 1);


                // Clear selections
                unset($checkedComments[$discussionID]);
                Gdn::userModel()->saveAttribute($session->UserID, 'CheckedComments', $checkedComments);
                ModerationController::informCheckedComments($sender);
                $sender->setRedirectTo('discussion/'.$newDiscussionID.'/'.Gdn_Format::url($data['Name']));
            }
        } else {
            $sender->Form->setValue('CategoryID', val('CategoryID', $discussion));
        }

        $sender->render($sender->fetchViewLocation('splitcomments', '', 'plugins/SplitMerge'));
    }

    /**
     * Add a method to the ModerationController to handle merging discussions.
     *
     * @param moderationController $Sender
     *
     * @throws Gdn_UserException
     */
    public function moderationController_mergeDiscussions_create($Sender) {
        $Session = Gdn::session();
        $Sender->Form = new Gdn_Form();
        $Sender->title(t('Merge Discussions'));

        $DiscussionModel = new DiscussionModel();
        $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', []);
        if (!is_array($CheckedDiscussions)) {
            $CheckedDiscussions = [];
        }

        $DiscussionIDs = $CheckedDiscussions;
        $CountCheckedDiscussions = count($DiscussionIDs);
        $Discussions = $DiscussionModel->SQL->whereIn('DiscussionID', $DiscussionIDs)->get('Discussion')->resultArray();

        // Make sure none of the selected discussions are ghost redirects.
        $discussionTypes = array_column($Discussions, 'Type');
        if (in_array('redirect', $discussionTypes)) {
            throw new Gdn_UserException('You cannot merge redirects.', 400);
        }

        // Check that the user has permission to edit all discussions
        foreach ($Discussions as $discussion) {
            if (!DiscussionModel::canEdit($discussion)) {
                throw permissionException('@'.t('You do not have permission to edit all of the posts you are trying to merge.'));
            }
        }

        $Sender->setData('DiscussionIDs', $DiscussionIDs);
        $Sender->setData('CountCheckedDiscussions', $CountCheckedDiscussions);
        $Sender->setData('Discussions', $Discussions);

        // Perform the merge
        if ($Sender->Form->authenticatedPostBack()) {
            // Create a new discussion record
            $MergeDiscussion = false;
            $MergeDiscussionID = $Sender->Form->getFormValue('MergeDiscussionID');
            foreach ($Discussions as $Discussion) {
                if ($Discussion['DiscussionID'] == $MergeDiscussionID) {
                    $MergeDiscussion = $Discussion;
                    break;
                }
            }
            $RedirectLink = $Sender->Form->getFormValue('RedirectLink');

            if ($MergeDiscussion) {
                $ErrorCount = 0;

                // Verify that the user has permission to perform the merge.
                $Category = CategoryModel::categories($MergeDiscussion['CategoryID']);
                if ($Category && !$Category['PermsDiscussionsEdit']) {
                    throw permissionException('Vanilla.Discussions.Edit');
                }

                $DiscussionModel->defineSchema();
                $MaxNameLength = val('Length', $DiscussionModel->Schema->getField('Name'));

                // Assign the comments to the new discussion record
                $DiscussionModel->SQL
                    ->update('Comment')
                    ->set('DiscussionID', $MergeDiscussionID)
                    ->whereIn('DiscussionID', $DiscussionIDs)
                    ->put();

                $CommentModel = new CommentModel();
                foreach ($Discussions as $Discussion) {
                    if ($Discussion['DiscussionID'] == $MergeDiscussionID) {
                        continue;
                    }

                    // Create a comment out of the discussion.
                    $Comment = arrayTranslate($Discussion, ['Body', 'Format', 'DateInserted', 'InsertUserID', 'InsertIPAddress', 'DateUpdated', 'UpdateUserID', 'UpdateIPAddress', 'Attributes', 'Spam', 'Likes', 'Abuse']);
                    $Comment['DiscussionID'] = $MergeDiscussionID;

                    $CommentModel->Validation->results(true);
                    $CommentID = $CommentModel->save($Comment);
                    if ($CommentID) {
                        $Comment['CommentID'] = $CommentID;
                        $this->EventArguments['SourceDiscussion'] = $Discussion;
                        $this->EventArguments['TargetComment'] = $Comment;
                        $this->fireEvent('TransformDiscussionToComment');

                        if ($RedirectLink) {
                            // The discussion needs to be changed to a moved link.
                            $RedirectDiscussion = [
                                'Name' => sliceString(sprintf(t('Merged: %s'), $Discussion['Name']), $MaxNameLength),
                                'Type' => 'redirect',
                                'Body' => formatString(t('This discussion has been <a href="{url,html}">merged</a>.'), ['url' => discussionUrl($MergeDiscussion)]),
                                'Format' => 'Html'
                            ];
                            $DiscussionModel->setField($Discussion['DiscussionID'], $RedirectDiscussion);
                            $CommentModel->updateCommentCount($Discussion['DiscussionID']);
                            $CommentModel->removePageCache($Discussion['DiscussionID']);
                        } else {
                            // Delete discussion that was merged.
                            $DiscussionModel->deleteID($Discussion['DiscussionID']);
                        }
                    } else {
                        $Sender->informMessage($CommentModel->Validation->resultsText());
                        $ErrorCount++;
                    }
                }
                // Update counts on all affected discussions.
                $CommentModel->updateCommentCount($MergeDiscussionID);
                $CommentModel->removePageCache($MergeDiscussionID);

                // Clear selections
                Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedDiscussions', false);
                ModerationController::informCheckedDiscussions($Sender);
                if ($ErrorCount == 0) {
                    $Sender->jsonTarget('', '', 'Refresh');
                }
            }
        }

        $Sender->render('MergeDiscussions', '', 'plugins/SplitMerge');
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        saveToConfig('Vanilla.AdminCheckboxes.Use', true);
    }
}
