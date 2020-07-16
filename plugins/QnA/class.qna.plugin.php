<?php
/**
 * @copyright 2009-2018 Vanilla Forums Inc.
 * @license GNU GPLv2 http://www.opensource.org/licenses/gpl-2.0.php
 */

use Garden\Container\Reference;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Vanilla\ApiUtils;
use Garden\Container\Container;
use Vanilla\QnA\Models\AnswerSearchType;
use Vanilla\QnA\Models\QuestionSearchType;
use Vanilla\QnA\Models\SearchRecordTypeQuestion;
use Vanilla\QnA\Models\SearchRecordTypeAnswer;
use Vanilla\Search\AbstractSearchDriver;

/**
 * Adds Question & Answer format to Vanilla.
 *
 * You can set Plugins.QnA.UseBigButtons = true in config to separate 'New Discussion'
 * and 'Ask Question' into "separate" forms each with own big button in Panel.
 */
class QnAPlugin extends Gdn_Plugin {

    /**
     * This is the name of the feature flag to display QnA tag in the discussion.
     */
    const FEATURE_FLAG = 'DiscussionQnATag';

    /** @var bool|array  */
    protected $Reactions = false;

    /** @var bool|array  */
    protected $Badges = false;

    /** @var bool */
    private $apiQuestionInsert = false;

    /** @var CommentModel */
    private $commentModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var UserModel */
    private $userModel;

    /** @var array Lookup cache for commentsApiController_normalizeOutput */
    public $discussionsCache = [];

    /**
     * QnAPlugin constructor.
     *
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     */
    public function __construct(CommentModel $commentModel, DiscussionModel $discussionModel, UserModel $userModel) {
        parent::__construct();

        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;

        if (Gdn::addonManager()->isEnabled('Reactions', \Vanilla\Addon::TYPE_ADDON) && c('Plugins.QnA.Reactions', true)) {
            $this->Reactions = true;
        }

        if ((Gdn::addonManager()->isEnabled('Reputation', \Vanilla\Addon::TYPE_ADDON) || Gdn::addonManager()->isEnabled('badges', \Vanilla\Addon::TYPE_ADDON))
            && c('Plugins.QnA.Badges', true)) {
            $this->Badges = true;
        }
    }

    /**
     * Run once on enable.
     */
    public function setup() {
        $this->structure();

        \Gdn::config()->touch('QnA.Points.Enabled', false);
        \Gdn::config()->touch('QnA.Points.Answer', 1);
        \Gdn::config()->touch('QnA.Points.AcceptedAnswer', 1);
    }

    /**
     * @param Container $dic
     */
    public function container_init(Container $dic) {
        /*
         * Register additional advanced search sphinx record types
         */
        $dic
            ->rule(Vanilla\Contracts\Search\SearchRecordTypeProviderInterface::class)
            ->addCall('setType', [new SearchRecordTypeQuestion()])
            ->addCall('setType', [new SearchRecordTypeAnswer()])
        ;

        $dic->rule(AbstractSearchDriver::class)
            ->addCall('registerSearchType', [new Reference(QuestionSearchType::class)])
            ->addCall('registerSearchType', [new Reference(AnswerSearchType::class)]);
    }

    /**
     * Add Javascript.
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_render_before($sender, $args) {
        if ($sender->MasterView == 'admin') {
            $sender->addJsFile('QnA.js', 'plugins/QnA');
        }
    }

    /**
     * Database updates.
     */
    public function structure() {
        include __DIR__.'/structure.php';
        \Gdn::config()->touch([
            'Preferences.Email.AnswerAccepted' => 1,
            'Preferences.Popup.AnswerAccepted' => 1,
            'Preferences.Email.QuestionAnswered' => 1,
            'Preferences.Popup.QuestionAnswered' => 1
        ]);
    }

