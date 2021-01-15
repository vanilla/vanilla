<?php
/**
 * Moderation controller
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Vanilla
 * @since 2.0
 */

use Webmozart\Assert\Assert;

/**
 * Handles content moderation via /moderation endpoint.
 */
class ModerationController extends VanillaController {
    use \Vanilla\Web\TwigRenderTrait;

    // Maximum number of seconds a batch of deletes should last before a new batch needs to be scheduled.
    private const MAX_TIME_BATCH = 10;

    /** @var \Garden\EventManager */
    private $eventManager;

    /**
     * ModerationController constructor.
     *
     * @param \Garden\EventManager $eventManager
     */
    public function __construct(\Garden\EventManager $eventManager) {
        $this->eventManager = $eventManager;
        parent::__construct();
    }

    /**
     * Looks at the user's attributes and form postback to see if any comments
     * have been checked for administration, and if so, puts an inform message on
     * the screen to take action.
     */
    public function checkedComments() {
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
    public function checkedDiscussions() {
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
    public static function informCheckedComments($sender) {
        $session = Gdn::session();
        $hadCheckedComments = false;
        $transientKey = val('TransientKey', $_POST);
        if ($session->isValid() && $session->validateTransientKey($transientKey)) {
            // Form was posted, so accept changes to checked items.
            $discussionID = val('DiscussionID', $_POST, 0);
            $checkIDs = val('CheckIDs', $_POST);
            if (empty($checkIDs)) {
                $checkIDs = [];
            }
            $checkIDs = (array)$checkIDs;

            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedComments', []);
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
                if (val('checked', $check)) {
                    if (!arrayHasValue($checkedComments, $check['checkId'])) {
                        $checkedComments[$discussionID][] = $check['checkId'];
                    }
                } else {
                    removeValueFromArray($checkedComments[$discussionID], $check['checkId']);
                }
            }

            if (count($checkedComments[$discussionID]) == 0) {
                unset($checkedComments[$discussionID]);
            }

            Gdn::userModel()->saveAttribute($session->User->UserID, 'CheckedComments', $checkedComments);
        } elseif ($session->isValid()) {
            // No form posted, just retrieve checked items for display
            $discussionID = property_exists($sender, 'DiscussionID') ? $sender->DiscussionID : 0;
            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedComments', []);
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
            $selectionMessage = wrap(sprintf(
                t('You have selected %1$s in this discussion.'),
                plural($countComments, '%s comment', '%s comments')
            ), 'div');
            $actionMessage = t('Take Action:');

            // Can the user delete the comment?
            $discussionModel = new DiscussionModel();
            $discussion = $discussionModel->getID($discussionID);
            if (CategoryModel::checkPermission(val('CategoryID', $discussion), 'Vanilla.Comments.Delete')) {
                $actionMessage .= ' '.anchor(t('Delete'), 'moderation/confirmcommentdeletes/'.$discussionID, 'Delete Popup');
            }

            $sender->EventArguments['SelectionMessage'] = &$selectionMessage;
            $sender->EventArguments['ActionMessage'] = &$actionMessage;
            $sender->EventArguments['Discussion'] = $discussion;
            $sender->fireEvent('BeforeCheckComments');
            $actionMessage .= ' '.anchor(t('Cancel'), 'moderation/clearcommentselections/'.$discussionID.'/{TransientKey}/?Target={SelfUrl}', 'CancelAction');

            $sender->informMessage(
                $selectionMessage
                .wrap($actionMessage, 'div', ['class' => 'Actions']),
                [
                    'CssClass' => 'NoDismiss',
                    'id' => 'CheckSummary'
                ]
            );
        } elseif ($hadCheckedComments) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $sender->informMessage('', ['id' => 'CheckSummary']);
        }
    }

