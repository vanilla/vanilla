<?php
/**
 * Manages the activity stream.
 *
 * @copyright 2009-2015 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /activity endpoint.
 */
class ActivityController extends Gdn_Controller {

    /**  @var array Models to include. */
    public $Uses = array('Database', 'Form', 'ActivityModel');

    /** @var ActivityModel */
    public $ActivityModel;

    /**
     * Create some virtual properties.
     *
     * @param $Name
     * @return Gdn_DataSet
     */
    public function __get($Name) {
        switch ($Name) {
            case 'CommentData':
                Deprecated('ActivityController->CommentData', "ActivityController->data('Activities')");
                $Result = new Gdn_DataSet(array(), DATASET_TYPE_OBJECT);
                return $Result;
            case 'ActivityData':
                Deprecated('ActivityController->ActivityData', "ActivityController->data('Activities')");
                $Result = new Gdn_DataSet($this->data('Activities'), DATASET_TYPE_ARRAY);
                $Result->datasetType(DATASET_TYPE_OBJECT);
                return $Result;
        }
    }

    /**
     * Include JS, CSS, and modules used by all methods.
     *
     * Always called by dispatcher before controller's requested method.
     *
     * @since 2.0.0
     * @access public
     */
    public function initialize() {
        $this->Head = new HeadModule($this);
        $this->addJsFile('jquery.js');
        $this->addJsFile('jquery.livequery.js');
        $this->addJsFile('jquery.form.js');
        $this->addJsFile('jquery.popup.js');
        $this->addJsFile('jquery.gardenhandleajaxform.js');
        $this->addJsFile('global.js');

        $this->addCssFile('style.css');
        $this->addCssFile('vanillicon.css', 'static');

        // Add Modules
        $this->addModule('GuestModule');
        $this->addModule('SignedInModule');

        parent::initialize();
        Gdn_Theme::section('ActivityList');
        $this->setData('Breadcrumbs', array(array('Name' => t('Activity'), 'Url' => '/activity')));
    }

    /**
     * Display a single activity item & comments.
     *
     * Email notifications regarding activities link to this method.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ActivityID Unique ID of activity item to display.
     */
    public function item($ActivityID = 0) {
        $this->addJsFile('activity.js');
        $this->title(t('Activity Item'));

        if (!is_numeric($ActivityID) || $ActivityID < 0) {
            $ActivityID = 0;
        }

        $this->ActivityData = $this->ActivityModel->getWhere(array('ActivityID' => $ActivityID));
        $this->setData('Comments', $this->ActivityModel->getComments(array($ActivityID)));
        $this->setData('Activities', $this->ActivityData);

        $this->render();
    }

    /**
     * Default activity stream.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $Offset Number of activity items to skip.
     */
    public function index($Filter = false, $Page = false) {
        switch (strtolower($Filter)) {
            case 'mods':
                $this->title(t('Recent Moderator Activity'));
                $this->permission('Garden.Moderation.Manage');
                $NotifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case 'admins':
                $this->title(t('Recent Admin Activity'));
                $this->permission('Garden.Settings.Manage');
                $NotifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            case '':
            case 'feed': // rss feed
                $Filter = 'public';
                $this->title(t('Recent Activity'));
                $this->permission('Garden.Activity.View');
                $NotifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
            default:
                throw notFoundException();
        }

        // Which page to load
        list($Offset, $Limit) = offsetLimit($Page, c('Garden.Activities.PerPage', 30));
        $Offset = is_numeric($Offset) ? $Offset : 0;
        if ($Offset < 0) {
            $Offset = 0;
        }

        // Page meta.
        $this->addJsFile('activity.js');

        if ($this->Head) {
            $this->Head->addRss(url('/activity/feed.rss', true), $this->Head->title());
        }

        // Comment submission
        $Session = Gdn::session();
        $Comment = $this->Form->getFormValue('Comment');
        $Activities = $this->ActivityModel->getWhere(array('NotifyUserID' => $NotifyUserID), $Offset, $Limit)->resultArray();
        $this->ActivityModel->joinComments($Activities);

        $this->setData('Filter', strtolower($Filter));
        $this->setData('Activities', $Activities);

        $this->addModule('ActivityFilterModule');

        $this->View = 'all';
        $this->render();
    }

    public function deleteComment($ID, $TK, $Target = '') {
        $session = Gdn::session();
        if (!$session->validateTransientKey($TK)) {
            throw permissionException();
        }

        if (!is_numeric($ID)) {
            throw Gdn_UserException('Invalid ID');
        }

        $comment = $this->ActivityModel->getComment($ID);
        if (!$comment) {
            throw notFoundException('Comment');
        }

        $activity = $this->ActivityModel->getID(val('ActivityID', $comment));
        if (!$activity) {
            throw notFoundException('Activity');
        }

        if (!$this->ActivityModel->canDelete($activity)) {
            throw permissionException();
        }
        $this->ActivityModel->deleteComment($ID);
        if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirect($Target);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Delete an activity item.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $ActivityID Unique ID of item to delete.
     * @param string $TransientKey Verify intent.
     */
    public function delete($ActivityID = '', $TransientKey = '') {
        $session = Gdn::session();
        if (!$session->validateTransientKey($TransientKey)) {
            throw permissionException();
        }

        if (!is_numeric($ActivityID)) {
            throw Gdn_UserException('Invalid ID');
        }

        if (!$this->ActivityModel->canDelete($this->ActivityModel->getID($ActivityID))) {
            throw permissionException();
        }

        $this->ActivityModel->delete($ActivityID);

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            redirect(GetIncomingValue('Target', $this->SelfUrl));
        }

        // Still here? Getting a 404.
        $this->ControllerName = 'Home';
        $this->View = 'FileNotFound';
        $this->render();
    }