    /**
     * Create a method called "QnA" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_qnA_create($sender) {
        // Prevent non-admins from accessing this page
        $sender->permission('Garden.Settings.Manage');

        $sender->title(sprintf(t('%s settings'), t('Q&A')));
        $sender->setData('PluginDescription', $this->getPluginKey('Description'));
        $sender->addSideMenu('settings/QnA');

        $sender->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);
        $configurationModel->setField([
            'QnA.Points.Enabled' => c('QnA.Points.Enabled', false),
            'QnA.Points.Answer' => c('QnA.Points.Answer', 1),
            'QnA.Points.AcceptedAnswer' => c('QnA.Points.AcceptedAnswer', 1),
        ]);
        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule('QnA.Points.Enabled', 'Boolean');

            if ($sender->Form->getFormValue('QnA.Points.Enabled')) {
                $configurationModel->Validation->applyRule('QnA.Points.Answer', 'Required');
                $configurationModel->Validation->applyRule('QnA.Points.Answer', 'Integer');

                $configurationModel->Validation->applyRule('QnA.Points.AcceptedAnswer', 'Required');
                $configurationModel->Validation->applyRule('QnA.Points.AcceptedAnswer', 'Integer');

                if ($sender->Form->getFormValue('QnA.Points.Answer') < 0) {
                    $sender->Form->setFormValue('QnA.Points.Answer', 0);
                }
                if ($sender->Form->getFormValue('QnA.Points.AcceptedAnswer') < 0) {
                    $sender->Form->setFormValue('QnA.Points.AcceptedAnswer', 0);
                }
            }

            if ($sender->Form->save() !== false) {
                // Update the AcceptAnswer reaction points.
                try {
                    if ($this->Reactions) {
                        $reactionModel = new ReactionModel();
                        $reactionModel->save([
                            'UrlCode' => 'AcceptAnswer',
                            'Points' => c('QnA.Points.AcceptedAnswer'),
                        ]);
                    }
                } catch(Exception $e) {
                    // Do nothing; no reaction was found to update so just press on.
                }
                $sender->StatusMessage = t('Your changes have been saved.');
            }
        }

        $sender->render($this->getView('configuration.php'));
    }

    /**
     * Trigger reaction or badge creation if those addons are enabled later.
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_addonEnabled_handler($sender, $args) {
        switch (strtolower($args['AddonName'])) {
            case 'reactions':
                $this->Reactions = true;
                $this->structure();
                break;
            case 'badges':
                $this->Badges = true;
                $this->structure();
                break;
        }
    }

    /**
     *
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_beforeCommentDisplay_handler($sender, $args) {
        $qnA = valr('Comment.QnA', $args);

        if ($qnA && isset($args['CssClass'])) {
            $args['CssClass'] = concatSep(' ', $args['CssClass'], "QnA-Item-$qnA");
        }
    }

    /**
     *
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionTypes_handler($sender, $args) {
        if (!c('Plugins.QnA.UseBigButtons')) {
            $args['Types']['Question'] = [
                'Singular' => 'Question',
                'Plural' => 'Questions',
                'AddUrl' => '/post/question',
                'AddText' => 'Ask a Question'
            ];
        }
    }

    /**
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_commentInfo_handler($sender, $args) {
        $type = val('Type', $args);
        if ($type != 'Comment') {
            return;
        }

        $qnA = valr('Comment.QnA', $args);

        if ($qnA && ($qnA == 'Accepted' || Gdn::session()->checkRankedPermission('Garden.Curation.Manage'))) {
            $title = t("QnA $qnA Answer", "$qnA Answer");
            echo ' <span class="Tag QnA-Box QnA-'.$qnA.'" title="'.htmlspecialchars($title).'"><span>'.$title.'</span></span> ';
        }
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_commentOptions_handler($sender, $args) {
        $comment = $args['Comment'];
        if (!$comment) {
            return;
        }
        $discussion = Gdn::controller()->data('Discussion');

        if (val('Type', $discussion) != 'Question') {
            return;
        }

        $permissionDiscussion = Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID);
        $permissionCuration = Gdn::session()->checkRankedPermission('Garden.Curation.Manage');
        if (!($permissionDiscussion || $permissionCuration)) {
            return;
        }
        $args['CommentOptions']['QnA'] = ['Label' => t('Q&A').'...', 'Url' => '/discussion/qnaoptions?commentid='.$comment->CommentID, 'Class' => 'Popup'];
    }

    /**
     *
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionOptions_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!Gdn::session()->checkPermission('Vanilla.Discussions.Edit', true, 'Category', $discussion->PermissionCategoryID)) {
            return;
        }

        if (isset($args['DiscussionOptions'])) {
            $args['DiscussionOptions']['QnA'] = ['Label' => t('Q&A').'...', 'Url' => '/discussion/qnaoptions?discussionid='.$discussion->DiscussionID, 'Class' => 'Popup'];
        } elseif (isset($sender->Options)) {
            $sender->Options .= '<li>'.anchor(t('Q&A').'...', '/discussion/qnaoptions?discussionid='.$discussion->DiscussionID, 'Popup QnAOptions') . '</li>';
        }
    }

    /**
     *
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function commentModel_beforeNotification_handler($sender, $args) {
        $activityModel = $args['ActivityModel'];
        $comment = (array)$args['Comment'];
        $commentID = $comment['CommentID'];
        $discussion = (array)$args['Discussion'];

        if ($comment['InsertUserID'] == $discussion['InsertUserID']) {
            return;
        }
        if (strtolower($discussion['Type']) != 'question') {
            return;
        }
        if (!c('Plugins.QnA.Notifications', true)) {
            return;
        }

        $headlineFormat = t('HeadlineFormat.Answer', '{ActivityUserID,user} answered your question: <a href="{Url,html}">{Data.Name,text}</a>');

        $activity = [
            'ActivityType' => 'Comment',
            'ActivityUserID' => $comment['InsertUserID'],
            'NotifyUserID' => $discussion['InsertUserID'],
            'HeadlineFormat' => $headlineFormat,
            'RecordType' => 'Comment',
            'RecordID' => $commentID,
            'Route' => "/discussion/comment/$commentID#Comment_$commentID",
            'Data' => [
                'Name' => val('Name', $discussion)
            ]
        ];

        $activityModel->queue($activity, 'QuestionAnswered');
        $activityModel->saveQueue();
    }

    /**
     *
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function commentModel_beforeUpdateCommentCount_handler($sender, $args) {
        // Merge the updated counts to the discussion.
        $discussion = $args['Counts'] + $args['Discussion'];

        // Bail out if this isn't a comment on a question.
        if (strtolower($discussion['Type']) !== 'question') {
            return;
        }

        // Recalculate the Question state.
        if (!empty($args['Insert'])) {
            $this->recalculateDiscussionQnA($discussion);
        }
    }

    /**
     * Write the accept/reject buttons.
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_afterCommentBody_handler($sender, $args) {
        $discussion = $sender->data('Discussion');
        $comment = val('Comment', $args);

        if (!$comment) {
            return;
        }

        $commentID = val('CommentID', $comment);
        if (!is_numeric($commentID)) {
            return;
        }

        if (!$discussion) {
            $discussion = $this->discussionModel->getID(val('DiscussionID', $comment));
        }

        if (!$discussion || strtolower(val('Type', $discussion)) != 'question') {
            return;
        }

        // Check permissions.
        $canAccept = Gdn::session()->checkRankedPermission('Garden.Curation.Manage');
        $canAccept |= Gdn::session()->UserID == val('InsertUserID', $discussion);

        if (!$canAccept) {
            return;
        }

        $qnA = val('QnA', $comment);
        if ($qnA) {
            return;
        }


        $query = http_build_query(['commentid' => $commentID, 'tkey' => Gdn::session()->transientKey()]);

        echo '<div class="ActionBlock QnA-Feedback">';

        echo '<span class="DidThisAnswer">'.t('Did this answer the question?').'</span> ';

        echo '<span class="QnA-YesNo">';

        echo anchor(t('Yes'), '/discussion/qna/accept?'.$query, ['class' => 'React QnA-Yes', 'title' => t('Accept this answer.')]);
        echo ' '.bullet().' ';
        echo anchor(t('No'), '/discussion/qna/reject?'.$query, ['class' => 'React QnA-No', 'title' => t('Reject this answer.')]);

        echo '</span>';

        echo '</div>';
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_afterDiscussion_handler($sender, $args) {
        if ($sender->data('Answers')) {
            include $sender->fetchViewLocation('Answers', '', 'plugins/QnA');
        }
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     *
     * @throws notFoundException
     */
    public function discussionController_qnA_create($sender, $args) {
        $comment = Gdn::sql()->getWhere('Comment', ['CommentID' => $sender->Request->get('commentid')])->firstRow(DATASET_TYPE_ARRAY);
        if (!$comment) {
            throw notFoundException('Comment');
        }

        $discussion = Gdn::sql()->getWhere('Discussion', ['DiscussionID' => $comment['DiscussionID']])->firstRow(DATASET_TYPE_ARRAY);

        // Check for permission.
        if (!(Gdn::session()->UserID == val('InsertUserID', $discussion) || Gdn::session()->checkRankedPermission('Garden.Curation.Manage'))) {
            throw permissionException('Garden.Curation.Manage');
        }
        if (!Gdn::session()->validateTransientKey($sender->Request->get('tkey'))) {
            throw permissionException();
        }

        switch ($args[0]) {
            case 'accept':
                $qna = 'Accepted';
                break;
            case 'reject':
                $qna = 'Rejected';
                break;
        }

        if (isset($qna)) {
            $discussionSet = ['QnA' => $qna];
            $CommentSet = ['QnA' => $qna];

            if ($qna == 'Accepted') {
                $CommentSet['DateAccepted'] = Gdn_Format::toDateTime();
                $CommentSet['AcceptedUserID'] = Gdn::session()->UserID;

                if (!$discussion['DateAccepted']) {
                    $discussionSet['DateAccepted'] = Gdn_Format::toDateTime();
                    $discussionSet['DateOfAnswer'] = $comment['DateInserted'];
                }
            }

            // Update the comment.
            Gdn::sql()->put('Comment', $CommentSet, ['CommentID' => $comment['CommentID']]);

            // Update the discussion.
           $this->recalculateDiscussionQnA($discussion);

            // Determine QnA change
            if ($comment['QnA'] != $qna) {
                $change = 0;
                switch ($qna) {
                    case 'Rejected':
                        $change = -1;
                        if ($comment['QnA'] != 'Accepted') {
                            $change = 0;
                        }
                        break;

                    case 'Accepted':
                        $change = 1;
                        break;

                    default:
                        if ($comment['QnA'] == 'Rejected') {
                            $change = 0;
                        }
                        if ($comment['QnA'] == 'Accepted') {
                            $change = -1;
                        }
                        break;
                }
            }

            // Apply change effects
            if ($change && $discussion['InsertUserID'] != $comment['InsertUserID']) {
                // Update the user
                $userID = val('InsertUserID', $comment);
                $this->recalculateUserQnA($userID);

                // Update reactions
                if ($this->Reactions) {
                    include_once(Gdn::controller()->fetchViewLocation('reaction_functions', '', 'plugins/Reactions'));
                    $reactionModel = new ReactionModel();

                    // Assume that the reaction is done by the question's owner
                    $questionOwner = $discussion['InsertUserID'];
                    // If there's change, reactions will take care of it
                    $reactionModel->react('Comment', $comment['CommentID'], 'AcceptAnswer', $questionOwner, true);
                } else {
                    $nbsPoint = $change * (int)c('QnA.Points.AcceptedAnswer', 1);
                    if ($nbsPoint && c('QnA.Points.Enabled', false)) {
                        CategoryModel::givePoints($comment['InsertUserID'], $nbsPoint, 'QnA', $discussion['CategoryID']);
                    }
                }
            }

            $headlineFormat = t('HeadlineFormat.AcceptAnswer', '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>');

            // Record the activity.
            if ($qna == 'Accepted') {
                $activity = [
                    'ActivityType' => 'AnswerAccepted',
                    'NotifyUserID' => $comment['InsertUserID'],
                    'HeadlineFormat' => $headlineFormat,
                    'RecordType' => 'Comment',
                    'RecordID' => $comment['CommentID'],
                    'Route' => commentUrl($comment, '/'),
                    'Data' => [
                        'Name' => val('Name', $discussion)
                    ]
                ];

                $ActivityModel = new ActivityModel();
                $ActivityModel->queue($activity, 'AnswerAccepted');
                $ActivityModel->saveQueue();

                $this->EventArguments['Activity'] =& $activity;
                $this->fireEvent('AfterAccepted');
            }
        }
        redirectTo("/discussion/comment/{$comment['CommentID']}#Comment_{$comment['CommentID']}", 302, false);
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param string|int $discussionID Identifier of the discussion
     * @param string|int $commentID Identifier of the comment.
     */
    public function discussionController_qnAOptions_create($sender, $discussionID = '', $commentID = '') {
        if ($discussionID) {
            $this->_discussionOptions($sender, $discussionID);
        } elseif ($commentID) {
            $this->_commentOptions($sender, $commentID);
        }
    }

    /**
     * Recalculate the QnA status of a discussion.
     *
     * @param array|object $discussion The discussion to recalculate.
     * @param bool $return Whether to return the result or update the discussion.
     * @return array $set (optional based on the $return argument). Discussion QnA data.
     */
    public function recalculateDiscussionQnA($discussion, bool $return = false) {
        $set = [];

        // Look for at least one accepted answer/comment.
        $acceptedComment = Gdn::sql()->getWhere(
            'Comment', ['DiscussionID' => val('DiscussionID', $discussion), 'QnA' => 'Accepted'], '', 'asc', 1
        )->firstRow(DATASET_TYPE_ARRAY);

        if ($acceptedComment) {
            $set['QnA'] = 'Accepted';
            $set['DateAccepted'] = $acceptedComment['DateAccepted'];
            $set['DateOfAnswer'] = $acceptedComment['DateInserted'];
        } else {
            // Look for at least one untreated answer/comment.
            $answeredComment = Gdn::sql()->getWhere(
                'Comment', ['DiscussionID' => val('DiscussionID', $discussion), 'QnA is null' => ''], '', 'asc', 1
            )->firstRow(DATASET_TYPE_ARRAY);

            $countComments = val('CountComments', $discussion, 0);

            if ($answeredComment) {
                $set['QnA'] = 'Answered';
                $set['DateAccepted'] = null;
            } else if ($countComments > 0) {
                $set['QnA'] = 'Rejected';
                $set['DateAccepted'] = null;
                $set['DateOfAnswer'] = null;
            } else {
                $set['QnA'] = 'Unanswered';
                $set['DateAccepted'] = null;
                $set['DateOfAnswer'] = null;
            }
        }

        if ($return) {
            return $set;
        }
        $this->discussionModel->setField(val('DiscussionID', $discussion), $set);
    }

    /**
     *
     *
     * @param string|int $userID User identifier
     */
    public function recalculateUserQnA($userID) {
        $countAcceptedAnswers = Gdn::sql()->getCount('Comment', ['InsertUserID' => $userID, 'QnA' => 'Accepted']);
        Gdn::userModel()->setField($userID, 'CountAcceptedAnswers', $countAcceptedAnswers);
    }

    /**
     *
     *
     * @param $sender controller instance.
     * @param int|string $commentID Identifier of the comment.
     *
     * @throws notFoundException
     */
    public function _commentOptions($sender, $commentID) {
        $sender->Form = new Gdn_Form();

        $comment = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

        if (!$comment) {
            throw notFoundException('Comment');
        }

        $discussion = $this->discussionModel->getID(val('DiscussionID', $comment), DATASET_TYPE_ARRAY);
        if (!Gdn::session()->checkRankedPermission('Garden.Curation.Manage')) {
            $sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion));
        }

