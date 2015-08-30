<?php
/**
 * Moderation controller
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Vanilla
 * @since 2.0
 */

/**
 * Handles content moderation via /modersation endpoint.
 */
class ModerationController extends VanillaController {

    /**
     * Looks at the user's attributes and form postback to see if any comments
     * have been checked for administration, and if so, puts an inform message on
     * the screen to take action.
     */
    public function checkedComments() {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        ModerationController::InformCheckedComments($this);
        $this->render();
    }

    /**
     * Looks at the user's attributes and form postback to see if any discussions
     * have been checked for administration, and if so, puts an inform message on
     * the screen to take action.
     */
    public function checkedDiscussions() {
        $this->deliveryType(DELIVERY_TYPE_BOOL);
        $this->deliveryMethod(DELIVERY_METHOD_JSON);
        ModerationController::InformCheckedDiscussions($this);
        $this->render();
    }

    /**
     * Looks at the user's attributes and form postback to see if any comments
     * have been checked for administration, and if so, adds an inform message to
     * $Sender to take action.
     */
    public static function informCheckedComments($Sender) {
        $Session = Gdn::session();
        $HadCheckedComments = false;
        $TransientKey = val('TransientKey', $_POST);
        if ($Session->isValid() && $Session->validateTransientKey($TransientKey)) {
            // Form was posted, so accept changes to checked items.
            $DiscussionID = val('DiscussionID', $_POST, 0);
            $CheckIDs = val('CheckIDs', $_POST);
            if (empty($CheckIDs)) {
                $CheckIDs = array();
            }
            $CheckIDs = (array)$CheckIDs;

            $CheckedComments = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedComments', array());
            if (!is_array($CheckedComments)) {
                $CheckedComments = array();
            }

            if (!array_key_exists($DiscussionID, $CheckedComments)) {
                $CheckedComments[$DiscussionID] = array();
            } else {
                // Were there checked comments in this discussion before the form was posted?
                $HadCheckedComments = count($CheckedComments[$DiscussionID]) > 0;
            }
            foreach ($CheckIDs as $Check) {
                if (val('checked', $Check)) {
                    if (!ArrayHasValue($CheckedComments, $Check['checkId'])) {
                        $CheckedComments[$DiscussionID][] = $Check['checkId'];
                    }
                } else {
                    RemoveValueFromArray($CheckedComments[$DiscussionID], $Check['checkId']);
                }
            }

            if (count($CheckedComments[$DiscussionID]) == 0) {
                unset($CheckedComments[$DiscussionID]);
            }

            Gdn::userModel()->saveAttribute($Session->User->UserID, 'CheckedComments', $CheckedComments);
        } elseif ($Session->isValid()) {
            // No form posted, just retrieve checked items for display
            $DiscussionID = property_exists($Sender, 'DiscussionID') ? $Sender->DiscussionID : 0;
            $CheckedComments = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedComments', array());
            if (!is_array($CheckedComments)) {
                $CheckedComments = array();
            }

        }

        // Retrieve some information about the checked items
        $CountDiscussions = count($CheckedComments);
        $CountComments = 0;
        foreach ($CheckedComments as $DiscID => $Comments) {
            if ($DiscID == $DiscussionID) {
                $CountComments += count($Comments); // Sum of comments in this discussion
            }
        }
        if ($CountComments > 0) {
            $SelectionMessage = wrap(sprintf(
                t('You have selected %1$s in this discussion.'),
                plural($CountComments, '%s comment', '%s comments')
            ), 'div');
            $ActionMessage = t('Take Action:');

            // Can the user delete the comment?
            $DiscussionModel = new DiscussionModel();
            $Discussion = $DiscussionModel->getID($DiscussionID);
            $PermissionCategory = CategoryModel::categories(val('CategoryID', $Discussion));
            if ($Session->checkPermission('Vanilla.Comments.Delete', true, 'Category', val('PermissionCategoryID', $PermissionCategory))) {
                $ActionMessage .= ' '.anchor(t('Delete'), 'vanilla/moderation/confirmcommentdeletes/'.$DiscussionID, 'Delete Popup');
            }

            $Sender->EventArguments['SelectionMessage'] = &$SelectionMessage;
            $Sender->EventArguments['ActionMessage'] = &$ActionMessage;
            $Sender->EventArguments['Discussion'] = $Discussion;
            $Sender->fireEvent('BeforeCheckComments');
            $ActionMessage .= ' '.anchor(t('Cancel'), 'vanilla/moderation/clearcommentselections/'.$DiscussionID.'/{TransientKey}/?Target={SelfUrl}', 'CancelAction');

            $Sender->informMessage(
                $SelectionMessage
                .Wrap($ActionMessage, 'div', array('class' => 'Actions')),
                array(
                    'CssClass' => 'NoDismiss',
                    'id' => 'CheckSummary'
                )
            );
        } elseif ($HadCheckedComments) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $Sender->informMessage('', array('id' => 'CheckSummary'));
        }
    }

    /**
     * Looks at the user's attributes and form postback to see if any discussions
     * have been checked for administration, and if so, adds an inform message to
     * $Sender to take action.
     */
    public static function informCheckedDiscussions($Sender, $Force = false) {
        $Session = Gdn::session();
        $HadCheckedDiscussions = $Force;
        if ($Session->isValid() && Gdn::request()->isAuthenticatedPostBack()) {
            // Form was posted, so accept changes to checked items.
            $CheckIDs = val('CheckIDs', $_POST);
            if (empty($CheckIDs)) {
                $CheckIDs = array();
            }
            $CheckIDs = (array)$CheckIDs;

            $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', array());
            if (!is_array($CheckedDiscussions)) {
                $CheckedDiscussions = array();
            }

            // Were there checked discussions before the form was posted?
            $HadCheckedDiscussions |= count($CheckedDiscussions) > 0;

            foreach ($CheckIDs as $Check) {
                if (val('checked', $Check)) {
                    if (!ArrayHasValue($CheckedDiscussions, $Check['checkId'])) {
                        $CheckedDiscussions[] = $Check['checkId'];
                    }
                } else {
                    RemoveValueFromArray($CheckedDiscussions, $Check['checkId']);
                }
            }

            Gdn::userModel()->saveAttribute($Session->User->UserID, 'CheckedDiscussions', $CheckedDiscussions);
        } elseif ($Session->isValid()) {
            // No form posted, just retrieve checked items for display
            $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', array());
            if (!is_array($CheckedDiscussions)) {
                $CheckedDiscussions = array();
            }

        }

        // Retrieve some information about the checked items
        $CountDiscussions = count($CheckedDiscussions);
        if ($CountDiscussions > 0) {
            $SelectionMessage = wrap(sprintf(
                t('You have selected %1$s.'),
                plural($CountDiscussions, '%s discussion', '%s discussions')
            ), 'div');
            $ActionMessage = t('Take Action:');
            $ActionMessage .= ' '.anchor(t('Delete'), 'vanilla/moderation/confirmdiscussiondeletes/', 'Delete Popup');
            $ActionMessage .= ' '.anchor(t('Move'), 'vanilla/moderation/confirmdiscussionmoves/', 'Move Popup');

            $Sender->EventArguments['SelectionMessage'] = &$SelectionMessage;
            $Sender->EventArguments['ActionMessage'] = &$ActionMessage;
            $Sender->fireEvent('BeforeCheckDiscussions');
            $ActionMessage .= ' '.anchor(t('Cancel'), 'vanilla/moderation/cleardiscussionselections/{TransientKey}/?Target={SelfUrl}', 'CancelAction');

            $Sender->informMessage(
                $SelectionMessage
                .Wrap($ActionMessage, 'div', array('class' => 'Actions')),
                array(
                    'CssClass' => 'NoDismiss',
                    'id' => 'CheckSummary'
                )
            );
        } elseif ($HadCheckedDiscussions) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $Sender->informMessage('', array('id' => 'CheckSummary'));
        }
    }

    /**
     * Remove all comments checked for administration from the user's attributes.
     */
    public function clearCommentSelections($DiscussionID = '', $TransientKey = '') {
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey)) {
            $CheckedComments = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedComments', array());
            unset($CheckedComments[$DiscussionID]);
            Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
        }

        redirect(GetIncomingValue('Target', '/discussions'));
    }

    /**
     * Remove all discussions checked for administration from the user's attributes.
     */
    public function clearDiscussionSelections($TransientKey = '') {
        $Session = Gdn::session();
        if ($Session->validateTransientKey($TransientKey)) {
            Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedDiscussions', false);
        }

        redirect(GetIncomingValue('Target', '/discussions'));
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * comments (and has permission to do so).
     */
    public function confirmCommentDeletes($DiscussionID = '') {
        $Session = Gdn::session();
        $this->Form = new Gdn_Form();
        $DiscussionModel = new DiscussionModel();
        $Discussion = $DiscussionModel->getID($DiscussionID);
        if (!$Discussion) {
            return;
        }

        // Verify that the user has permission to perform the delete
        $PermissionCategory = CategoryModel::categories($Discussion->CategoryID);
        $this->permission('Vanilla.Comments.Delete', true, 'Category', val('PermissionCategoryID', $PermissionCategory));
        $this->title(t('Confirm'));

        $CheckedComments = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedComments', array());
        if (!is_array($CheckedComments)) {
            $CheckedComments = array();
        }

        $CommentIDs = array();
        $DiscussionIDs = array();
        foreach ($CheckedComments as $DiscID => $Comments) {
            foreach ($Comments as $Comment) {
                if (substr($Comment, 0, 11) == 'Discussion_') {
                    $DiscussionIDs[] = str_replace('Discussion_', '', $Comment);
                } elseif ($DiscID == $DiscussionID) {
                    $CommentIDs[] = str_replace('Comment_', '', $Comment);
                }
            }
        }
        $CountCheckedComments = count($CommentIDs);
        $this->setData('CountCheckedComments', $CountCheckedComments);

        if ($this->Form->authenticatedPostBack()) {
            // Delete the selected comments
            $CommentModel = new CommentModel();
            foreach ($CommentIDs as $CommentID) {
                $CommentModel->delete($CommentID);
            }

            // Clear selections
            unset($CheckedComments[$DiscussionID]);
            Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedComments', $CheckedComments);
            ModerationController::InformCheckedComments($this);
            $this->RedirectUrl = 'discussions';
        }

        $this->render();
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * discussions (and has permission to do so).
     */
    public function confirmDiscussionDeletes() {
        $Session = Gdn::session();
        $this->Form = new Gdn_Form();
        $DiscussionModel = new DiscussionModel();

        // Verify that the user has permission to perform the deletes
        $this->permission('Vanilla.Discussions.Delete', true, 'Category', 'any');
        $this->title(t('Confirm'));

        $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', array());
        if (!is_array($CheckedDiscussions)) {
            $CheckedDiscussions = array();
        }

        $DiscussionIDs = $CheckedDiscussions;
        $CountCheckedDiscussions = count($DiscussionIDs);
        $this->setData('CountCheckedDiscussions', $CountCheckedDiscussions);

        // Check permissions on each discussion to make sure the user has permission to delete them
        $AllowedDiscussions = array();
        $DiscussionData = $DiscussionModel->SQL->select('DiscussionID, CategoryID')->from('Discussion')->whereIn('DiscussionID', $DiscussionIDs)->get();
        foreach ($DiscussionData->result() as $Discussion) {
            $PermissionCategory = CategoryModel::categories(val('CategoryID', $Discussion));
            $CountCheckedDiscussions = $DiscussionData->numRows();
            if ($Session->checkPermission('Vanilla.Discussions.Delete', true, 'Category', val('PermissionCategoryID', $PermissionCategory))) {
                $AllowedDiscussions[] = $Discussion->DiscussionID;
            }
        }
        $this->setData('CountAllowed', count($AllowedDiscussions));
        $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
        $this->setData('CountNotAllowed', $CountNotAllowed);

        if ($this->Form->authenticatedPostBack()) {
            // Delete the selected discussions (that the user has permission to delete).
            foreach ($AllowedDiscussions as $DiscussionID) {
                $Deleted = $DiscussionModel->delete($DiscussionID);
                if ($Deleted) {
                    $this->jsonTarget("#Discussion_$DiscussionID", '', 'SlideUp');
                }
            }

            // Clear selections
            Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedDiscussions', null);
            ModerationController::InformCheckedDiscussions($this, true);
        }

        $this->render();
    }

    /**
     * Form to ask for the destination of the move, confirmation and permission check.
     */
    public function confirmDiscussionMoves($DiscussionID = null) {
        $Session = Gdn::session();
        $this->Form = new Gdn_Form();
        $DiscussionModel = new DiscussionModel();
        $CategoryModel = new CategoryModel();

        $this->title(t('Confirm'));

        if ($DiscussionID) {
            $CheckedDiscussions = (array)$DiscussionID;
            $ClearSelection = false;
        } else {
            $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', array());
            if (!is_array($CheckedDiscussions)) {
                $CheckedDiscussions = array();
            }

            $ClearSelection = true;
        }

        $DiscussionIDs = $CheckedDiscussions;
        $CountCheckedDiscussions = count($DiscussionIDs);
        $this->setData('CountCheckedDiscussions', $CountCheckedDiscussions);

        // Check for edit permissions on each discussion
        $AllowedDiscussions = array();
        $DiscussionData = $DiscussionModel->SQL->select('DiscussionID, Name, DateLastComment, CategoryID, CountComments')->from('Discussion')->whereIn('DiscussionID', $DiscussionIDs)->get();
        $DiscussionData = Gdn_DataSet::Index($DiscussionData->resultArray(), array('DiscussionID'));
        foreach ($DiscussionData as $DiscussionID => $Discussion) {
            $Category = CategoryModel::categories($Discussion['CategoryID']);
            if ($Category && $Category['PermsDiscussionsEdit']) {
                $AllowedDiscussions[] = $DiscussionID;
            }
        }
        $this->setData('CountAllowed', count($AllowedDiscussions));
        $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
        $this->setData('CountNotAllowed', $CountNotAllowed);

        if ($this->Form->authenticatedPostBack()) {
            // Retrieve the category id
            $CategoryID = $this->Form->getFormValue('CategoryID');
            $Category = CategoryModel::categories($CategoryID);
            $RedirectLink = $this->Form->getFormValue('RedirectLink');

            // User must have add permission on the target category
            if (!$Category['PermsDiscussionsAdd']) {
                throw forbiddenException('@'.t('You do not have permission to add discussions to this category.'));
            }

            $AffectedCategories = array();

            // Iterate and move.
            foreach ($AllowedDiscussions as $DiscussionID) {
                $Discussion = val($DiscussionID, $DiscussionData);

                // Create the shadow redirect.
                if ($RedirectLink) {
                    $DiscussionModel->defineSchema();
                    $MaxNameLength = val('Length', $DiscussionModel->Schema->GetField('Name'));

                    $RedirectDiscussion = array(
                        'Name' => SliceString(sprintf(t('Moved: %s'), $Discussion['Name']), $MaxNameLength),
                        'DateInserted' => $Discussion['DateLastComment'],
                        'Type' => 'redirect',
                        'CategoryID' => $Discussion['CategoryID'],
                        'Body' => formatString(t('This discussion has been <a href="{url,html}">moved</a>.'), array('url' => DiscussionUrl($Discussion))),
                        'Format' => 'Html',
                        'Closed' => true
                    );

                    // Pass a forced input formatter around this exception.
                    if (c('Garden.ForceInputFormatter')) {
                        $InputFormat = c('Garden.InputFormatter');
                        saveToConfig('Garden.InputFormatter', 'Html', false);
                    }

                    $RedirectID = $DiscussionModel->save($RedirectDiscussion);

                    // Reset the input formatter
                    if (c('Garden.ForceInputFormatter')) {
                        saveToConfig('Garden.InputFormatter', $InputFormat, false);
                    }

                    if (!$RedirectID) {
                        $this->Form->setValidationResults($DiscussionModel->validationResults());
                        break;
                    }
                }

                $DiscussionModel->setField($DiscussionID, 'CategoryID', $CategoryID);

                if (!isset($AffectedCategories[$Discussion['CategoryID']])) {
                    $AffectedCategories[$Discussion['CategoryID']] = array(-1, -$Discussion['CountComments']);
                } else {
                    $AffectedCategories[$Discussion['CategoryID']][0] -= 1;
                    $AffectedCategories[$Discussion['CategoryID']][1] -= $Discussion['CountComments'];
                }
                if (!isset($AffectedCategories[$CategoryID])) {
                    $AffectedCategories[$CategoryID] = array(1, $Discussion['CountComments']);
                } else {
                    $AffectedCategories[$CategoryID][0] += 1;
                    $AffectedCategories[$CategoryID][1] += $Discussion['CountComments'];
                }
            }

            // Update recent posts and counts on all affected categories.
            foreach ($AffectedCategories as $CategoryID => $Counts) {
                $CategoryModel->SetRecentPost($CategoryID);
                $CategoryModel->SQL
                    ->update('Category')
                    ->set('CountDiscussions', 'CountDiscussions'.($Counts[0] < 0 ? ' - ' : ' + ').abs($Counts[0]), false)
                    ->set('CountComments', 'CountComments'.($Counts[1] < 0 ? ' - ' : ' + ').abs($Counts[1]), false)
                    ->where('CategoryID', $CategoryID)
                    ->put();
            }

            // Clear selections.
            if ($ClearSelection) {
                Gdn::userModel()->saveAttribute($Session->UserID, 'CheckedDiscussions', false);
                ModerationController::InformCheckedDiscussions($this);
            }

            if ($this->Form->errorCount() == 0) {
                $this->jsonTarget('', '', 'Refresh');
            }
        }

        $this->render();
    }
}