    /**
     * Comment on an activity item.
     *
     * @since 2.0.0
     * @access public
     */
    public function comment() {
        $this->permission('Garden.Profiles.Edit');

        $Session = Gdn::session();
        $this->Form->setModel($this->ActivityModel);
        $NewActivityID = 0;

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            $Body = $this->Form->getValue('Body', '');
            $ActivityID = $this->Form->getValue('ActivityID', '');
            if (is_numeric($ActivityID) && $ActivityID > 0) {
                $ActivityComment = array(
                    'ActivityID' => $ActivityID,
                    'Body' => $Body,
                    'Format' => 'Text');

                $ID = $this->ActivityModel->comment($ActivityComment);

                if ($ID == SPAM || $ID == UNAPPROVED) {
                    $this->StatusMessage = t('ActivityCommentRequiresApproval', 'Your comment will appear after it is approved.');
                    $this->render('Blank', 'Utility');
                    return;
                }

                $this->Form->setValidationResults($this->ActivityModel->validationResults());
                if ($this->Form->errorCount() > 0) {
                    throw new Exception($this->ActivityModel->Validation->resultsText());

                    $this->errorMessage($this->Form->errors());
                }
            }
        }

        // Redirect back to the sending location if this isn't an ajax request
        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $Target = $this->Form->getValue('Return');
            if (!$Target) {
                $Target = '/activity';
            }
            redirect($Target);
        } else {
            // Load the newly added comment.
            $this->setData('Comment', $this->ActivityModel->getComment($ID));

            // Set it in the appropriate view.
            $this->View = 'comment';
        }

        // And render
        $this->render();
    }

    /**
     *
     * 
     * @param bool $Notify
     * @param bool $UserID
     */
    public function post($Notify = false, $UserID = false) {
        if (is_numeric($Notify)) {
            $UserID = $Notify;
            $Notify = false;
        }

        if (!$UserID) {
            $UserID = Gdn::session()->UserID;
        }

        switch ($Notify) {
            case 'mods':
                $this->permission('Garden.Moderation.Manage');
                $NotifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case 'admins':
                $this->permission('Garden.Settings.Manage');
                $NotifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            default:
                $this->permission('Garden.Profiles.Edit');
                $NotifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
        }

        $Activities = array();

        if ($this->Form->authenticatedPostBack()) {
            $Data = $this->Form->formValues();
            $Data = $this->ActivityModel->filterForm($Data);
            if (!isset($Data['Format']) || strcasecmp($Data['Format'], 'Raw') == 0) {
                $Data['Format'] = c('Garden.InputFormatter');
            }

            if ($UserID != Gdn::session()->UserID) {
                // This is a wall post.
                $Activity = array(
                    'ActivityType' => 'WallPost',
                    'ActivityUserID' => $UserID,
                    'RegardingUserID' => Gdn::session()->UserID,
                    'HeadlineFormat' => t('HeadlineFormat.WallPost', '{RegardingUserID,you} &rarr; {ActivityUserID,you}'),
                    'Story' => $Data['Comment'],
                    'Format' => $Data['Format'],
                    'Data' => array('Bump' => true)
                );
            } else {
                // This is a status update.
                $Activity = array(
                    'ActivityType' => 'Status',
                    'HeadlineFormat' => t('HeadlineFormat.Status', '{ActivityUserID,user}'),
                    'Story' => $Data['Comment'],
                    'Format' => $Data['Format'],
                    'NotifyUserID' => $NotifyUserID,
                    'Data' => array('Bump' => true)
                );
                $this->setJson('StatusMessage', Gdn_Format::plainText($Activity['Story'], $Activity['Format']));
            }

            $Activity = $this->ActivityModel->save($Activity, false, array('CheckSpam' => true));
            if ($Activity == SPAM || $Activity == UNAPPROVED) {
                $this->StatusMessage = t('ActivityRequiresApproval', 'Your post will appear after it is approved.');
                $this->render('Blank', 'Utility');
                return;
            }

            if ($Activity) {
                if ($UserID == Gdn::session()->UserID && $NotifyUserID == ActivityModel::NOTIFY_PUBLIC) {
                    Gdn::userModel()->setField(Gdn::session()->UserID, 'About', Gdn_Format::plainText($Activity['Story'], $Activity['Format']));
                }

                $Activities = array($Activity);
                ActivityModel::joinUsers($Activities);
                $this->ActivityModel->calculateData($Activities);
            } else {
                $this->Form->setValidationResults($this->ActivityModel->validationResults());

                $this->StatusMessage = $this->ActivityModel->Validation->resultsText();
//            $this->render('Blank', 'Utility');
            }
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $Target = $this->Request->get('Target', '/activity');
            if (isSafeUrl($Target)) {
                redirect($Target);
            } else {
                redirect(url('/activity'));
            }
        }

        $this->setData('Activities', $Activities);
        $this->render('Activities');
    }
}