        if ($sender->Form->authenticatedPostBack(true)) {
            $newQnA = $sender->Form->getFormValue('QnA');
            if (!$newQnA) {
                $newQnA = null;
            }

            $this->updateCommentQnA($discussion, $comment, $newQnA, $sender->Form);

            // Recalculate the Q&A status of the discussion.
            $this->recalculateDiscussionQnA($discussion);

            Gdn::controller()->jsonTarget('', '', 'Refresh');
        } else {
            $sender->Form->setData($comment);
        }

        $sender->setData('Comment', $comment);
        $sender->setData('Discussion', $discussion);
        $sender->setData('_QnAs', ['Accepted' => t('Yes'), 'Rejected' => t('No'), '' => t("Don't know")]);
        $sender->setData('Title', t('Q&A Options'));
        $sender->render('CommentOptions', '', 'plugins/QnA');
    }

    /**
     * Update a comment QnA data.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @param string|null $newQnA
     * @param Gdn_Form|null $form
     */
    protected function updateCommentQnA($discussion, $comment, $newQnA, Gdn_Form $form = null) {
        $currentQnA = val('QnA', $comment);

        if ($currentQnA != $newQnA) {
            $set = ['QnA' => $newQnA];

            if ($newQnA == 'Accepted') {
                $set['DateAccepted'] = Gdn_Format::toDateTime();
                $set['AcceptedUserID'] = Gdn::session()->UserID;
            } else {
                $set['DateAccepted'] = null;
                $set['AcceptedUserID'] = null;
            }

            $this->commentModel->setField(val('CommentID', $comment), $set);
            if ($form) {
                $form->setValidationResults($this->commentModel->validationResults());
            }

            // Determine QnA change
            if ($currentQnA != $newQnA) {
                $change = 0;
                switch ($newQnA) {
                    case 'Rejected':
                        $change = -1;
                        if ($currentQnA != 'Accepted') {
                            $change = 0;
                        }
                        break;

                    case 'Accepted':
                        $change = 1;
                        break;

                    default:
                        if ($currentQnA == 'Rejected') {
                            $change = 0;
                        }
                        if ($currentQnA == 'Accepted') {
                            $change = -1;
                        }
                        break;
                }
            }

            // Apply change effects
            if ($change && $discussion['InsertUserID'] != $comment['InsertUserID']) {
                // Update the user
                $userID = val('InsertUserID', $comment);
                $this->recalculateUserQnA($userID);

                // Update reactions
                if ($this->Reactions) {
                    include_once(Gdn::controller()->fetchViewLocation('reaction_functions', '', 'plugins/Reactions'));
                    $reactionModel = new ReactionModel();

                    // Assume that the reaction is done by the question's owner
                    $questionOwner = $discussion['InsertUserID'];
                    // If there's change, reactions will take care of it
                    $reactionModel->react('Comment', $comment['CommentID'], 'AcceptAnswer', $questionOwner, true);
                } else {
                    $nbsPoint = $change * (int)c('QnA.Points.AcceptedAnswer', 1);
                    if ($nbsPoint && c('QnA.Points.Enabled', false)) {
                        CategoryModel::givePoints($comment['InsertUserID'], $nbsPoint, 'QnA', $discussion['CategoryID']);
                    }
                }
            }
        }
    }

    /**
     *
     *
     * @param $sender controller instance.
     * @param int|string $discussionID Identifier of the discussion.
     *
     * @throws notFoundException
     */
    protected function _discussionOptions($sender, $discussionID) {
        $sender->Form = new Gdn_Form();

        $discussion = $this->discussionModel->getID($discussionID);

        if (!$discussion) {
            throw notFoundException('Discussion');
        }

        $sender->permission('Vanilla.Discussions.Edit', true, 'Category', val('PermissionCategoryID', $discussion));

        // Both '' and 'Discussion' denote a discussion type of discussion.
        if (!val('Type', $discussion)) {
            setValue('Type', $discussion, 'Discussion');
        }

        if ($sender->Form->authenticatedPostBack()) {
            $this->discussionModel->setField($discussionID, 'Type', $sender->Form->getFormValue('Type'));

            // Update the QnA field.  Default to "Unanswered" for questions. Null the field for other types.
            $qna = val('QnA', $discussion);
            switch ($sender->Form->getFormValue('Type')) {
                case 'Question':
                    $this->discussionModel->setField(
                        $discussionID,
                        'QnA',
                        $qna ? $qna : 'Unanswered'
                    );
                    break;
                default:
                    $this->discussionModel->setField($discussionID, 'QnA', null);
            }
            $sender->Form->setValidationResults($this->discussionModel->validationResults());

            Gdn::controller()->jsonTarget('', '', 'Refresh');
        } else {
            $sender->Form->setData($discussion);
        }

        $sender->setData('Discussion', $discussion);
        $sender->setData('_Types', ['Question' => '@'.t('Question Type', 'Question'), 'Discussion' => '@'.t('Discussion Type', 'Discussion')]);
        $sender->setData('Title', t('Q&A Options'));
        $sender->render('DiscussionOptions', '', 'plugins/QnA');
    }

    /**
     *
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_beforeGet_handler($sender, $args) {
        if (Gdn::controller()) {
            $unanswered = Gdn::controller()->ClassName == 'DiscussionsController' && Gdn::controller()->RequestMethod == 'unanswered';

            if ($unanswered) {
                $args['Wheres']['Type'] = 'Question';
                $args['Wheres']['Announce'] ='All';
                $this->discussionModel->SQL->beginWhereGroup()
                    ->where('d.QnA', null)
                    ->orWhereIn('d.QnA', ['Unanswered', 'Rejected'])
                    ->endWhereGroup();
                Gdn::controller()->title(t('Unanswered Questions'));
            } elseif ($qnA = Gdn::request()->get('qna')) {
                $args['Wheres']['QnA'] = $qnA;
            }
        }
    }

    /**
     *
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args) {
        if ($args['Insert']) {
            $post =& $args['FormPostValues'];

            if ($this->apiQuestionInsert) {
                $post['Type'] = 'Question';
            }

            if (val('Type', $post) == 'Question') {
                $post['QnA'] = 'Unanswered';
            }
        }
    }

    /**
     * New Html method of adding to discussion filters.
     *
     * @param $sender
     */
    public function base_afterDiscussionFilters_handler($sender) {
        $count = Gdn::cache()->get('QnA-UnansweredCount');
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count =
                '<span class="Aside">'
                    .'<span class="Popin Count" rel="/discussions/unansweredcount"></span>'
                .'</span>';
        } else {
            $count =
                '<span class="Aside">'
                    .'<span class="Count">'.$count.'</span>'
                .'</span>';
        }

        $extraClass = ($sender->RequestMethod == 'unanswered') ? 'Active' : '';
        $sprite = sprite('SpUnansweredQuestions');

        echo "<li class='QnA-UnansweredQuestions $extraClass'>"
                .anchor(
                    $sprite.' '.t('Unanswered').' '.$count,
                    '/discussions/unanswered',
                    'UnansweredQuestions'
                )
            .'</li>';
    }

    /**
     * Old Html method of adding to discussion filters.
     */
    public function discussionsController_afterDiscussionTabs_handler() {
        if (stringEndsWith(Gdn::request()->path(), '/unanswered', true)) {
            $cssClass = ' class="Active"';
        } else {
            $cssClass = '';
        }

        $count = Gdn::cache()->get('QnA-UnansweredCount');
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count = ' <span class="Popin Count" rel="/discussions/unansweredcount">';
        } else {
            $count = ' <span class="Count">'.$count.'</span>';
        }

        echo '<li'.$cssClass.'><a class="TabLink QnA-UnansweredQuestions" href="'.url('/discussions/unanswered').'">'.t('Unanswered Questions', 'Unanswered').$count.'</span></a></li>';
    }

    /**
     *
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unanswered_create($sender, $args) {
        $sender->View = 'Index';
        $sender->title(t('Unanswered Questions'));
        $sender->setData('_PagerUrl', 'discussions/unanswered/{Page}');

        // Be sure to display every unanswered question (ie from groups)
        $categories = CategoryModel::categories();

        $this->EventArguments['Categories'] = &$categories;
        $this->fireEvent('UnansweredBeforeSetCategories');

        $sender->setCategoryIDs(array_keys($categories));

        $sender->index(val(0, $args, 'p1'));
        $this->InUnanswered = true;
    }

    /**
     *
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_beforeBuildPager_handler($sender, $args) {
        if (Gdn::controller()->RequestMethod == 'unanswered') {
            $count = $this->getUnansweredCount(true);
            $sender->setData('CountDiscussions', $count);
        }
    }

    /**
     * Return the number of unanswered questions.
     *
     * @param bool $pager Whether this count affects the pager or not.
     * @return int
     */
    public function getUnansweredCount($pager = false) {
        // TODO: Dekludge this when category permissions are refactored (tburry).
        $cacheKey = Gdn::request()->webRoot().'/QnA-UnansweredCount';
        $questionCount = Gdn::cache()->get($cacheKey);

        if ($questionCount === Gdn_Cache::CACHEOP_FAILURE) {
            $questionCount = Gdn::sql()
                ->beginWhereGroup()
                ->where('QnA', null)
                ->orWhereIn('QnA', ['Unanswered', 'Rejected'])
                ->endWhereGroup()
                ->getCount('Discussion', ['Type' => 'Question']);

            Gdn::cache()->store($cacheKey, $questionCount, [Gdn_Cache::FEATURE_EXPIRY => 15 * 60]);
        }

        // Check to see if another plugin can handle this.
        $this->EventArguments['questionCount'] = &$questionCount;
        $this->EventArguments['pagerCount'] = $pager;
        $this->fireEvent('unansweredCount');

        return $questionCount;
    }

    /**
     * Displays the amounts of unanswered questions.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unanswered_render($sender, $args) {
        $sender->setData('CountDiscussions', false);

        // Add 'Ask a Question' button if using BigButtons.
        if (c('Plugins.QnA.UseBigButtons')) {
            $questionModule = new NewQuestionModule($sender, 'plugins/QnA');
            $sender->addModule($questionModule);
        }

        // Remove announcements that aren't questions...
        if (is_a($sender->data('Announcements'), 'Gdn_DataSet')) {
            $sender->data('Announcements')->result();
            $announcements = [];
            foreach ($sender->data('Announcements') as $i => $row) {
                if (val('Type', $row) == 'Question') {
                    $announcements[] = $row;
                }
            }
            trace($announcements);
            $sender->setData('Announcements', $announcements);
            $sender->AnnounceData = $announcements;
        }

        $sender->setData('Breadcrumbs', [['Name' => t('Unanswered'), 'Url' => '/discussions/unanswered']]);
    }

    /**
     * Displays the amounts of unanswered questions.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unansweredCount_create($sender, $args) {
        $count = $this->getUnansweredCount();

        $sender->setData('UnansweredCount', $count);
        $sender->setData('_Value', $count);
        $sender->render('Value', 'Utility', 'Dashboard');
    }

    /**
     * Print QnA meta data tag
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionMeta_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!empty($discussion)) {
            echo $this->getDiscussionQnATagString($discussion);
        }
    }

    /**
     * Print QnA meta data tag
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function discussionController_discussionInfo_handler($sender, $args) {
        $discussion = $args['Discussion'];
        if (!empty($discussion) && \Vanilla\FeatureFlagHelper::featureEnabled(static::FEATURE_FLAG)) {
            echo $this->getDiscussionQnATagString($discussion);
        }
    }

    /**
     * Return QnA meta data tag string
     *
     * @param object $discussion
     */
    private function getDiscussionQnATagString(object $discussion = null): string {
        $tag = '';
        if (strtolower(val('Type', $discussion)) != 'question') {
            return $tag;
        }

        $qnA = $discussion->QnA;
        $title = '';
        switch ($qnA) {
            case '':
            case 'Unanswered':
            case 'Rejected':
                $text = 'Question';
                $qnA = 'Question';
                break;
            case 'Answered':
                $text = 'Answered';
                if ($discussion->InsertUserID == Gdn::session()->UserID) {
                    $qnA = 'Answered';
                    $title = ' title="'.t("Someone's answered your question. You need to accept/reject the answer.").'"';
                }
                break;
            case 'Accepted':
                $text = 'Accepted Answer';
                $title = ' title="'.t("This question's answer has been accepted.").'"';
                break;
            default:
                $qnA = false;
        }
        if ($qnA) {
            $tag = '<span class="Tag QnA-Tag-'.$qnA.'" '.$title.'>'.t("Q&A $qnA", $text).'</span> ';
        }

        return $tag;
    }

    /**
     * Notifies the current user when one of his questions have been answered.
     *
     * @param NotificationsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function notificationsController_beforeInformNotifications_handler($sender, $args) {
        $path = trim($sender->Request->getValue('Path'), '/');
        if (preg_match('`^(vanilla/)?discussion[^s]`i', $path)) {
            return;
        }
    }

    /**
     * Add 'Ask a Question' button if using BigButtons.
     *
     * @param CategoriesController $sender Sending controller instance.
     */
    public function categoriesController_render_before($sender) {
        if (c('Plugins.QnA.UseBigButtons')) {
            $questionModule = new NewQuestionModule($sender, 'plugins/QnA');
            $sender->addModule($questionModule);
        }
    }

    /**
     * Add 'Ask a Question' button if using BigButtons and modify flow of discussion by pinning accepted answers.
     *
     * @param DiscussionController $sender Sending controller instance.
     */
    public function discussionController_render_before($sender) {
        if (c('Plugins.QnA.UseBigButtons')) {
            $questionModule = new NewQuestionModule($sender, 'plugins/QnA');
            $sender->addModule($questionModule);
        }

        if ($sender->data('Discussion.Type') == 'Question') {
            $sender->setData('_CommentsHeader', t('Answers'));
        }

        if ($sender->data('Discussion.QnA')) {
            $sender->CssClass .= ' Question';
        }

        if (strcasecmp($sender->data('Discussion.QnA'), 'Accepted') != 0) {
            return;
        }
    }

    /**
     * Set Answers to the discussion
     *
     * @param DiscussionController $sender
     */
    public function discussionController_beforeDiscussionRender_handler($sender) {
        // Find the accepted answer(s) to the question.
        $answers = $this->commentModel->getWhere(['DiscussionID' => $sender->data('Discussion.DiscussionID'), 'Qna' => 'Accepted'])->result();

        if (class_exists('ReplyModel')) {
            $replyModel = new ReplyModel();
            $discussion = null;
            $replyModel->joinReplies($discussion, $answers);
        }

        $sender->setData('Answers', $answers);

        // Remove the accepted answers from the comments.
        // Allow this to be skipped via config.
        if (c('QnA.AcceptedAnswers.Filter', true)) {
            if (isset($sender->Data['Comments'])) {
                $comments = $sender->Data['Comments']->result();
                $comments = array_filter($comments, function($row) {
                    return strcasecmp(val('QnA', $row), 'accepted');
                });
                $sender->Data['Comments'] = new Gdn_DataSet(array_values($comments));
            }
        }
    }

    /**
     * Add the question form to vanilla's post page.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_afterForms_handler($sender) {
        $forms = $sender->data('Forms');
        $forms[] = ['Name' => 'Question', 'Label' => sprite('SpQuestion').t('Ask a Question'), 'Url' => 'post/question'];
        $sender->setData('Forms', $forms);
    }

    /**
     * Update comment form filters on the post controller.
     *
     * @param PostController $sender
     * @return array
     */
    public function postController_initialize(PostController $sender) {
        $sender->CommentModel->addFilterField("QnA");
    }

    /**
     * Create the new question method on post controller.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_question_create($sender, $categoryUrlCode = '') {
        $categoryModel = new CategoryModel();
        if ($categoryUrlCode != '') {
            $category = (array)$categoryModel->getByCode($categoryUrlCode);
            $category = $categoryModel::permissionCategory($category);
            $isAllowedTypes = isset($category['AllowedDiscussionTypes']);
            $isAllowedQuestion = in_array('Question', $category['AllowedDiscussionTypes']);
        }

        if ($category && !$isAllowedQuestion && $isAllowedTypes) {
            $sender->Form->addError(t('You are not allowed to post a question in this category.'));
        }
        // Create & call PostController->discussion()
        $sender->View = PATH_PLUGINS . '/QnA/views/post.php';
        $sender->setData('Type', 'Question');
        $sender->discussion($categoryUrlCode);
    }

    /**
     * Override the PostController->discussion() method before render to use our view instead.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_beforeDiscussionRender_handler($sender) {
        // Override if we are looking at the question url.
        if ($sender->RequestMethod == 'question') {
            $sender->Form->addHidden('Type', 'Question');
            $sender->title(t('Ask a Question'));
            $sender->setData('Breadcrumbs', [['Name' => $sender->data('Title'), 'Url' => '/post/question']]);
        }
    }

    /**
     * Add 'New Question Form' location to Messages.
     */
    public function messageController_afterGetLocationData_handler($sender, $args) {
        $args['ControllerData']['Vanilla/Post/Question'] = t('New Question Form');
    }

    /**
     * Give point(s) to users for their first answer on an unanswered question!
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_afterSaveComment_handler($sender, $args) {
        if (!c('QnA.Points.Enabled', false) || !$args['Insert']) {
            return;
        }

        $discussion = $this->discussionModel->getID($args['CommentData']['DiscussionID'], DATASET_TYPE_ARRAY);

        $isCommentAnAnswer = $discussion['Type'] === 'Question';
        $isQuestionResolved = $discussion['QnA'] === 'Accepted';
        $isCurrentUserOriginalPoster = $discussion['InsertUserID'] == GDN::session()->UserID;
        if (!$isCommentAnAnswer || $isQuestionResolved || $isCurrentUserOriginalPoster) {
            return;
        }

        $userAnswersToQuestion = $this->commentModel->getWhere([
            'DiscussionID' => $args['CommentData']['DiscussionID'],
            'InsertUserID' => $args['CommentData']['InsertUserID'],
        ]);
        // Award point(s) only for the first answer to the question
        if ($userAnswersToQuestion->count() > 1) {
            return;
        }

        CategoryModel::givePoints(GDN::session()->UserID, c('QnA.Points.Answer', 1), 'QnA', $discussion['CategoryID']);
    }


    ##########################
    ## API Controller
    ###########################

    /**
     * The question's meta data schema.
     *
     * @return Schema
     */
    public function fullQuestionMetaDataSchema() {
        return Schema::parse([
            'status:s' => [
                'enum' => ['unanswered', 'answered', 'accepted', 'rejected'],
                'description' => 'The answering state of the question.'
            ],
            'dateAccepted:dt|n' => 'When an answer was accepted.',
            'dateAnswered:dt|n' => 'When the last answer was inserted.',
            "acceptedAnswers" => [
                "object" => "Accepted answers.",
                "type" => "array",
                "items" => Schema::parse([
                    "commentID" => [
                        "type" => "integer",
                        "description" => "Unique ID of the accepted answer's comment row."
                    ],
                    "body?" => [
                        "type" => "string",
                        "description" => "Rendered content of the answer."
                    ],
                ]),
            ]
        ]);
    }

    /**
     * Add question meta data to discussion schema.
     *
     * @param Schema $schema
     */
    public function discussionSchema_init(Schema $schema) {
        $attributes = $schema->getField('properties.attributes');

        // Add to an existing "attributes" field or create a new one?
        if ($attributes instanceof Schema) {
            $attributes->merge(Schema::parse([
                'question?' => $this->fullQuestionMetaDataSchema()
            ]));
        } else {
            $schema->merge(Schema::parse([
                'attributes?' => Schema::parse([
                    'question?' => $this->fullQuestionMetaDataSchema()
                ])
            ]));
        }
    }

    /**
     * Update the schema when getting a specific discussion from the API.
     *
     * @param Schema $schema
     */
    public function discussionGetSchema_init(Schema $schema) {
        // Add expanding a discussion's accepted answer content.
        $expand = $schema->getField("properties.expand.items.enum");
        $expand[] = "acceptedAnswers";
        $schema->setField("properties.expand.items.enum", $expand);
    }

    /**
     * Add question meta data to discussion record.
     *
     * @param array $discussion
     * @param DiscussionsApiController $discussionsApiController
     * @param array $options
     * @return array
     */
    public function discussionsApiController_normalizeOutput(
        array $discussion,
        DiscussionsApiController $discussionsApiController,
        array $options
    ) {
        if ($discussion['type'] !== 'question') {
            return $discussion;
        }

        $expandAnswers = $discussionsApiController->isExpandField("acceptedAnswers", $options["expand"] ?? []);
        $acceptedAnswers = [];
        $acceptedAnswerRows = $this->commentModel->getWhere([
            "DiscussionID" => $discussion["discussionID"],
            "Qna" => "Accepted"
        ])->resultArray();
        foreach ($acceptedAnswerRows as $comment) {
            $answer = [
                "commentID" => $comment["CommentID"],
            ];
            if ($expandAnswers) {
                $answer["body"] = Gdn_Format::to($comment["Body"], $comment["Format"]);
            }
            $acceptedAnswers[] = $answer;
        }

        $discussion['attributes']['question'] = [
            'status' => !empty($discussion['qnA']) ? strtolower($discussion['qnA']) : 'unanswered',
            'dateAccepted' => $discussion['dateAccepted'],
            'dateAnswered' => $discussion['dateOfAnswer'],
            "acceptedAnswers" => $acceptedAnswers,
        ];

        return $discussion;
    }

    /**
     * Create POST /discussions/question endpoint.
     *
     * @param DiscussionsApiController $sender
     * @param array $body
     * @return array
     */
    public function discussionsApiController_post_question(DiscussionsApiController $sender, array $body) {
        $this->apiQuestionInsert = true;
        try {
            // Type is added in discussionModel_beforeSaveDiscussion_handler
            return $sender->post($body);
        } finally {
            $this->apiQuestionInsert = false;
        }
    }

    /**
     * Add answer meta data to comment schema.
     *
     * @return Schema
     */
    public function fullAnswerMetaDataSchema() {
        return Schema::parse([
            'status:s' => [
                'enum' => ['accepted', 'rejected', 'pending'],
                'description' => 'The state of the answer.'
            ],
            'dateAccepted:dt|n' => 'When an answer was accepted.',
            'acceptUserID:i|n' => 'The user that accepted this answer.'
        ]);
    }

    /**
     * Add answer meta data to comment schema.
     *
     * @param Schema $schema
     */
    public function commentSchema_init(Schema $schema) {
        $schema->merge(Schema::parse([
            'attributes' => Schema::parse([
                'answer?' => $this->fullAnswerMetaDataSchema(),
            ]),
        ]));
    }

    /**
     * Add answer meta data to comment record.
     *
     * @param array $comment
     * @param CommentsApiController $commentsApiController
     * @param array $options
     * @return array
     */
    public function commentsApiController_normalizeOutput(
        array $comment,
        CommentsApiController $commentsApiController,
        array $options
    ) {
        $discussionID = $comment['discussionID'];

        if (!isset($this->discussionsCache[$discussionID])) {
            // This has the potential to be pretty bad, performance wise, so we at least cached the results.
            $this->discussionsCache[$discussionID] = $commentsApiController->discussionByID($discussionID);
        }
        $discussion = $this->discussionsCache[$discussionID];

        if ($discussion['Type'] !== 'Question') {
            return $comment;
        }

        $comment['attributes']['answer'] = [
            'status' => !empty($comment['qnA']) ? strtolower($comment['qnA']) : 'pending',
            'dateAccepted' => $comment['dateAccepted'],
            'acceptUserID' => $comment['acceptedUserID'],
        ];

        return $comment;
    }

    /**
     * Create PATCH /comments/answer endpoint.
     *
     * @throws ClientException
     * @param CommentsApiController $sender
     * @param int $id
     * @param array $body
     * @return array
     */
    public function commentsApiController_patch_answer(CommentsApiController $sender, $id, array $body) {
        $sender->permission('Garden.SignIn.Allow');

        $sender->idParamSchema('in');
        $in = $sender->schema(
            Schema::parse([
                'status'
            ])->add($this->fullAnswerMetaDataSchema()),
            'AnswerPatch'
        )->setDescription('Update an answer\'s metadata.');
        $out = $sender->commentSchema('out');

        $body = $in->validate($body);
        $data = ApiUtils::convertInputKeys($body);
        $data['CommentID'] = $id;
        $comment = $sender->commentByID($id);
        $discussion = $sender->discussionByID($comment['DiscussionID']);

        if ($discussion['Type'] !== 'Question') {
            throw new ClientException('The comment is not an answer.');
        }

        if ($discussion['InsertUserID'] !== $sender->getSession()->UserID) {
            $this->discussionModel->categoryPermission('Vanilla.Discussions.Edit', $discussion['CategoryID']);
        }

        // Body is a required field in CommentModel::save.
        if (!array_key_exists('Body', $data)) {
            $data['Body'] = $comment['Body'];
        }

        $status = ucFirst($body['status']);
        if ($status === 'Pending') {
            $status = null;
        }
        $this->updateCommentQnA($discussion, $comment, $status);
        $sender->validateModel($this->commentModel);

        $this->recalculateDiscussionQnA($discussion);

        $row = $sender->commentByID($id);
        $this->userModel->expandUsers($row, ['InsertUserID']);
        $row = $sender->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add QnA fields to the search schema.
     *
     * @param Schema $schema
     */
    public function searchResultSchema_init(Schema $schema) {
        $types = $schema->getField('properties.type.enum');
        $types[] = 'question';
        $types[] = 'answer';
        $schema->setField('properties.type.enum', $types);
    }

    /**
     * Add option to dba/counts to recalculate QnA state of discussions(questions).
     *
     * @param DBAController $sender
     */
    public function dbaController_countJobs_handler($sender) {
        $name = "Recalculate Discussion.QnA";
        // We name the table QnA and not Discussion because the model is instantiated from the table name in DBAModel.
        $url = "/dba/counts.json?" . http_build_query(['table' => 'QnA', 'column' => 'QnA']);
        $sender->Data['Jobs'][$name] = $url;
    }

    /**
     * Adds status notification options to profiles.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender) {
        $sender->Preferences['Notifications']['Email.AnswerAccepted'] = t('Notify me when people accept my answer.');
        $sender->Preferences['Notifications']['Popup.AnswerAccepted'] = t('Notify me when people accept my answer.');
        $sender->Preferences['Notifications']['Email.QuestionAnswered'] = t('Notify me when people answer my question.');
        $sender->Preferences['Notifications']['Popup.QuestionAnswered'] = t('Notify me when people answer my question.');
    }
}