    /**
     * Looks at the user's attributes and form postback to see if any discussions
     * have been checked for administration, and if so, adds an inform message to
     * $sender to take action.
     */
    public static function informCheckedDiscussions($sender, $force = false) {
        $session = Gdn::session();
        $hadCheckedDiscussions = $force;
        if ($session->isValid() && Gdn::request()->isAuthenticatedPostBack()) {
            // Form was posted, so accept changes to checked items.
            $checkIDs = val('CheckIDs', $_POST);
            if (empty($checkIDs)) {
                $checkIDs = [];
            }
            $checkIDs = (array)$checkIDs;

            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedDiscussions', []);
            if (!is_array($checkedDiscussions)) {
                $checkedDiscussions = [];
            }

            // Were there checked discussions before the form was posted?
            $hadCheckedDiscussions |= count($checkedDiscussions) > 0;

            foreach ($checkIDs as $check) {
                if (val('checked', $check)) {
                    if (!arrayHasValue($checkedDiscussions, $check['checkId'])) {
                        $checkedDiscussions[] = $check['checkId'];
                    }
                } else {
                    removeValueFromArray($checkedDiscussions, $check['checkId']);
                }
            }

            Gdn::userModel()->saveAttribute($session->User->UserID, 'CheckedDiscussions', $checkedDiscussions);
        } elseif ($session->isValid()) {
            // No form posted, just retrieve checked items for display
            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedDiscussions', []);
            if (!is_array($checkedDiscussions)) {
                $checkedDiscussions = [];
            }

        }

        // Retrieve some information about the checked items
        $countDiscussions = count($checkedDiscussions);
        if ($countDiscussions > 0) {
            $selectionMessage = wrap(sprintf(
                t('You have selected %1$s.'),
                plural($countDiscussions, '%s discussion', '%s discussions')
            ), 'div');
            $actionMessage = t('Take Action:');
            $actionMessage .= ' '.anchor(t('Delete'), 'moderation/confirmdiscussiondeletes/', 'Delete Popup');
            $actionMessage .= ' '.anchor(t('Move'), 'moderation/confirmdiscussionmoves/', 'Move Popup');

            $sender->EventArguments['SelectionMessage'] = &$selectionMessage;
            $sender->EventArguments['ActionMessage'] = &$actionMessage;
            $sender->fireEvent('BeforeCheckDiscussions');
            $actionMessage .= ' '.anchor(t('Cancel'), 'moderation/cleardiscussionselections/{TransientKey}/?Target={SelfUrl}', 'CancelAction');

            $sender->informMessage(
                $selectionMessage
                .wrap($actionMessage, 'div', ['class' => 'Actions']),
                [
                    'CssClass' => 'NoDismiss',
                    'id' => 'CheckSummary'
                ]
            );
        } elseif ($hadCheckedDiscussions) {
            // Remove the message completely if there were previously checked comments in this discussion, but none now
            $sender->informMessage('', ['id' => 'CheckSummary']);
        }
    }

    /**
     * Remove all comments checked for administration from the user's attributes.
     *
     * @param int $discussionID
     * @param string $transientKey
     */
    public function clearCommentSelections($discussionID, $transientKey = '') {
        $session = Gdn::session();
        if ($session->validateTransientKey($transientKey)) {
            $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedComments', []);
            unset($checkedComments[$discussionID]);
            Gdn::userModel()->saveAttribute($session->UserID, 'CheckedComments', $checkedComments);
        }

        redirectTo(getIncomingValue('Target', '/discussions'));
    }

