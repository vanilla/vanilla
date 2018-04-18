<?php
/**
 * Manages the activity stream.
 *
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license http://www.opensource.org/licenses/gpl-2.0.php GNU GPL v2
 * @package Dashboard
 * @since 2.0
 */

/**
 * Handles /activity endpoint.
 */
class ActivityController extends Gdn_Controller {

    /**  @var array Models to include. */
    public $Uses = ['Database', 'Form', 'ActivityModel'];

    /** @var ActivityModel */
    public $ActivityModel;

    /**
     * Create some virtual properties.
     *
     * @param $name
     * @return Gdn_DataSet
     */
    public function __get($name) {
        switch ($name) {
            case 'CommentData':
                deprecated('ActivityController->CommentData', "ActivityController->data('Activities')");
                $result = new Gdn_DataSet([], DATASET_TYPE_OBJECT);
                return $result;
            case 'ActivityData':
                deprecated('ActivityController->ActivityData', "ActivityController->data('Activities')");
                $result = new Gdn_DataSet($this->data('Activities'), DATASET_TYPE_ARRAY);
                $result->datasetType(DATASET_TYPE_OBJECT);
                return $result;
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
        $this->setData('Breadcrumbs', [['Name' => t('Activity'), 'Url' => '/activity']]);
    }

    /**
     * Display a single activity item & comments.
     *
     * Email notifications regarding activities link to this method.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $activityID Unique ID of activity item to display.
     */
    public function item($activityID = 0) {
        $this->addJsFile('activity.js');
        $this->title(t('Activity Item'));

        if (!is_numeric($activityID) || $activityID < 0) {
            $activityID = 0;
        }

        $this->ActivityData = $this->ActivityModel->getWhere(['ActivityID' => $activityID]);

        // Check visibility.
        if (!$this->ActivityModel->canView($this->ActivityData->firstRow())) {
            throw permissionException();
        }

        $this->setData('Comments', $this->ActivityModel->getComments([$activityID]));
        $this->setData('Activities', $this->ActivityData);

        $this->render();
    }

    /**
     * Default activity stream.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $offset Number of activity items to skip.
     */
    public function index($filter = false, $page = false) {
        switch (strtolower($filter)) {
            case 'mods':
                $this->title(t('Recent Moderator Activity'));
                $this->permission('Garden.Moderation.Manage');
                $notifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case 'admins':
                $this->title(t('Recent Admin Activity'));
                $this->permission('Garden.Settings.Manage');
                $notifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            case '':
            case 'feed': // rss feed
                $filter = 'public';
                $this->title(t('Recent Activity'));
                $this->permission('Garden.Activity.View');
                $notifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
            default:
                throw notFoundException();
        }

        // Which page to load
        list($offset, $limit) = offsetLimit($page, c('Garden.Activities.PerPage',30));
        $offset = is_numeric($offset) ? $offset : 0;
        if ($offset < 0) {
            $offset = 0;
        }

        // Page meta.
        $this->addJsFile('activity.js');

        if ($this->Head) {
            $this->Head->addRss(url('/activity/feed.rss', true), $this->Head->title());
        }

        // Comment submission
        $session = Gdn::session();
        $comment = $this->Form->getFormValue('Comment');
        $activities = $this->ActivityModel->getWhere(['NotifyUserID' => $notifyUserID], '', '', $limit, $offset)->resultArray();
        $this->ActivityModel->joinComments($activities);

        $this->setData('Filter', strtolower($filter));
        $this->setData('Activities', $activities);

        $this->addModule('ActivityFilterModule');

        $this->View = 'all';
        $this->render();
    }

    public function deleteComment($iD, $tK, $target = '') {
        $session = Gdn::session();
        if (!$session->validateTransientKey($tK)) {
            throw permissionException();
        }

        if (!is_numeric($iD)) {
            throw gdn_UserException('Invalid ID');
        }

        $comment = $this->ActivityModel->getComment($iD);
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
        $this->ActivityModel->deleteComment($iD);
        if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirectTo($target);
        }

        $this->render('Blank', 'Utility', 'Dashboard');
    }

    /**
     * Delete an activity item.
     *
     * @since 2.0.0
     * @access public
     *
     * @param int $activityID Unique ID of item to delete.
     * @param string $transientKey Verify intent.
     */
    public function delete($activityID = '', $transientKey = '') {
        $session = Gdn::session();
        if (!$session->validateTransientKey($transientKey)) {
            throw permissionException();
        }

        if (!is_numeric($activityID)) {
            throw gdn_UserException('Invalid ID');
        }

        if (!$this->ActivityModel->canDelete($this->ActivityModel->getID($activityID))) {
            throw permissionException();
        }

        $this->ActivityModel->deleteID($activityID);


        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $target = Gdn::request()->get('Target');
            if ($target) {
                // Bail with a redirect if we got one.
                redirectTo($target);
            } else {
                // We got this as a full page somehow, so send them back to /activity.
                $this->setRedirectTo('activity');
            }
        }

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

