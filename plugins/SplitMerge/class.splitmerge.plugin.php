<?php
/**
 * SplitMerge plugin.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package SplitMerge
 */

// Define the plugin:
$PluginInfo['SplitMerge'] = array(
    'Name' => 'Split / Merge',
    'Description' => 'Allows moderators with discussion edit permission to split & merge discussions.',
    'Version' => '1.2',
    'HasLocale' => true,
    'Author' => "Mark O'Sullivan",
    'AuthorEmail' => 'mark@vanillaforums.com',
    'AuthorUrl' => 'http://www.vanillaforums.com'
);

/**
 * Class SplitMergePlugin
 */
class SplitMergePlugin extends Gdn_Plugin {

    /**
     * Add "split" action link.
     */
    public function base_beforeCheckComments_handler($Sender) {
        $ActionMessage = &$Sender->EventArguments['ActionMessage'];
        $Discussion = $Sender->EventArguments['Discussion'];
        if (Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $Discussion->PermissionCategoryID)) {
            $ActionMessage .= ' '.anchor(t('Split'), 'vanilla/moderation/splitcomments/'.$Discussion->DiscussionID.'/', 'Split Popup');
        }
    }

    /**
     * Add "merge" action link.
     */
    public function base_beforeCheckDiscussions_handler($Sender) {
        $ActionMessage = &$Sender->EventArguments['ActionMessage'];
        if (Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', 'any')) {
            $ActionMessage .= ' '.anchor(t('Merge'), 'vanilla/moderation/mergediscussions/', 'Merge Popup');
        }
    }

    /**
     * Add a method to the ModerationController to handle splitting comments out to a new discussion.
     */
    public function moderationController_splitComments_create($Sender) {
        $Session = Gdn::session();
        $Sender->Form = new Gdn_Form();
        $Sender->title(t('Split Comments'));
        $Sender->Category = false;

        $DiscussionID = val('0', $Sender->RequestArgs, '');
        if (!is_numeric($DiscussionID)) {
            return;
        }

        $DiscussionModel = new DiscussionModel();
        $Discussion = $DiscussionModel->getID($DiscussionID);
        if (!$Discussion) {
            return;
        }

        // Verify that the user has permission to perform the split
        $Sender->permission('Vanilla.Discussions.Edit', true, 'Category', $Discussion->PermissionCategoryID);

        $CheckedComments = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedComments', array());
        if (!is_array($CheckedComments)) {
            $CheckedComments = array();
        }

        $CommentIDs = array();
        foreach ($CheckedComments as $DiscID => $Comments) {
            foreach ($Comments as $Comment) {
                if ($DiscID == $DiscussionID) {
                    $CommentIDs[] = str_replace('Comment_', '', $Comment);
                }
            }
        }
        // Load category data.
        $Sender->ShowCategorySelector = (bool)c('Vanilla.Categories.Use');
        $CountCheckedComments = count($CommentIDs);
        $Sender->setData('CountCheckedComments', $CountCheckedComments);
        // Perform the split
        if ($Sender->Form->authenticatedPostBack()) {
            // Create a new discussion record
            $Data = $Sender->Form->formValues();
            $Data['Body'] = sprintf(t('This discussion was created from comments split from: %s.'), anchor(Gdn_Format::text($Discussion->Name), 'discussion/'.$Discussion->DiscussionID.'/'.Gdn_Format::url($Discussion->Name).'/'));
            $Data['Format'] = 'Html';
            $Data['Type'] = 'Discussion';
            $NewDiscussionID = $DiscussionModel->save($Data);
            $Sender->Form->setValidationResults($DiscussionModel->validationResults());

            if ($Sender->Form->errorCount() == 0 && $NewDiscussionID > 0) {
                // Re-assign the comments to the new discussion record
                $DiscussionModel->SQL
                    ->update('Comment')
                    ->set('DiscussionID', $NewDiscussionID)
                    ->whereIn('CommentID', $CommentIDs)
                    ->put();

                // Update counts on both discussions
                $CommentModel = new CommentModel();
                $CommentModel->updateCommentCount($DiscussionID);
//            $CommentModel->UpdateUserCommentCounts($DiscussionID);
                $CommentModel->updateCommentCount($NewDiscussionID);
                $CommentModel->removePageCache($DiscussionID, 1);


                // Clear selections
                unset($CheckedComments[$DiscussionID]);
                Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
                ModerationController::informCheckedComments($Sender);
                $Sender->RedirectUrl = url('discussion/'.$NewDiscussionID.'/'.Gdn_Format::url($Data['Name']));
            }
        } else {
            $Sender->Form->setValue('CategoryID', val('CategoryID', $Discussion));
        }

        $Sender->render($this->getView('splitcomments.php'));
    }

    /**
     * Add a method to the ModerationController to handle merging discussions.
     *
     * @param Gdn_Controller $Sender
     */
    public function moderationController_mergeDiscussions_create($Sender) {
        $Session = Gdn::session();
        $Sender->Form = new Gdn_Form();
        $Sender->title(t('Merge Discussions'));

        $DiscussionModel = new DiscussionModel();
        $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', array());
        if (!is_array($CheckedDiscussions)) {
            $CheckedDiscussions = array();
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
        foreach($Discussions as $discussion) {
            if (!DiscussionModel::canEdit($discussion)) {
                throw permissionException('@'.t('You do not have permission to edit all of the discussions you are trying to merge.'));
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
                    $Comment = arrayTranslate($Discussion, array('Body', 'Format', 'DateInserted', 'InsertUserID', 'InsertIPAddress', 'DateUpdated', 'UpdateUserID', 'UpdateIPAddress', 'Attributes', 'Spam', 'Likes', 'Abuse'));
                    $Comment['DiscussionID'] = $MergeDiscussionID;

                    $CommentModel->Validation->results(true);
                    $CommentID = $CommentModel->save($Comment);
                    if ($CommentID) {
                        // Move any attachments (FileUpload plugin awareness)
                        if (class_exists('MediaModel')) {
                            $MediaModel = new MediaModel();
                            $MediaModel->reassign($Discussion['DiscussionID'], 'discussion', $CommentID, 'comment');
                        }

                        if ($RedirectLink) {
                            // The discussion needs to be changed to a moved link.
                            $RedirectDiscussion = array(
                                'Name' => SliceString(sprintf(t('Merged: %s'), $Discussion['Name']), $MaxNameLength),
                                'Type' => 'redirect',
                                'Body' => formatString(t('This discussion has been <a href="{url,html}">merged</a>.'), array('url' => DiscussionUrl($MergeDiscussion))),
                                'Format' => 'Html'
                            );
                            $DiscussionModel->setField($Discussion['DiscussionID'], $RedirectDiscussion);
                            $CommentModel->updateCommentCount($Discussion['DiscussionID']);
                            $CommentModel->removePageCache($Discussion['DiscussionID']);
                        } else {
                            // Delete discussion that was merged.
                            $DiscussionModel->delete($Discussion['DiscussionID']);
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
     *
     */
    public function setup() {
        saveToConfig('Vanilla.AdminCheckboxes.Use', true);
    }
}