    /**
     * Remove all discussions checked for administration from the user's attributes.
     */
    public function clearDiscussionSelections($transientKey = '') {
        $session = Gdn::session();
        if ($session->validateTransientKey($transientKey)) {
            Gdn::userModel()->saveAttribute($session->UserID, 'CheckedDiscussions', false);
        }

        redirectTo(getIncomingValue('Target', '/discussions'));
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * comments (and has permission to do so).
     *
     * @param int $discussionID
     */
    public function confirmCommentDeletes(int $discussionID) {
        $session = Gdn::session();
        $this->Form = new Gdn_Form();
        $discussionModel = new DiscussionModel();
        $discussion = $discussionModel->getID($discussionID);
        if (!$discussion) {
            return;
        }

        // Verify that the user has permission to perform the delete
        $this->categoryPermission($discussion->CategoryID, 'Vanilla.Comments.Delete');
        $this->title(t('Confirm'));

        $checkedComments = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedComments', []);
        if (!is_array($checkedComments)) {
            $checkedComments = [];
        }

        $commentIDs = [];
        $discussionIDs = [];
        foreach ($checkedComments as $discID => $comments) {
            foreach ($comments as $comment) {
                if (substr($comment, 0, 11) == 'Discussion_') {
                    $discussionIDs[] = str_replace('Discussion_', '', $comment);
                } elseif ($discID == $discussionID) {
                    $commentIDs[] = str_replace('Comment_', '', $comment);
                }
            }
        }
        $countCheckedComments = count($commentIDs);
        $this->setData('CountCheckedComments', $countCheckedComments);

        if ($this->Form->authenticatedPostBack()) {
            // Delete the selected comments
            $commentModel = new CommentModel();
            foreach ($commentIDs as $commentID) {
                Assert::integerish($commentID);
                $comment = $commentModel->getID($commentID, DATASET_TYPE_ARRAY);
                if ((int)$comment['DiscussionID'] === (int)$discussionID) {
                    // Make sure the comment is from the same discussion that was deleted.
                    // The user interface ensures this, but just in case it becomes not true due to a UX error let's not
                    // make this an error that the user may not be able to recover from.
                    $commentModel->deleteID($commentID);
                }
            }

            // Clear selections
            unset($checkedComments[$discussionID]);
            Gdn::userModel()->saveAttribute($session->UserID, 'CheckedComments', $checkedComments);
            ModerationController::informCheckedComments($this);
            $this->setRedirectTo(discussionUrl($discussion));
        }

        $this->render();
    }

    /**
     * Form to confirm that the administrator wants to delete the selected
     * discussions (and has permission to do so).
     */
    public function confirmDiscussionDeletes() {
        $startTime = time();
        $session = Gdn::session();
        $this->Form = new Gdn_Form();
        $discussionModel = new DiscussionModel();

        // Verify that the user has permission to perform the deletes
        $this->permission('Vanilla.Discussions.Delete', true, 'Category', 'any');
        $this->title(t('Confirm'));

        $checkedDiscussions = $this->Request->post('discussionIDs', null);

        if ($checkedDiscussions === null) {
            $checkedDiscussions = Gdn::userModel()->getAttribute($session->User->UserID, 'CheckedDiscussions', []);
        }

        if (!is_array($checkedDiscussions)) {
            $checkedDiscussions = [];
        } else {
            array_walk($checkedDiscussions, [Assert::class, 'integerish']);
        }

        $discussionIDs = $checkedDiscussions;
        $countCheckedDiscussions = count($discussionIDs);
        $this->setData('CountCheckedDiscussions', $countCheckedDiscussions);

        // Check permissions on each discussion to make sure the user has permission to delete them
        $allowedDiscussions = [];
        $discussionData = $discussionModel->SQL
            ->select('DiscussionID, CategoryID')
            ->from('Discussion')
            ->whereIn('DiscussionID', $discussionIDs)
            ->get();
        foreach ($discussionData->result() as $discussion) {
            $countCheckedDiscussions = $discussionData->numRows();
            if (CategoryModel::checkPermission(val('CategoryID', $discussion), 'Vanilla.Discussions.Delete')) {
                $allowedDiscussions[] = $discussion->DiscussionID;
            }
        }
        $this->setData('CountAllowed', count($allowedDiscussions));
        $countNotAllowed = $countCheckedDiscussions - count($allowedDiscussions);
        $this->setData('CountNotAllowed', $countNotAllowed);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            $checkedDiscussions = array_combine($allowedDiscussions, $allowedDiscussions);

            foreach ($allowedDiscussions as $discussionID) {
                $discussionModel->deleteID($discussionID);
                unset($checkedDiscussions[$discussionID]);
                Gdn::userModel()->saveAttribute(
                    $session->UserID,
                    'CheckedDiscussions',
                    array_values($checkedDiscussions)
                );
                $this->jsonTarget("#Discussion_$discussionID", ["remove" => true], 'SlideUp');

                $elapsedTime = time() - $startTime;
                if ($elapsedTime > self::MAX_TIME_BATCH) {
                    break;
                }
            }
            if (!empty($checkedDiscussions)) {
                $this->jsonTarget('', [
                    'url' => '/moderation/confirmdiscussiondeletes',
                    'reprocess' => true,
                    'data' => [
                        'DeliveryType' => DELIVERY_TYPE_VIEW,
                        'DeliveryMethod' => DELIVERY_METHOD_JSON,
                        'discussionIDs' => array_values($checkedDiscussions),
                        'fork' => false
                    ]
                ], 'Ajax');
                $this->title(t("Deleting..."));
                $this->setFormSaved(false);
                $this->jsonTarget(
                    "#Popup .Content",
                    $this->renderTwig('/applications/vanilla/views/moderation/progress.twig', $this->Data),
                    "Html"
                );
                $this->View = "progress";
            } else {
                $this->jsonTarget("!element", "", "closePopup");
                $this->setFormSaved(true);
            }

            ModerationController::informCheckedDiscussions($this, true);
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
        $startTime = time();

        $this->title(t('Confirm'));

        if ($DiscussionID) {
            $CheckedDiscussions = (array)$DiscussionID;
            $discussion = $DiscussionModel->getID($DiscussionID, DATASET_TYPE_ARRAY);
            $this->setData('CategoryID', $discussion['CategoryID']);
            $this->setData('DiscussionType', $discussion['Type']);
        } else {
            $CheckedDiscussions = $this->Request->post('discussionIDs', null);

            if ($CheckedDiscussions === null) {
                $CheckedDiscussions = Gdn::userModel()->getAttribute($Session->User->UserID, 'CheckedDiscussions', []);
            }

            if (!is_array($CheckedDiscussions)) {
                $CheckedDiscussions = [];
            }
        }

        $DiscussionIDs = $CheckedDiscussions;
        $CountCheckedDiscussions = count($DiscussionIDs);
        $this->setData('CountCheckedDiscussions', $CountCheckedDiscussions);

        // fire event
        $this->EventArguments['select'] = ['DiscussionID', 'Name', 'Type', 'DateLastComment', 'CategoryID', 'CountComments'];
        $this->fireEvent('beforeDiscussionMoveSelect', $this->EventArguments);

        // Check for edit permissions on each discussion
        $AllowedDiscussions = [];
        $DiscussionData = $DiscussionModel->SQL
            ->select($this->EventArguments['select'])
            ->from('Discussion')->whereIn('DiscussionID', $DiscussionIDs)->get();

        $DiscussionData = Gdn_DataSet::index($DiscussionData->resultArray(), ['DiscussionID']);
        foreach ($DiscussionData as $DiscussionID => $Discussion) {
            $Category = CategoryModel::categories($Discussion['CategoryID']);
            if (!array_key_exists('DiscussionType', $this->Data) && !is_null($Discussion['Type'])) {
                $this->setData('DiscussionType', $Discussion['Type']);
                $this->setData('CategoryID', $Category['CategoryID']);
            }
            if ($Category && CategoryModel::checkPermission($Category, 'Vanilla.Discussions.Edit')) {
                $AllowedDiscussions[] = $DiscussionID;
            }
        }

        $checkedDiscussions = array_combine($AllowedDiscussions, $AllowedDiscussions);
        $this->setData('CountAllowed', count($AllowedDiscussions));
        $CountNotAllowed = $CountCheckedDiscussions - count($AllowedDiscussions);
        $this->setData('CountNotAllowed', $CountNotAllowed);

        if ($this->Request->isAuthenticatedPostBack(true)) {
            // Retrieve the category id
            $CategoryID = $this->Form->getFormValue('CategoryID');
            $Category = CategoryModel::categories($CategoryID);
            $this->Form->validateRule('CategoryID', 'function:ValidateRequired', 'Category is required');
            $this->Form->setValidationResults($CategoryModel->validationResults());
            if ($this->Form->errorCount() === 0) {
                $RedirectLink = $this->Form->getFormValue('RedirectLink');

                // User must have add permission on the target category
                if (!CategoryModel::checkPermission($Category, 'Vanilla.Discussions.Add')) {
                    throw forbiddenException('@' . t('You do not have permission to add discussions to this category.'));
                }

                // Iterate and move.
                foreach ($AllowedDiscussions as $DiscussionID) {
                    $Discussion = val($DiscussionID, $DiscussionData);
                    $this->EventArguments['discussion'] = &$Discussion;

                    // Create the shadow redirect.
                    if ($RedirectLink) {
                        $DiscussionModel->defineSchema();
                        $MaxNameLength = val('Length', $DiscussionModel->Schema->getField('Name'));

                        $RedirectDiscussion = [
                            'Name' => sliceString(sprintf(t('Moved: %s'), $Discussion['Name']), $MaxNameLength),
                            'DateInserted' => $Discussion['DateLastComment'],
                            'Type' => 'redirect',
                            'CategoryID' => $Discussion['CategoryID'],
                            'Body' => formatString(
                                t('This discussion has been <a href="{url,html}">moved</a>.'),
                                ['url' => discussionUrl($Discussion)]
                            ),
                            'Format' => 'Html',
                            'Closed' => true
                        ];

                        $this->EventArguments['redirectDiscussion'] = &$RedirectDiscussion;

                        // fire event
                        $this->fireEvent('beforeRedirectDiscussionSave', $this->EventArguments);

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

                    $this->EventArguments['save'] = [
                        "CategoryID" => $CategoryID,
                        "DiscussionID" => $DiscussionID,
                    ];

                    // fire event
                    $this->fireEvent('beforeDiscussionMoveSave', $this->EventArguments);

                    $DiscussionModel->save($this->EventArguments['save']);
                    unset($checkedDiscussions[$DiscussionID]);

                    Gdn::userModel()->saveAttribute(
                        $Session->UserID,
                        "CheckedDiscussions",
                        array_values($checkedDiscussions)
                    );

                    $elapsedTime = time() - $startTime;
                    if ($elapsedTime > self::MAX_TIME_BATCH) {
                        break;
                    }
                }

                if (!empty($checkedDiscussions)) {
                    $this->jsonTarget('', [
                        'url' => '/moderation/confirmdiscussionmoves',
                        'reprocess' => true,
                        'data' => [
                            'DeliveryType' => DELIVERY_TYPE_VIEW,
                            'DeliveryMethod' => DELIVERY_METHOD_JSON,
                            'CategoryID' => $CategoryID,
                            'RedirectLink' => $this->Form->getFormValue('RedirectLink') ? 1 : 0,
                            'discussionIDs' => array_values($checkedDiscussions),
                            'fork' => false
                        ]
                    ], 'Ajax');
                    $this->title(t("Moving..."));
                    $this->setFormSaved(false);
                    $this->jsonTarget(
                        "#Popup .Content",
                        $this->renderTwig('/applications/vanilla/views/moderation/progress.twig', $this->Data),
                        "Html"
                    );
                    $this->View = "progress";
                } else {
                    ModerationController::informCheckedDiscussions($this);

                    if ($this->Form->errorCount() == 0) {
                        $this->setFormSaved(true);
                        $this->jsonTarget('', '', 'Refresh');
                    }
                }
            }
        }
        $this->render();
    }
}