        $session = Gdn::session();
        $this->Form->setModel($this->ActivityModel);
        $newActivityID = 0;

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            $body = $this->Form->getValue('Body', '');
            $activityID = $this->Form->getValue('ActivityID', '');
            if (is_numeric($activityID) && $activityID > 0) {
                $activity = $this->ActivityModel->getID($activityID);
                if ($activity) {
                    if ($activity['NotifyUserID'] == ActivityModel::NOTIFY_ADMINS) {
                        $this->permission('Garden.Settings.Manage');
                    } elseif ($activity['NotifyUserID'] == ActivityModel::NOTIFY_MODS) {
                        $this->permission('Garden.Moderation.Manage');
                    }
                } else {
                    throw new Exception(t('Invalid activity'));
                }

                $activityComment = [
                    'ActivityID' => $activityID,
                    'Body' => $body,
                    'Format' => 'Text'];

                $iD = $this->ActivityModel->comment($activityComment);

                if ($iD == SPAM || $iD == UNAPPROVED) {
                    $this->StatusMessage = t('Your comment will appear after it is approved.');
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
            $target = $this->Form->getValue('Return');
            if (!$target) {
                $target = '/activity';
            }
            redirectTo($target);
        } else {
            // Load the newly added comment.
            $this->setData('Comment', $this->ActivityModel->getComment($iD));

            // Set it in the appropriate view.
            $this->View = 'comment';
        }

        // And render
        $this->render();
    }

    /**
     *
     *
     * @param bool $notify
     * @param bool $userID
     */
    public function post($notify = false, $userID = false) {
        if (is_numeric($notify)) {
            $userID = $notify;
            $notify = false;
        }

        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        switch ($notify) {
            case 'mods':
                $this->permission('Garden.Moderation.Manage');
                $notifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case 'admins':
                $this->permission('Garden.Settings.Manage');
                $notifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            default:
                $this->permission('Garden.Profiles.Edit');
                $notifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
        }

        $activities = [];

        if ($this->Form->authenticatedPostBack()) {
            $data = $this->Form->formValues();
            $data = $this->ActivityModel->filterForm($data);
            if (!isset($data['Format']) || strcasecmp($data['Format'], 'Raw') == 0) {
                $data['Format'] = c('Garden.InputFormatter');
            }

            if ($userID != Gdn::session()->UserID) {
                // This is a wall post.
                $activity = [
                    'ActivityType' => 'WallPost',
                    'ActivityUserID' => $userID,
                    'RegardingUserID' => Gdn::session()->UserID,
                    'HeadlineFormat' => t('HeadlineFormat.WallPost', '{RegardingUserID,you} &rarr; {ActivityUserID,you}'),
                    'Story' => $data['Comment'],
                    'Format' => $data['Format'],
                    'Data' => ['Bump' => true]
                ];
            } else {
                // This is a status update.
                $activity = [
                    'ActivityType' => 'Status',
                    'HeadlineFormat' => t('HeadlineFormat.Status', '{ActivityUserID,user}'),
                    'Story' => $data['Comment'],
                    'Format' => $data['Format'],
                    'NotifyUserID' => $notifyUserID,
                    'Data' => ['Bump' => true]
                ];
                $this->setJson('StatusMessage', Gdn_Format::plainText($activity['Story'], $activity['Format']));
            }

            $activity = $this->ActivityModel->save($activity, false, ['CheckSpam' => true]);
            if ($activity == SPAM || $activity == UNAPPROVED) {
                $this->StatusMessage = t('ActivityRequiresApproval', 'Your post will appear after it is approved.');
                $this->render('Blank', 'Utility');
                return;
            }

            if ($activity) {
                if ($userID == Gdn::session()->UserID && $notifyUserID == ActivityModel::NOTIFY_PUBLIC) {
                    Gdn::userModel()->setField(Gdn::session()->UserID, 'About', Gdn_Format::plainText($activity['Story'], $activity['Format']));
                }

                $activities = [$activity];
                ActivityModel::joinUsers($activities);
                $this->ActivityModel->calculateData($activities);
            } else {
                $this->Form->setValidationResults($this->ActivityModel->validationResults());

                $this->StatusMessage = $this->ActivityModel->Validation->resultsText();
//            $this->render('Blank', 'Utility');
            }
        }

        if ($this->deliveryType() == DELIVERY_TYPE_ALL) {
            $target = $this->Request->get('Target', '/activity');
            if (isSafeUrl($target)) {
                redirectTo($target);
            } else {
                redirectTo('/activity');
            }
        }

        $this->setData('Activities', $activities);
        $this->render('Activities');
    }
}
