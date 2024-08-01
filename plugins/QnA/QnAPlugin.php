<?php
/**
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GNU GPLv2 http://www.opensource.org/licenses/gpl-2.0.php
 */

use Garden\Container\Container;
use Garden\Container\Reference;
use Garden\EventManager;
use Garden\PsrEventHandlersInterface;
use Garden\Schema\Schema;
use Garden\Web\Exception\ClientException;
use Garden\Web\Exception\NotFoundException;
use Gdn_Session as SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Vanilla\ApiUtils;
use Vanilla\Community\Events\DiscussionStatusEvent;
use Vanilla\Zendesk\Events\ZendeskArticleDiscussionEvent;
use Vanilla\Dashboard\Models\RecordStatusModel;
use Vanilla\Dashboard\Models\RecordStatusStructureEvent;
use Vanilla\Exception\PermissionException;
use Vanilla\Formatting\DateTimeFormatter;
use Vanilla\Formatting\FormatService;
use Vanilla\Forum\Modules\QnAWidgetModule;
use Vanilla\QnA\Models\AnswerModel;
use Vanilla\QnA\Models\AnswerSearchType;
use Vanilla\QnA\Models\QnAJsonLD;
use Vanilla\QnA\Models\QuestionSearchType;
use Vanilla\Search\SearchTypeCollectorInterface;
use Vanilla\Utility\ModelUtils;
use Vanilla\Widgets\WidgetService;

/**
 * Adds Question & Answer format to Vanilla.
 *
 * You can set Plugins.QnA.UseBigButtons = true in config to separate 'New Discussion'
 * and 'Ask Question' into "separate" forms each with own big button in Panel.
 */
class QnAPlugin extends Gdn_Plugin implements LoggerAwareInterface, PsrEventHandlersInterface
{
    use LoggerAwareTrait;

    /**
     * This is the name of the feature flag to display QnA tag in the discussion.
     */
    const FEATURE_FLAG = "DiscussionQnATag";

    /** @var string key used when normalizing a record */
    const QNA_KEY = "qnA";

    /** @var int */
    private const ANSWERED_LIMIT = 100;

    /** @var int Maximum unanswered ideas to be counted. */
    public const UNANSWERED_COUNT_LIMIT_DEFAULT = 100;

    /** @var int Interval in which follow up feature triggers */
    private $followUpInterval = 7;

    /** @var int Threshold in which follow up stops checking statuses */
    private $followUpThreshold = 60;

    /** @var int Threshold timeout when sending emails */
    private const EMAIL_TIMEOUT_THRESHOLD = 30;

    /** @var bool|array */
    protected $Reactions = false;

    /** @var bool|array */
    protected $Badges = false;

    /** @var bool */
    private $apiQuestionInsert = false;

    /** @var Gdn_Database */
    private $database;

    /** @var CommentModel */
    private $commentModel;

    /** @var DiscussionModel */
    private $discussionModel;

    /** @var CategoryModel */
    private $categoryModel;

    /** @var UserModel */
    private $userModel;

    /** @var AnswerModel */
    private $answerModel;

    /** @var array Lookup cache for commentsApiController_normalizeOutput */
    public $discussionsCache = [];

    /** @var SessionInterface */
    private $session;

    /** @var int */
    private $unansweredCountLimit = self::UNANSWERED_COUNT_LIMIT_DEFAULT;

    /** @var FormatService */
    private $formatService;

    /** @var DiscussionStatusModel */
    private $discussionStatusModel;

    /** @var RecordStatusModel */
    private $recordStatusModel;

    /** @var QnaModel */
    private $qnaModel;

    public const DISCUSSION_STATUS_UNANSWERED = 1;
    public const DISCUSSION_STATUS_ANSWERED = 2;
    public const DISCUSSION_STATUS_ACCEPTED = 3;
    public const DISCUSSION_STATUS_REJECTED = 4;
    public const COMMENT_STATUS_ACCEPTED = 5;
    public const COMMENT_STATUS_REJECTED = 6;

    /** @var array $pluginDefinedRecordStatusIDs */
    public static $pluginDefinedRecordStatusIDs = [
        self::DISCUSSION_STATUS_UNANSWERED,
        self::DISCUSSION_STATUS_ANSWERED,
        self::DISCUSSION_STATUS_ACCEPTED,
        self::DISCUSSION_STATUS_REJECTED,
        self::COMMENT_STATUS_ACCEPTED,
        self::COMMENT_STATUS_REJECTED,
    ];

    /** @var array */
    private const DEFAULT_QUESTION_UNANSWERED_STATUS = [
        "statusID" => self::DISCUSSION_STATUS_UNANSWERED,
        "name" => "Unanswered",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 1,
        "isSystem" => 1,
    ];
    /** @var array */
    private const DEFAULT_QUESTION_ANSWERED_STATUS = [
        "statusID" => self::DISCUSSION_STATUS_ANSWERED,
        "name" => "Answered",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    private const DEFAULT_QUESTION_ACCEPTED_STATUS = [
        "statusID" => self::DISCUSSION_STATUS_ACCEPTED,
        "name" => "Accepted",
        "state" => "closed",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_QUESTION_REJECTED_STATUS = [
        "statusID" => self::DISCUSSION_STATUS_REJECTED,
        "name" => "Rejected",
        "state" => "open",
        "recordType" => "discussion",
        "recordSubtype" => "question",
        "isDefault" => 0,
        "isSystem" => 1,
    ];

    /** @var array */
    protected const DEFAULT_COMMENT_ACCEPTED_STATUS = [
        "statusID" => self::COMMENT_STATUS_ACCEPTED,
        "name" => "Accepted",
        "state" => "closed",
        "recordType" => "comment",
        "recordSubtype" => "answer",
        "isDefault" => 0,
        "isSystem" => 1,
    ];
    /** @var array */
    protected const DEFAULT_COMMENT_REJECTED_STATUS = [
        "statusID" => self::COMMENT_STATUS_REJECTED,
        "name" => "Rejected",
        "state" => "closed",
        "recordType" => "comment",
        "recordSubtype" => "answer",
        "isDefault" => 0,
        "isSystem" => 1,
    ];

    /** @var array[] DEFAULT_STATUSES */
    protected const DEFAULT_STATUSES = [
        self::DEFAULT_QUESTION_UNANSWERED_STATUS,
        self::DEFAULT_QUESTION_ANSWERED_STATUS,
        self::DEFAULT_QUESTION_ACCEPTED_STATUS,
        self::DEFAULT_QUESTION_REJECTED_STATUS,
        self::DEFAULT_COMMENT_ACCEPTED_STATUS,
        self::DEFAULT_COMMENT_REJECTED_STATUS,
    ];

    /**
     * QnAPlugin constructor.
     *
     * @param CommentModel $commentModel
     * @param DiscussionModel $discussionModel
     * @param UserModel $userModel
     * @param CategoryModel $categoryModel
     * @param AnswerModel $questionModel
     * @param Gdn_Session $session
     * @param FormatService $formatService
     * @param DiscussionStatusModel $discussionStatusModel
     * @param RecordStatusModel $recordStatusModel
     * @param Gdn_Database $database
     * @param QnaModel $qnaModel
     */
    public function __construct(
        CommentModel $commentModel,
        DiscussionModel $discussionModel,
        UserModel $userModel,
        CategoryModel $categoryModel,
        AnswerModel $questionModel,
        SessionInterface $session,
        FormatService $formatService,
        DiscussionStatusModel $discussionStatusModel,
        RecordStatusModel $recordStatusModel,
        Gdn_Database $database,
        QnaModel $qnaModel,
        EventManager $eventManager
    ) {
        parent::__construct();

        $this->commentModel = $commentModel;
        $this->discussionModel = $discussionModel;
        $this->userModel = $userModel;
        $this->categoryModel = $categoryModel;
        $this->answerModel = $questionModel;
        $this->session = $session;
        $this->formatService = $formatService;
        $this->discussionStatusModel = $discussionStatusModel;
        $this->recordStatusModel = $recordStatusModel;
        $this->database = $database;
        $this->qnaModel = $qnaModel;
        $this->eventManager = $eventManager;
        $this->setLogger(Logger::getLogger());
        if (c("Plugins.QnA.Reactions", true)) {
            $this->Reactions = true;
        }

        if (
            (Gdn::addonManager()->isEnabled("Reputation", \Vanilla\Addon::TYPE_ADDON) ||
                Gdn::addonManager()->isEnabled("badges", \Vanilla\Addon::TYPE_ADDON)) &&
            c("Plugins.QnA.Badges", true)
        ) {
            $this->Badges = true;
        }

        //set followup values
        $this->setFollowUpInterval(c("QnA.FollowUp.Interval"));

        //set allowed query string values for modifying pager links using discussion list filters
        $filterKeys = array_map("strtolower", [QnaModel::ACCEPTED, QnaModel::ANSWERED, QnaModel::UNANSWERED]);
        $this->discussionModel->addFilterSet("qna", "All Questions", [], false);
        foreach ($filterKeys as $filterKey) {
            $this->discussionModel->addFilter($filterKey, "qna", [], "", "qna");
        }
    }

    /**
     * Run once on enable.
     */
    public function setup()
    {
        \Gdn::config()->touch("QnA.Points.Enabled", false);
        \Gdn::config()->touch("QnA.Points.Answer", 1);
        \Gdn::config()->touch("QnA.Points.AcceptedAnswer", 1);
    }

    /**
     * {@inheritDoc}
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return [
            "handleDiscussionStatusEvent",
            "handleRecordStatusStructureEvent",
            "handleZendeskArticleDiscussionEvent",
        ];
    }

    /**
     * @param Container $dic
     */
    public function container_init(Container $dic)
    {
        $dic->rule(SearchTypeCollectorInterface::class)
            ->addCall("registerSearchType", [new Reference(QuestionSearchType::class)])
            ->addCall("registerSearchType", [new Reference(AnswerSearchType::class)]);

        $dic->rule(WidgetService::class)->addCall("registerWidget", [QnAWidgetModule::class]);

        $dic->rule(\Vanilla\DiscussionTypeConverter::class)->addCall("addTypeHandler", [
            new Reference(QnaTypeHandler::class),
        ]);
    }

    /**
     * Database updates.
     */
    public function structure()
    {
        include __DIR__ . "/structure.php";
        // Create this plugin's default statuses to insert into the recordStatus table.
        foreach (self::DEFAULT_STATUSES as $default) {
            $this->recordStatusModel->processDefaultStatuses($this->database, $default, true);
        }

        $this->recordStatusModel->structureActiveStates();
        \Gdn::config()->touch([
            "Preferences.Email.AnswerAccepted" => 1,
            "Preferences.Popup.AnswerAccepted" => 1,
            "Preferences.Email.QuestionAnswered" => 1,
            "Preferences.Popup.QuestionAnswered" => 1,
        ]);

        if ($this->questionFollowUpFeatureEnabled()) {
            \Gdn::config()->touch([
                "Preferences.Email.QuestionFollowUp" => 1,
            ]);
        }
    }

    /**
     * Create a method called "QnA" on the SettingController.
     *
     * @param $sender Sending controller instance
     */
    public function settingsController_qnA_create($sender)
    {
        // Prevent non-admins from accessing this page
        $sender->permission("Garden.Settings.Manage");

        $sender->title(sprintf(t("%s settings"), t("Q&A")));
        $sender->setData("PluginDescription", $this->getPluginKey("Description"));
        $sender->addSideMenu("settings/QnA");

        $sender->Form = new Gdn_Form();
        $validation = new Gdn_Validation();
        $configurationModel = new Gdn_ConfigurationModel($validation);

        $configurationModel->setField([
            "QnA.Points.Enabled" => c("QnA.Points.Enabled", false),
            "QnA.Points.Answer" => c("QnA.Points.Answer", 1),
            "QnA.Points.AcceptedAnswer" => c("QnA.Points.AcceptedAnswer", 1),
            "QnA.FollowUp.Enabled" => c("QnA.FollowUp.Enabled", false),
            "QnA.FollowUp.Interval" => c("QnA.FollowUp.Interval", $this->getFollowUpInterval()),
        ]);

        $sender->Form->setModel($configurationModel);

        // If seeing the form for the first time...
        if ($sender->Form->authenticatedPostBack() === false) {
            $sender->Form->setData($configurationModel->Data);
        } else {
            $configurationModel->Validation->applyRule("QnA.Points.Enabled", "Boolean");

            if ($sender->Form->getFormValue("QnA.Points.Enabled")) {
                $configurationModel->Validation->applyRule("QnA.Points.Answer", "Required");
                $configurationModel->Validation->applyRule("QnA.Points.Answer", "Integer");

                $configurationModel->Validation->applyRule("QnA.Points.AcceptedAnswer", "Required");
                $configurationModel->Validation->applyRule("QnA.Points.AcceptedAnswer", "Integer");

                if ($sender->Form->getFormValue("QnA.Points.Answer") < 0) {
                    $sender->Form->setFormValue("QnA.Points.Answer", 0);
                }
                if ($sender->Form->getFormValue("QnA.Points.AcceptedAnswer") < 0) {
                    $sender->Form->setFormValue("QnA.Points.AcceptedAnswer", 0);
                }
            }

            if ($sender->Form->getFormValue("QnA.FollowUp.Enabled")) {
                //add custom validation rule
                $configurationModel->Validation->addRule("validatePositiveNumber", "function:validatePositiveNumber");

                $configurationModel->Validation->applyRule("QnA.FollowUp.Interval", "Required");
                $configurationModel->Validation->applyRule("QnA.FollowUp.Interval", "Integer");
                $configurationModel->Validation->applyRule(
                    "QnA.FollowUp.Interval",
                    "validatePositiveNumber",
                    sprintf(t("%s must be a positive number."), t("Interval"))
                );
            }

            if ($sender->Form->save() !== false) {
                // Update the AcceptAnswer reaction points.
                try {
                    if ($this->Reactions) {
                        $reactionModel = new ReactionModel();
                        $reactionModel->save([
                            "UrlCode" => "AcceptAnswer",
                            "Points" => c("QnA.Points.AcceptedAnswer"),
                        ]);
                    }
                } catch (Exception $e) {
                    // Do nothing; no reaction was found to update so just press on.
                }

                //run structure to set DB/notification updates
                if ($this->questionFollowUpFeatureEnabled()) {
                    $this->structure();
                }

                $sender->StatusMessage = t("Your changes have been saved.");
            }
        }

        $sender->render($this->getView("configuration.php"));
    }

    /**
     * Trigger reaction or badge creation if those addons are enabled later.
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_addonEnabled_handler($sender, $args)
    {
        switch (strtolower($args["AddonName"])) {
            case "badges":
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
    public function base_beforeCommentDisplay_handler($sender, $args)
    {
        $qnA = valr("Comment.QnA", $args);

        if ($qnA && isset($args["CssClass"])) {
            $args["CssClass"] = concatSep(" ", $args["CssClass"], "QnA-Item-$qnA");
        }
    }

    /**
     * Add accepted answer to the Zendesk article body.
     *
     * @param array $record
     * @return array
     */
    public function handleZendeskArticleDiscussionEvent(
        ZendeskArticleDiscussionEvent $zendeskArticleDiscussionEvent
    ): ZendeskArticleDiscussionEvent {
        if ($zendeskArticleDiscussionEvent->getDiscussionType() === "question") {
            $acceptedAnswers = $this->getDiscussionAnswersByType(
                $zendeskArticleDiscussionEvent->getDiscussionID(),
                "accepted"
            );
            if (empty($acceptedAnswers)) {
                return $zendeskArticleDiscussionEvent;
            }
            $acceptedAnswerBody = "<p>";
            $acceptedAnswerBody .= "<h2>" . plural(count($acceptedAnswers), "Answer", "Answers") . "</h2>";
            foreach ($acceptedAnswers as $answer) {
                $acceptedAnswerBody .= $answer["body"];
            }
            $acceptedAnswerBody .= "</p>";
            $zendeskArticleDiscussionEvent->appendToDiscussionBody($acceptedAnswerBody);
        }
        return $zendeskArticleDiscussionEvent;
    }

    /**
     * Add the "Ask a Question" option to the new post menu.
     *
     * @param Gdn_Controller $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionTypes_handler($sender, $args)
    {
        $category = val("Category", $args);
        if (empty($category) || !c("Plugins.QnA.UseBigButtons")) {
            $args["Types"]["Question"] = [
                "layoutViewType" => "questionThread",
                "apiType" => "question",
                "Singular" => "Question",
                "Plural" => "Questions",
                "AddUrl" => "/post/question",
                "AddText" => "Ask a Question",
                "AddIcon" => "new-question",
            ];
        }
    }

    /**
     *
     * @param $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_commentInfo_handler($sender, $args)
    {
        $type = val("Type", $args);
        if ($type != "Comment") {
            return;
        }

        $qnA = valr("Comment.QnA", $args);

        if ($qnA && ($qnA == "Accepted" || Gdn::session()->checkRankedPermission("Garden.Curation.Manage"))) {
            $title = t("QnA $qnA Answer", "$qnA Answer");
            echo ' <span class="Tag QnA-Box QnA-' .
                $qnA .
                '" title="' .
                htmlspecialchars($title) .
                '"><span>' .
                $title .
                "</span></span> ";
        }
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_commentOptions_handler($sender, $args)
    {
        $comment = $args["Comment"];
        if (!$comment) {
            return;
        }
        $discussion = Gdn::controller()->data("Discussion");

        if (val("Type", $discussion) != "Question") {
            return;
        }

        $permissionDiscussion = Gdn::session()->checkPermission(
            "Vanilla.Discussions.Edit",
            true,
            "Category",
            $discussion->PermissionCategoryID
        );
        $permissionCuration = Gdn::session()->checkRankedPermission("Garden.Curation.Manage");
        if (!($permissionDiscussion || $permissionCuration)) {
            return;
        }
        $args["CommentOptions"]["QnA"] = [
            "Label" => t("Q&A") . "...",
            "Url" => "/discussion/qnaoptions?commentid=" . $comment->CommentID,
            "Class" => "Popup",
        ];
    }

    /**
     * Update items in discussion options dropdown menu.
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_discussionOptions_handler($sender, $args)
    {
        $discussion = $args["Discussion"];

        if (
            !Gdn::session()->checkPermission(
                "Vanilla.Discussions.Edit",
                true,
                "Category",
                $discussion->PermissionCategoryID
            )
        ) {
            return;
        }

        if (isset($args["DiscussionOptions"])) {
            $args["DiscussionOptions"]["QnA"] = [
                "Label" => t("Q&A") . "...",
                "Url" => "/discussion/qnaoptions?discussionid=" . $discussion->DiscussionID,
                "Class" => "Popup",
            ];
        } elseif (isset($sender->Options)) {
            $sender->Options .=
                "<li>" .
                anchor(
                    t("Q&A") . "...",
                    "/discussion/qnaoptions?discussionid=" . $discussion->DiscussionID,
                    "Popup QnAOptions"
                ) .
                "</li>";
        }

        // add option for follow up notification endpoint manual trigger
        if (
            strtolower($sender->ControllerName) === "discussioncontroller" &&
            $this->isFollowUpOptionAvailable($discussion)
        ) {
            $args["DiscussionOptions"]["QnAFollowUp"] = [
                "Label" => t("Send Q&A Follow-up Email"),
                "Url" => "/api/v2/discussions/question-notifications",
                "Class" => "QnAFollowUp",
            ];
        }
    }

    /**
     * Check discussion conditions for followup notification
     *
     * @param stdClass $discussion
     * @return bool
     */
    private function isFollowUpOptionAvailable($discussion)
    {
        $session = Gdn::session();
        $isFollowUpOptionAvailable = true;

        if (!$session->checkPermission("Garden.Community.Manage")) {
            $isFollowUpOptionAvailable = false;
        }

        if ($discussion->statusID !== self::DISCUSSION_STATUS_ANSWERED) {
            $isFollowUpOptionAvailable = false;
        }

        if (!$this->questionFollowUpFeatureEnabled()) {
            $isFollowUpOptionAvailable = false;
        }

        if (!$this->categoryAllowFollowUpNotification($discussion)) {
            $isFollowUpOptionAvailable = false;
        }

        return $isFollowUpOptionAvailable;
    }

    /**
     *
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function commentModel_beforeNotification_handler($sender, $args)
    {
        $activityModel = $args["ActivityModel"];
        $comment = (array) $args["Comment"];
        $commentID = $comment["CommentID"];
        $discussion = (array) $args["Discussion"];
        $categoryID = $discussion["CategoryID"];
        $category = CategoryModel::categories($categoryID);
        $existingActivity = $args["Activity"];

        if ($comment["InsertUserID"] == $discussion["InsertUserID"]) {
            return;
        }
        if (strtolower($discussion["Type"]) != "question") {
            return;
        }
        if (!c("Plugins.QnA.Notifications", true)) {
            return;
        }

        $headlineFormat = t(
            "HeadlineFormat.Answer",
            '{ActivityUserID,user} answered your question: <a href="{Url,html}">{Data.Name,text}</a>'
        );

        $pluralHeadline = t(
            "PluralHeadlineFormat.Answer",
            'There are <strong>{count}</strong> new answers to your question: <a href="{Url,html}">{Data.Name,text}</a>'
        );

        $activity = [
            "ActivityType" => "QuestionAnswer",
            "ActivityUserID" => $comment["InsertUserID"],
            "ActivityEventID" => $existingActivity["ActivityEventID"],
            "NotifyUserID" => $discussion["InsertUserID"],
            "HeadlineFormat" => $headlineFormat,
            "PluralHeadlineFormat" => $pluralHeadline,
            "RecordType" => "Comment",
            "RecordID" => $commentID,
            "ParentRecordID" => $discussion["DiscussionID"],
            "Route" => "/discussion/comment/$commentID#Comment_$commentID",
            "Data" => [
                "Name" => val("Name", $discussion),
                "Category" => $category["Name"] ?? null,
                "Reason" => "questionAnswered",
            ],
        ];

        $activityModel->save($activity, "QuestionAnswered");
    }

    /**
     * Handle a comment being inserted or deleted.
     *
     * @param array{comment: array, discussion: array} $args Event arguments.
     */
    public function forumAggregateModel_comment_handler(array $args)
    {
        $discussion = $args["discussion"];

        // Bail out if this isn't a comment on a question.
        if (strtolower($discussion["Type"]) !== "question") {
            return;
        }

        // Recalculate the Question state.
        $this->recalculateDiscussionQnA($discussion);
    }

    /**
     * Write the accept/reject buttons.
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_afterCommentBody_handler($sender, $args)
    {
        $discussion = $sender->data("Discussion");
        $comment = val("Comment", $args);

        if (!$comment) {
            return;
        }

        $commentID = val("CommentID", $comment);
        if (!is_numeric($commentID)) {
            return;
        }

        if (!$discussion) {
            $discussion = $this->discussionModel->getID(val("DiscussionID", $comment));
        }

        if (!$discussion || strtolower(val("Type", $discussion)) != "question") {
            return;
        }

        // Check permissions.
        $canAccept = Gdn::session()->UserID == val("InsertUserID", $discussion) && !$discussion->Closed;
        $canAccept |=
            Gdn::session()
                ->getPermissions()
                ->hasRanked("Garden.Curation.Manage") && !$discussion->Closed;
        $canAccept |= Gdn::session()
            ->getPermissions()
            ->hasRanked("Garden.Moderation.Manage");

        if (!$canAccept) {
            return;
        }

        $qnA = val("QnA", $comment);
        if ($qnA) {
            return;
        }

        $query = http_build_query(["commentid" => $commentID, "tkey" => Gdn::session()->transientKey()]);

        echo '<div class="ActionBlock QnA-Feedback">';

        echo '<span class="DidThisAnswer">' . t("Did this answer the question?") . "</span> ";

        echo '<span class="QnA-YesNo">';

        echo anchor(t("Yes"), "/discussion/qna/accept?" . $query, [
            "class" => "React QnA-Yes",
            "title" => t("Accept this answer."),
        ]);
        echo " " . bullet() . " ";
        echo anchor(t("No"), "/discussion/qna/reject?" . $query, [
            "class" => "React QnA-No",
            "title" => t("Reject this answer."),
        ]);

        echo "</span>";

        echo "</div>";
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionController_afterDiscussion_handler($sender, $args)
    {
        if ($sender->data("Answers")) {
            include $sender->fetchViewLocation("Answers", "", "plugins/QnA");
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
    public function discussionController_qnA_create($sender, $args)
    {
        $comment = Gdn::sql()
            ->getWhere("Comment", ["CommentID" => $sender->Request->get("commentid")])
            ->firstRow(DATASET_TYPE_ARRAY);
        if (!$comment) {
            throw notFoundException("Comment");
        }

        $discussion = Gdn::sql()
            ->getWhere("Discussion", ["DiscussionID" => $comment["DiscussionID"]])
            ->firstRow(DATASET_TYPE_ARRAY);

        // Check for permission.
        if (
            !(
                Gdn::session()->UserID == val("InsertUserID", $discussion) ||
                Gdn::session()->checkRankedPermission("Garden.Curation.Manage")
            )
        ) {
            throw permissionException("Garden.Curation.Manage");
        }
        if (!Gdn::session()->validateTransientKey($sender->Request->get("tkey"))) {
            throw permissionException();
        }

        switch ($args[0]) {
            case "accept":
                $qna = "Accepted";
                break;
            case "reject":
                $qna = "Rejected";
                break;
        }

        if (isset($qna)) {
            $CommentSet = ["QnA" => $qna];

            if ($qna == "Accepted") {
                $CommentSet["DateAccepted"] = DateTimeFormatter::getCurrentDateTime();
                $CommentSet["AcceptedUserID"] = Gdn::session()->UserID;

                if (!$discussion["DateAccepted"]) {
                    $discussionSet["DateAccepted"] = DateTimeFormatter::getCurrentDateTime();
                    $discussionSet["DateOfAnswer"] = $comment["DateInserted"];
                }
            }

            // Update the comment.
            Gdn::sql()->put("Comment", $CommentSet, ["CommentID" => $comment["CommentID"]]);

            // Update the discussion.
            $this->recalculateDiscussionQnA($discussion);

            $commentID = $comment["CommentID"] ?? false;
            $updatedAnswer = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

            $this->answerModel->applyCommentQnAChange($discussion, $updatedAnswer, $comment["QnA"], $qna);

            $headlineFormat = t(
                "HeadlineFormat.AcceptAnswer",
                '{ActivityUserID,You} accepted {NotifyUserID,your} answer to a question: <a href="{Url,html}">{Data.Name,text}</a>'
            );

            // Record the activity.
            if ($qna == "Accepted") {
                $activity = [
                    "ActivityType" => "AnswerAccepted",
                    "NotifyUserID" => $comment["InsertUserID"],
                    "HeadlineFormat" => $headlineFormat,
                    "RecordType" => "Comment",
                    "RecordID" => $comment["CommentID"],
                    "Route" => commentUrl($comment, "/"),
                    "Data" => [
                        "Name" => val("Name", $discussion),
                    ],
                ];

                $ActivityModel = new ActivityModel();
                $ActivityModel->queue($activity, "AnswerAccepted");
                $ActivityModel->saveQueue();

                $this->EventArguments["Activity"] = &$activity;
            }
        }
        redirectTo("/discussion/comment/{$comment["CommentID"]}#Comment_{$comment["CommentID"]}", 302, false);
    }

    /**
     *
     *
     * @param DiscussionController $sender Sending controller instance.
     * @param string|int $discussionID Identifier of the discussion
     * @param string|int $commentID Identifier of the comment.
     */
    public function discussionController_qnAOptions_create($sender, $discussionID = "", $commentID = "")
    {
        if ($discussionID) {
            $this->_discussionOptions($sender, $discussionID);
        } elseif ($commentID) {
            $this->_commentOptions($sender, $commentID);
        }
    }

    /**
     * Update old status columns
     *
     * @param DiscussionStatusEvent $event
     *
     * @return DiscussionStatusEvent event passed in.
     */
    public function handleDiscussionStatusEvent(DiscussionStatusEvent $event): DiscussionStatusEvent
    {
        $set = [];
        $payload = $event->getPayload();
        $discussion = $payload["discussion"];
        $statusID = $discussion["statusID"];
        $discussionID = $discussion["discussionID"];
        $type = $discussion["type"];
        if ($type == "question") {
            switch ($statusID) {
                case self::DISCUSSION_STATUS_ACCEPTED:
                    $acceptedComment = Gdn::sql()
                        ->getWhere("Comment", ["DiscussionID" => $discussionID, "QnA" => "Accepted"], "", "asc", 1)
                        ->firstRow(DATASET_TYPE_ARRAY);
                    $set["DateAccepted"] = $acceptedComment["DateAccepted"];
                    $set["DateOfAnswer"] = $acceptedComment["DateInserted"];
                    break;
                case self::DISCUSSION_STATUS_ANSWERED:
                    $answeredComment = Gdn::sql()
                        ->getWhere("Comment", ["DiscussionID" => $discussionID, "QnA is null" => ""], "", "asc", 1)
                        ->firstRow(DATASET_TYPE_ARRAY);
                    $set["DateAccepted"] = null;
                    $set["DateOfAnswer"] = $answeredComment["DateInserted"] ?? null;
                    break;
                case self::DISCUSSION_STATUS_REJECTED:
                case self::DISCUSSION_STATUS_UNANSWERED:
                    $set["DateAccepted"] = null;
                    $set["DateOfAnswer"] = null;
                    break;
            }
        }
        $this->discussionModel->setField($discussionID, $set, null, true);

        return $event;
    }

    /**
     * Event handler that add this plugin's specific `recordStatus`'s `statusID` that needs to be enabled.
     *
     * @param RecordStatusStructureEvent $event
     * @return RecordStatusStructureEvent
     */
    public function handleRecordStatusStructureEvent(RecordStatusStructureEvent $event): RecordStatusStructureEvent
    {
        $event->addActiveRecordStatusIDs(self::$pluginDefinedRecordStatusIDs);
        return $event;
    }

    /**
     * Recalculate the QnA status of a discussion.
     *
     * @param array|object $discussion The discussion to recalculate.
     */
    public function recalculateDiscussionQnA($discussion): void
    {
        $discussionArr = (array) $discussion;
        $discussionID = (int) ($discussionArr["discussionID"] ?? $discussionArr["DiscussionID"]);
        $currentStatus = $this->discussionStatusModel->getDiscussionStatus($discussionID);
        if ($currentStatus["recordSubtype"] !== null) {
            // Look for at least one accepted answer/comment.
            $acceptedComment = Gdn::sql()
                ->getWhere("Comment", ["DiscussionID" => $discussionID, "QnA" => "Accepted"], "", "asc", 1)
                ->firstRow(DATASET_TYPE_ARRAY);

            if ($acceptedComment) {
                $status = self::DISCUSSION_STATUS_ACCEPTED;
            } else {
                // Look for at least one untreated answer/comment.
                $answeredComment = Gdn::sql()
                    ->getWhere("Comment", ["DiscussionID" => $discussionID, "QnA is null" => ""], "", "asc", 1)
                    ->firstRow(DATASET_TYPE_ARRAY);

                $countComments = val("CountComments", $discussion, 0);

                if ($answeredComment) {
                    $status = self::DISCUSSION_STATUS_ANSWERED;
                } elseif ($countComments > 0) {
                    $status = self::DISCUSSION_STATUS_REJECTED;
                } else {
                    $status = self::DISCUSSION_STATUS_UNANSWERED;
                }
            }
            $this->discussionStatusModel->updateDiscussionStatus($discussionID, $status);
        }
    }

    /**
     *
     *
     * @param string|int $userID User identifier
     */
    public function recalculateUserQnA($userID)
    {
        $this->answerModel->recalculateUserQnA($userID);
    }

    /**
     *
     *
     * @param $sender controller instance.
     * @param int|string $commentID Identifier of the comment.
     *
     * @throws notFoundException
     */
    public function _commentOptions($sender, $commentID)
    {
        $sender->Form = new Gdn_Form();

        $comment = $this->commentModel->getID($commentID, DATASET_TYPE_ARRAY);

        if (!$comment) {
            throw notFoundException("Comment");
        }

        $discussion = $this->discussionModel->getID(val("DiscussionID", $comment), DATASET_TYPE_ARRAY);
        if (!Gdn::session()->checkRankedPermission("Garden.Curation.Manage")) {
            $sender->permission("Vanilla.Discussions.Edit", true, "Category", val("PermissionCategoryID", $discussion));
        }

        if ($sender->Form->authenticatedPostBack(true)) {
            $newQnA = $sender->Form->getFormValue("QnA");
            if (!$newQnA) {
                $newQnA = null;
            }

            $this->updateCommentQnA($discussion, $comment, $newQnA, $sender->Form);

            // Recalculate the Q&A status of the discussion.
            $this->recalculateDiscussionQnA($discussion);

            Gdn::controller()->jsonTarget("", "", "Refresh");
        } else {
            $sender->Form->setData($comment);
        }

        $sender->setData("Comment", $comment);
        $sender->setData("Discussion", $discussion);
        $sender->setData("_QnAs", ["Accepted" => t("Yes"), "Rejected" => t("No"), "" => t("Don't know")]);
        $sender->setData("Title", t("Q&A Options"));
        $sender->render("CommentOptions", "", "plugins/QnA");
    }

    /**
     * Update a comment QnA data.
     *
     * @param array|object $discussion
     * @param array|object $comment
     * @param string|null $newQnA
     * @param Gdn_Form|null $form
     */
    protected function updateCommentQnA($discussion, $comment, $newQnA, Gdn_Form $form = null)
    {
        $this->answerModel->updateCommentQnA($discussion, $comment, $newQnA, $form);
    }

    /**
     *
     *
     * @param $sender controller instance.
     * @param int|string $discussionID Identifier of the discussion.
     *
     * @throws notFoundException
     */
    protected function _discussionOptions($sender, $discussionID)
    {
        $sender->Form = new Gdn_Form();

        $discussion = $this->discussionModel->getID($discussionID, DATASET_TYPE_ARRAY);

        if (!$discussion) {
            throw notFoundException("Discussion");
        }

        $sender->permission("Vanilla.Discussions.Edit", true, "Category", val("PermissionCategoryID", $discussion));

        // Both '' and 'Discussion' denote a discussion type of discussion.
        if (!val("Type", $discussion)) {
            setValue("Type", $discussion, "Discussion");
        }

        if ($sender->Form->authenticatedPostBack()) {
            $type = $sender->Form->getFormValue("Type");
            $this->updateRecordType($discussionID, $discussion, $type);
            $sender->Form->setValidationResults($this->discussionModel->validationResults());
            Gdn::controller()->jsonTarget("", "", "Refresh");
        } else {
            $sender->Form->setData($discussion);
        }

        $sender->setData("Discussion", $discussion);
        $sender->setData("_Types", [
            "Question" => "@" . t("Question Type", "Question"),
            "Discussion" => "@" . t("Discussion Type", "Discussion"),
        ]);
        $sender->setData("Title", t("Q&A Options"));
        $sender->render("DiscussionOptions", "", "plugins/QnA");
    }

    /**
     * Add where filter into discussion query
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_beforeGet_handler($sender, $args)
    {
        if (Gdn::controller()) {
            $unanswered =
                Gdn::controller()->ClassName == "DiscussionsController" &&
                Gdn::controller()->RequestMethod == "unanswered";
            $this->discussionModelQnaFilter($unanswered, $args);
        }
    }

    /**
     * Add where filter into count query
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_beforeGetCount_handler($sender, $args)
    {
        if (Gdn::controller()) {
            $unanswered =
                Gdn::controller()->ClassName == "DiscussionsController" &&
                Gdn::controller()->RequestMethod == "unanswered";
            $this->discussionModelQnaFilter($unanswered, $args);
        }
    }

    /**
     * Qna filter where builder
     *
     * @param bool $unanswered Is this the unanswered filter page or a QnA filter args
     * @param array $args query args
     */
    private function discussionModelQnaFilter(bool $unanswered = false, array $args = [])
    {
        if ($unanswered) {
            $this->discussionModel->SQL
                ->where("d.statusID", [self::DISCUSSION_STATUS_UNANSWERED, self::DISCUSSION_STATUS_REJECTED])
                ->beginWhereGroup()
                ->where("d.Type", "Question")
                ->where("d.Announce", "All")
                ->endWhereGroup();
            Gdn::controller()->title(t("Unanswered Questions"));
        } elseif ($qnA = Gdn::request()->get("qna")) {
            if (isset($args["Wheres"]["QnA"])) {
                unset($args["Wheres"]["QnA"]);
            }
            $qnaModel = \Gdn::getContainer()->get(QnaModel::class);
            $status = $qnaModel->getQuestionStatusByName(ucfirst($qnA));
            $args["Wheres"]["statusID"] = $status["statusID"];
        }
    }

    /**
     *
     *
     * @param DiscussionModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionModel_beforeSaveDiscussion_handler($sender, $args)
    {
        if ($args["Insert"]) {
            $post = &$args["FormPostValues"];

            if ($this->apiQuestionInsert) {
                $post["Type"] = "Question";
            }

            if (val("Type", $post) == "Question") {
                $post["statusID"] = self::DISCUSSION_STATUS_UNANSWERED;
            }
        }
    }

    /**
     * Add QnaFollowUpNotification toggle
     *
     * @param GDN_Controller $sender Sending controller instance
     */
    public function base_afterCategorySettings_handler($sender)
    {
        if ($this->questionFollowUpFeatureEnabled()) {
            $category = $sender->Category;
            if ($category->DisplayAs === "Discussions") {
                echo "<li class='form-group'>";
                echo $sender->Form->toggle("QnaFollowUpNotification", "Enable Q&A follow-up notifications.");
                echo "</li>";
            }
        }
    }

    /**
     * New Html method of adding to discussion filters.
     *
     * @param GDN_Controller $sender Sending controller instance
     */
    public function base_afterDiscussionFilters_handler($sender)
    {
        $cached = Gdn::cache()->get("QnA-UnansweredCount");
        if ($cached === Gdn_Cache::CACHEOP_FAILURE) {
            $count =
                '<span class="Aside">' .
                '<span class="Popin Count" rel="/discussions/unansweredcount"></span>' .
                "</span>";
        } else {
            $total = $cached < $this->unansweredCountLimit ? $cached : $this->unansweredCountLimit . "+";
            $count = '<span class="Aside">' . '<span class="Count">' . $total . "</span>" . "</span>";
        }

        $extraClass = $sender->RequestMethod == "unanswered" ? "Active" : "";
        $sprite = sprite("SpUnansweredQuestions");

        echo "<li class='QnA-UnansweredQuestions $extraClass'>" .
            anchor($sprite . " " . t("Unanswered") . " " . $count, "/discussions/unanswered", "UnansweredQuestions") .
            "</li>";
    }

    /**
     * Old Html method of adding to discussion filters.
     */
    public function discussionsController_afterDiscussionTabs_handler()
    {
        if (stringEndsWith(Gdn::request()->path(), "/unanswered", true)) {
            $cssClass = ' class="Active"';
        } else {
            $cssClass = "";
        }

        $count = Gdn::cache()->get("QnA-UnansweredCount");
        if ($count === Gdn_Cache::CACHEOP_FAILURE) {
            $count = ' <span class="Popin Count" rel="/discussions/unansweredcount">';
        } else {
            $count = ' <span class="Count">' . $count . "</span>";
        }

        echo "<li" .
            $cssClass .
            '><a class="TabLink QnA-UnansweredQuestions" href="' .
            url("/discussions/unanswered") .
            '">' .
            t("Unanswered Questions", "Unanswered") .
            $count .
            "</span></a></li>";
    }

    /**
     *
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unanswered_create($sender, $args)
    {
        // The frontend part of this isn't ready yet as it doesn't handle the appropriate url querystring.
        if (\Vanilla\FeatureFlagHelper::featureEnabled("customLayout.discussionList.QnAPlugin")) {
            redirectTo("/discussions?type=question&status=unanswered");
        }
        $sender->View = "Index";
        $sender->title(t("Unanswered Questions"));
        $sender->setData("_PagerUrl", "discussions/unanswered/{Page}");

        // Be sure to display every unanswered question (ie from groups)
        $categories = [];
        $visibleCategoryIDs = $this->categoryModel->getVisibleCategoryIDs([
            "forceArrayReturn" => true,
            "filterHideDiscussions" => true,
            "filterArchivedCategories" => true,
        ]);
        $unindexedCategories = $this->categoryModel->getWhere(["CategoryID" => $visibleCategoryIDs])->resultArray();
        // CategoryIDs should be used as the records key index.
        foreach ($unindexedCategories as $unindexedCategory) {
            $categories[$unindexedCategory["CategoryID"]] = $unindexedCategory;
        }

        $this->EventArguments["Categories"] = &$categories;
        $this->fireEvent("UnansweredBeforeSetCategories");

        $sender->setData("ApplyRestrictions", true);
        $sender->setCategoryIDs(array_keys($categories));

        // unanswered should not be filtered by category follow
        if ($this->categoryModel->followingEnabled()) {
            $sender->setData("EnableFollowingFilter", false);
            $sender->setData("Followed", false);
        }

        $sender->index(val(0, $args, "p1"));
        $this->InUnanswered = true;
    }

    /**
     *
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_beforeBuildPager_handler($sender, $args)
    {
        if (Gdn::controller()->RequestMethod == "unanswered" || Gdn::request()->get("qna")) {
            $sender->setData("CountDiscussions", false);
        }
    }

    /**
     * Displays the amounts of unanswered questions.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unanswered_render($sender, $args)
    {
        $sender->setData("CountDiscussions", false);

        // Add 'Ask a Question' button if using BigButtons.
        if (c("Plugins.QnA.UseBigButtons")) {
            $questionModule = new NewQuestionModule($sender, "plugins/QnA");
            $sender->addModule($questionModule);
        }

        // Remove announcements that aren't questions...
        if (is_a($sender->data("Announcements"), "Gdn_DataSet")) {
            $sender->data("Announcements")->result();
            $announcements = [];
            foreach ($sender->data("Announcements") as $i => $row) {
                if (val("Type", $row) == "Question") {
                    $announcements[] = $row;
                }
            }
            trace($announcements);
            $sender->setData("Announcements", $announcements);
            $sender->AnnounceData = $announcements;
        }

        $sender->setData("Breadcrumbs", [["Name" => t("Unanswered"), "Url" => "/discussions/unanswered"]]);
    }

    /**
     * Displays the amounts of unanswered questions.
     *
     * @param DiscussionsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function discussionsController_unansweredCount_create($sender, $args)
    {
        /** @var QnaModel $qnaModel */
        $qnaModel = \Gdn::getContainer()->get(QnaModel::class);

        $count = $qnaModel->getUnansweredCount($this->unansweredCountLimit);
        $count = $count < $this->unansweredCountLimit ? $count : $this->unansweredCountLimit . "+";

        $sender->setData("UnansweredCount", $count);
        $sender->setData("_Value", $count);
        $sender->render("Value", "Utility", "Dashboard");
    }

    /**
     * Print QnA meta data tag
     *
     * @param Gdn_Controller $sender
     * @param array $args
     */
    public function base_beforeDiscussionMeta_handler($sender, $args)
    {
        $discussion = $args["Discussion"];
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
    public function discussionController_discussionInfo_handler($sender, $args)
    {
        $discussion = $args["Discussion"];
        if (!empty($discussion) && \Vanilla\FeatureFlagHelper::featureEnabled(static::FEATURE_FLAG)) {
            echo $this->getDiscussionQnATagString($discussion);
        }
    }

    /**
     * Return QnA meta data tag string
     *
     * @param object $discussion
     */
    private function getDiscussionQnATagString(object $discussion = null): string
    {
        $tag = "";
        if (strtolower(val("Type", $discussion)) != "question") {
            return $tag;
        }

        $qnA = $this->qnaModel->getStatus($discussion->statusID)["name"];
        $title = "";
        switch ($qnA) {
            case "":
            case "Unanswered":
            case "Rejected":
                $text = "Question";
                $qnA = "Question";
                break;
            case "Answered":
                $text = "Answered";
                if ($discussion->InsertUserID == Gdn::session()->UserID) {
                    $qnA = "Answered";
                    $title =
                        ' title="' . t("Someone's answered your question. You need to accept/reject the answer.") . '"';
                }
                break;
            case "Accepted":
                $text = "Accepted Answer";
                $title = ' title="' . t("This question's answer has been accepted.") . '"';
                break;
            default:
                $qnA = false;
        }
        if ($qnA) {
            $tag = '<span class="Tag QnA-Tag-' . $qnA . '" ' . $title . ">" . t("Q&A $qnA", $text) . "</span> ";
        }

        return $tag;
    }

    /**
     * Notifies the current user when one of his questions have been answered.
     *
     * @param NotificationsController $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function notificationsController_beforeInformNotifications_handler($sender, $args)
    {
        $path = trim($sender->Request->getValue("Path"), "/");
        if (preg_match("`^(vanilla/)?discussion[^s]`i", $path)) {
            return;
        }
    }

    /**
     * Add 'Ask a Question' button if using BigButtons.
     *
     * @param CategoriesController $sender Sending controller instance.
     */
    public function categoriesController_render_before($sender)
    {
        if (c("Plugins.QnA.UseBigButtons")) {
            $questionModule = new NewQuestionModule($sender, "plugins/QnA");
            $sender->addModule($questionModule);
        }
    }

    /**
     * Add 'Ask a Question' button if using BigButtons and modify flow of discussion by pinning accepted answers.
     *
     * @param DiscussionController $sender Sending controller instance.
     */
    public function discussionController_render_before($sender)
    {
        if (c("Plugins.QnA.UseBigButtons")) {
            $questionModule = new NewQuestionModule($sender, "plugins/QnA");
            $sender->addModule($questionModule);
        }

        if ($sender->data("Discussion.Type") == "Question") {
            $sender->setData("_CommentsHeader", t("Answers"));
        }
        $statusID = $sender->data("Discussion.statusID");
        if (in_array($statusID, self::$pluginDefinedRecordStatusIDs)) {
            $sender->CssClass .= " Question";
            $discussion = (array) $sender->data("Discussion");
            // Limit up to 3 answers
            /** @var int $limit */
            $limit = Gdn::config("QnA.JsonLD.AnswersLimit", 3);
            /** @var  array $answers */
            $answers = array_slice($sender->data("Answers", []), 0, $limit, true);
            // This data structure works only if we have accepted answer or rules that bring best answer(vote, score,...)
            if ($answers) {
                $QnAJsonLDItem = new QnAJsonLD($discussion, $answers, $this->formatService, $this->userModel);
                $sender->Head->addJsonLDItem($QnAJsonLDItem);
            }
        }
    }

    /**
     * Set Answers to the discussion
     *
     * @param DiscussionController $sender
     */
    public function discussionController_beforeDiscussionRender_handler($sender)
    {
        if ($sender->data("Discussion.statusID") != self::DISCUSSION_STATUS_ACCEPTED) {
            return;
        }

        // Find the accepted answer(s) to the question.
        $answers = $this->commentModel
            ->getWhere(["DiscussionID" => $sender->data("Discussion.DiscussionID"), "Qna" => "Accepted"])
            ->result();

        $sender->setData("Answers", $answers);

        // Remove the accepted answers from the comments.
        // Allow this to be skipped via config.
        if (c("QnA.AcceptedAnswers.Filter", true)) {
            if (isset($sender->Data["Comments"])) {
                $comments = $sender->Data["Comments"]->result();
                $comments = array_filter($comments, function ($row) {
                    return strcasecmp(val("QnA", $row), "accepted");
                });
                $sender->Data["Comments"] = new Gdn_DataSet(array_values($comments));
            }
        }
    }

    /**
     * Add the question form to vanilla's post page.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_afterForms_handler($sender)
    {
        $forms = $sender->data("Forms");
        $forms[] = [
            "Name" => "Question",
            "Label" => sprite("SpQuestion") . t("Ask a Question"),
            "Url" => "post/question",
        ];
        $sender->setData("Forms", $forms);
    }

    /**
     * Update comment form filters on the post controller.
     *
     * @param PostController $sender
     * @return array
     */
    public function postController_initialize(PostController $sender)
    {
        $sender->CommentModel->addFilterField("QnA");
    }

    /**
     * Create the new question method on post controller.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_question_create($sender, $categoryUrlCode = "")
    {
        $category = null;
        $categoryModel = new CategoryModel();
        if ($categoryUrlCode != "") {
            $category = (array) $categoryModel->getByCode($categoryUrlCode);
            $category = $categoryModel::permissionCategory($category);
            $isAllowedTypes = isset($category["AllowedDiscussionTypes"]);
            $isAllowedQuestion = in_array("Question", (array) $category["AllowedDiscussionTypes"] ?? []);
        }

        if ($category && !$isAllowedQuestion && $isAllowedTypes) {
            $sender->Form->addError(t("You are not allowed to post a question in this category."));
        }
        // Create & call PostController->discussion()
        $sender->View = PATH_PLUGINS . "/QnA/views/post.php";
        $sender->setData("Type", "Question");
        $sender->discussion($categoryUrlCode);
    }

    /**
     * Override the PostController->discussion() method before render to use our view instead.
     *
     * @param PostController $sender Sending controller instance.
     */
    public function postController_beforeDiscussionRender_handler($sender)
    {
        // Override if we are looking at the question url.
        if ($sender->RequestMethod == "question" || $sender->data("Type") == "Question") {
            $sender->Form->addHidden("Type", "Question");
            $sender->title(t("Ask a Question"));
            $sender->setData("Breadcrumbs", [["Name" => $sender->data("Title"), "Url" => "/post/question"]]);
        }
    }

    /**
     * Give point(s) to users for their first answer on an unanswered question!
     *
     * @param CommentModel $sender Sending controller instance.
     * @param array $args Event arguments.
     */
    public function base_afterSaveComment_handler($sender, $args)
    {
        if (!c("QnA.Points.Enabled", false) || !$args["Insert"]) {
            return;
        }

        $discussion = $this->discussionModel->getID($args["CommentData"]["DiscussionID"], DATASET_TYPE_ARRAY);

        $isCommentAnAnswer = $discussion["Type"] === "Question";
        $isQuestionResolved = $discussion["statusID"] === self::DISCUSSION_STATUS_ACCEPTED;
        $isCurrentUserOriginalPoster = $discussion["InsertUserID"] == GDN::session()->UserID;
        if (!$isCommentAnAnswer || $isQuestionResolved || $isCurrentUserOriginalPoster) {
            return;
        }

        $userAnswersToQuestion = $this->commentModel->getWhere([
            "DiscussionID" => $args["CommentData"]["DiscussionID"],
            "InsertUserID" => $args["CommentData"]["InsertUserID"],
        ]);
        // Award point(s) only for the first answer to the question
        if ($userAnswersToQuestion->count() > 1) {
            return;
        }

        CategoryModel::givePoints(GDN::session()->UserID, c("QnA.Points.Answer", 1), "QnA", $discussion["CategoryID"]);
    }

    ##########################
    ## API Controller
    ###########################

    /**
     * The question's meta data schema.
     *
     * @return Schema
     */
    public function fullQuestionMetaDataSchema()
    {
        $schema = Schema::parse([
            "status:s" => [
                "enum" => ["unanswered", "answered", "accepted", "rejected"],
                "description" => "The answering state of the question.",
            ],
            "dateAccepted:dt|n" => "When an answer was accepted.",
            "dateAnswered:dt|n" => "When the last answer was inserted.",
            "acceptedAnswers?" => [
                "object" => "Accepted answers.",
                "type" => "array",
                "items" => Schema::parse([
                    "commentID" => [
                        "type" => "integer",
                        "description" => "Unique ID of the accepted answer's comment row.",
                    ],
                    "body?" => [
                        "type" => "string",
                        "description" => "Rendered content of the answer.",
                    ],
                ]),
            ],
        ]);

        if ($this->session->checkPermission(["Garden.Curation.Manage"])) {
            $schema->merge(
                Schema::parse([
                    "rejectedAnswers?" => [
                        "object" => "Rejected answers.",
                        "type" => "array",
                        "items" => Schema::parse([
                            "commentID" => [
                                "type" => "integer",
                                "description" => "Unique ID of the accepted answer's comment row.",
                            ],
                            "body?" => [
                                "type" => "string",
                                "description" => "Rendered content of the answer.",
                            ],
                        ]),
                    ],
                ])
            );
        }

        return $schema;
    }

    /**
     * Add question status to discussion schema.
     *
     * @param Schema $schema
     */
    public function discussionIndexSchema_init(Schema $schema)
    {
        $schema->merge(
            Schema::parse([
                "status:s?" => [
                    "enum" => array_map("strtolower", [
                        QnaModel::ACCEPTED,
                        QnaModel::ANSWERED,
                        QnaModel::UNANSWERED,
                        QnaModel::REJECTED,
                    ]),
                ],
            ])
        );
    }

    /**
     * Format search param if question status exist in Discussion Search
     *
     * @param array $where Where clause as array
     * @param DiscussionsAPIController $controller
     * @param Schema $inSchema
     * @param array $query
     * @return array Where clause as array
     */
    public function discussionsApiController_indexFilters(
        array $where,
        DiscussionsApiController $controller,
        Schema $inSchema,
        array $query
    ) {
        if (!isset($query["status"])) {
            return $where;
        }

        $status = $this->qnaModel->getQuestionStatusByName($query["status"]);
        $where["statusID"] = $status["statusID"];

        return $where;
    }

    /**
     * Add answer status to comment schema.
     *
     * @param Schema $schema
     */
    public function commentIndexSchema_init(Schema $schema)
    {
        $schema
            ->merge(
                Schema::parse([
                    "qna:s?" => [
                        "enum" => array_map("strtolower", [QnaModel::ACCEPTED, QnaModel::REJECTED]),
                        "x-filter" => [
                            "field" => "c.QnA",
                        ],
                    ],
                ])
            )
            ->addValidator("qna", function ($data) {
                if ($data == "rejected" && !$this->session->checkPermission(["Garden.Curation.Manage"])) {
                    throw new PermissionException("Garden.Curation.Manage");
                }
            });
    }

    /**
     * Add question meta data to discussion schema.
     *
     * @param Schema $schema
     */
    public function discussionSchema_init(Schema $schema)
    {
        $attributes = $schema->getField("properties.attributes");

        // Add to an existing "attributes" field or create a new one?
        if ($attributes instanceof Schema) {
            $attributes->merge(
                Schema::parse([
                    "question?" => $this->fullQuestionMetaDataSchema(),
                ])
            );
        } else {
            $schema->merge(
                Schema::parse([
                    "attributes?" => Schema::parse([
                        "question?" => $this->fullQuestionMetaDataSchema(),
                    ]),
                ])
            );
        }
    }

    /**
     * Update the schema when getting a specific discussion from the API.
     *
     * @param Schema $schema
     */
    public function discussionGetSchema_init(Schema $schema)
    {
        // Add expanding a discussion's accepted answer content.
        $expand = $schema->getField("properties.expand.items.enum");
        $expand[] = "acceptedAnswers";
        $expand[] = "rejectedAnswers";
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
        if ($discussion["type"] !== "question") {
            return $discussion;
        }

        $acceptedAnswers = [];
        $status = $this->qnaModel->getStatus($discussion["statusID"]);
        $discussion["attributes"]["question"] = [
            "status" => !empty($status) ? strtolower($status["name"]) : "unanswered",
            "dateAccepted" => $discussion["dateAccepted"],
            "dateAnswered" => $discussion["dateOfAnswer"],
        ];

        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $options["expand"] ?? [])) {
            if (!empty($discussion[self::QNA_KEY])) {
                $discussion["labelCodes"][] = $discussion[self::QNA_KEY];
            }
            return $discussion;
        }

        $expandAccepted = $discussionsApiController->isExpandField("acceptedAnswers", $options["expand"] ?? []);
        $expandRejected = $discussionsApiController->isExpandField("rejectedAnswers", $options["expand"] ?? []);

        if (!$expandAccepted && !$expandRejected) {
            return $discussion;
        }

        if ($expandAccepted) {
            $acceptedAnswers = $this->getDiscussionAnswersByType($discussion["discussionID"], "accepted");
            $discussion["attributes"]["question"]["acceptedAnswers"] = $acceptedAnswers;
        }

        if ($expandRejected) {
            if ($this->session->checkPermission(["Garden.Curation.Manage"])) {
                $rejectedAnswers = $this->getDiscussionAnswersByType($discussion["discussionID"], "rejected");
            } else {
                $rejectedAnswers = [];
            }
            $discussion["attributes"]["question"]["rejectedAnswers"] = $rejectedAnswers;
        }

        return $discussion;
    }

    private function getDiscussionAnswersByType(int $discussionID, string $type): array
    {
        $type = ucfirst($type);
        $answers = [];
        $answerRows = $this->commentModel
            ->getWhere([
                "DiscussionID" => $discussionID,
                "Qna" => $type,
            ])
            ->resultArray();

        foreach ($answerRows as $comment) {
            $answer = [
                "commentID" => $comment["CommentID"],
                "body" => Gdn_Format::to($comment["Body"], $comment["Format"]),
            ];
            $answers[] = $answer;
        }

        return $answers;
    }

    /**
     * Create POST /discussions/question endpoint.
     *
     * @param DiscussionsApiController $sender
     * @param array $body
     * @return array
     */
    public function discussionsApiController_post_question(DiscussionsApiController $sender, array $body)
    {
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
    public function fullAnswerMetaDataSchema()
    {
        return Schema::parse([
            "status:s" => [
                "enum" => ["accepted", "rejected", "pending"],
                "description" => "The state of the answer.",
            ],
            "dateAccepted:dt|n" => "When an answer was accepted.",
            "acceptUserID:i|n" => "The user that accepted this answer.",
        ]);
    }

    /**
     * Add answer meta data to comment schema.
     *
     * @param Schema $schema
     */
    public function commentSchema_init(Schema $schema)
    {
        $attributes = $schema->getField("properties.attributes");

        // Add to an existing "attributes" field or create a new one?
        if ($attributes instanceof Schema) {
            $attributes->merge(Schema::parse(["answer?" => $this->fullAnswerMetaDataSchema()]));
        } else {
            $schema->merge(
                Schema::parse([
                    "attributes?" => Schema::parse(["answer?" => $this->fullAnswerMetaDataSchema()]),
                ])
            );
        }
    }

    /**
     * Apply expand query params for api queries.
     * @param string $recordType
     * @return array
     */
    public function discussionArticleModel_applyExpand(string $recordType = ""): array
    {
        $expand = [];
        if ($recordType === "question") {
            $expand["expand"] = "acceptedAnswers";
        }
        return $expand;
    }

    /**
     * Add answer meta data to comment record.
     *
     * @param array $comment
     * @param CommentsApiController $commentsApiController
     * @param array $options
     * @return array
     * @throws NotFoundException
     */
    public function commentsApiController_normalizeOutput(
        array $comment,
        CommentsApiController $commentsApiController,
        array $options
    ) {
        if (ModelUtils::isExpandOption(ModelUtils::EXPAND_CRAWL, $options["expand"] ?? [])) {
            if (!empty($comment[self::QNA_KEY])) {
                if ($comment[self::QNA_KEY] === "Rejected") {
                    if ($this->session->checkPermission("Garden.Curation.Manage")) {
                        $comment["labelCodes"][] = $comment[self::QNA_KEY];
                    }
                } else {
                    $comment["labelCodes"][] = $comment[self::QNA_KEY];
                }
            }
            return $comment;
        }
        $discussionID = $comment["discussionID"] ?? null;
        if ($discussionID === null) {
            return $comment;
        }

        if (!isset($this->discussionsCache[$discussionID])) {
            // This has the potential to be pretty bad, performance wise, so we at least cached the results.
            $this->discussionsCache[$discussionID] = $commentsApiController->discussionByID($discussionID);
        }
        $discussion = $this->discussionsCache[$discussionID];

        if ($discussion["Type"] !== "Question") {
            return $comment;
        }

        if (!$this->session->checkPermission("Garden.Curation.Manage") && strtolower($comment["qnA"]) === "rejected") {
            $comment["qna"] = "pending";
        }

        $comment = $this->answerModel->normalizeRow($comment);
        return $comment;
    }

    /**
     * Create PATCH /comments/answer endpoint.
     *
     * @param CommentsApiController $sender
     * @param int $id
     * @param array $body
     * @return array
     * @throws ClientException
     */
    public function commentsApiController_patch_answer(CommentsApiController $sender, $id, array $body)
    {
        $sender->permission("Garden.SignIn.Allow");

        $sender->idParamSchema("in");
        $in = $sender
            ->schema(Schema::parse(["status"])->add($this->fullAnswerMetaDataSchema()), "AnswerPatch")
            ->setDescription('Update an answer\'s metadata.');
        $out = $sender->commentSchema("out");

        $body = $in->validate($body);
        $data = ApiUtils::convertInputKeys($body);
        $data["CommentID"] = $id;
        $comment = $sender->commentByID($id);
        $discussion = $sender->discussionByID($comment["DiscussionID"]);

        if ($discussion["Type"] !== "Question") {
            throw new ClientException("The comment is not an answer.");
        }

        if ($discussion["InsertUserID"] !== $sender->getSession()->UserID) {
            $this->discussionModel->categoryPermission("Vanilla.Discussions.Edit", $discussion["CategoryID"]);
        }

        if ($discussion["Closed"]) {
            $sender->permission("Garden.Moderation.Manage");
        }

        // Body is a required field in CommentModel::save.
        if (!array_key_exists("Body", $data)) {
            $data["Body"] = $comment["Body"];
        }

        $status = ucFirst($body["status"]);
        if ($status === "Pending") {
            $status = null;
        }
        $this->updateCommentQnA($discussion, $comment, $status);
        $sender->validateModel($this->commentModel);

        $this->recalculateDiscussionQnA($discussion);

        $row = $sender->commentByID($id);
        $this->userModel->expandUsers($row, ["InsertUserID"]);
        $row = $sender->normalizeOutput($row);

        $result = $out->validate($row);
        return $result;
    }

    /**
     * Add QnA fields to the search schema.
     *
     * @param Schema $schema
     */
    public function searchResultSchema_init(Schema $schema)
    {
        $types = $schema->getField("properties.type.enum");
        $types[] = "question";
        $types[] = "answer";
        $schema->setField("properties.type.enum", $types);
    }

    /**
     * Add option to dba/counts to recalculate QnA state of discussions(questions).
     *
     * @param DBAController $sender
     */
    public function dbaController_countJobs_handler($sender)
    {
        $name = "Recalculate Discussion.statusID";
        // We name the table QnA and not Discussion because the model is instantiated from the table name in DBAModel.
        $url = "/dba/counts.json?" . http_build_query(["table" => "QnA", "column" => "statusID"]);
        $sender->Data["Jobs"][$name] = $url;
    }

    /**
     * Adds status notification options to profiles.
     *
     * @param ProfileController $sender
     */
    public function profileController_afterPreferencesDefined_handler($sender)
    {
        $sender->Preferences["Notifications"]["Email.AnswerAccepted"] = t("Notify me when people accept my answer.");
        $sender->Preferences["Notifications"]["Popup.AnswerAccepted"] = t("Notify me when people accept my answer.");
        $sender->Preferences["Notifications"]["Email.QuestionAnswered"] = t(
            "Notify me when people answer my question."
        );
        $sender->Preferences["Notifications"]["Popup.QuestionAnswered"] = t(
            "Notify me when people answer my question."
        );
        if ($this->questionFollowUpFeatureEnabled()) {
            $sender->Preferences["Notifications"]["Email.QuestionFollowUp"] = t(
                "Send me a follow-up for my answered questions"
            );
        }
    }

    /**
     * Check if Question Follow-Up is enabled.
     *
     * @return bool
     */
    private function questionFollowUpFeatureEnabled(): bool
    {
        return Gdn::config()->get("QnA.FollowUp.Enabled");
    }

    /**
     * Create POST /discussions/question-notifications endpoint.
     * This is an endpoint for internal use.
     *
     * @param DiscussionsApiController $sender
     * @param array $body
     * @return mixed
     * @throws Exception If the feature flag is not enabled.
     */
    public function discussionsApiController_post_questionNotifications(DiscussionsApiController $sender, array $body)
    {
        $in = $sender->schema($this->qnaFollowUpNotificationInSchema(), "in");
        $out = $sender->schema($this->qnaFollowUpNotificationOutSchema(), "out");
        $result = ["notificationsSent" => 0];
        if (!$this->questionFollowUpFeatureEnabled()) {
            throw new ClientException("This feature is not enabled.");
        }

        $sender->permission("Garden.Community.Manage");

        $body = $in->validate($body);

        $discussionsAnswered = $this->getDiscussionsAnswered($body["discussionID"] ?? null);
        $notificationsSent = 0;
        $startTime = time();
        foreach ($discussionsAnswered as $discussion) {
            if (!$this->categoryAllowFollowUpNotification($discussion)) {
                continue;
            }

            if ($notificationDate = $this->followUpNotificationAlreadySent($discussion, $this->getFollowUpInterval())) {
                $result["notificationDate"] = $notificationDate;
                $result["message"] = t("A follow-up email was already sent.");
                continue;
            }

            $user = $this->userModel->getID($discussion["InsertUserID"], DATASET_TYPE_ARRAY);
            if (!$this->userAllowFollowUpNotifications($user)) {
                $result["message"] = t("This user has disabled this notification preference.");
                continue;
            }

            $wasSent = $this->sendNotificationEmails($discussion, $user["Email"]);
            if ($wasSent) {
                $notificationsSent++;
                $this->logNotificationsSent($notificationsSent);
                $elapsedTime = time() - $startTime;
                if ($elapsedTime > static::EMAIL_TIMEOUT_THRESHOLD) {
                    break;
                }
            }
        }
        $result["notificationsSent"] = $notificationsSent;

        if (!array_key_exists("message", $result)) {
            //don't override other messages
            $result["message"] = t("QnAFollowUp.Success", "Notifications sent successfully.");
        }

        $out->validate($result);

        $result = \Garden\Web\Data::box($result);
        $result->setHeader("status", 200);
        return $result;
    }

    /**
     * Check category has 'QnaFollowUpNotification'
     *
     * @param mixed $discussion
     * @return bool
     */
    private function categoryAllowFollowUpNotification($discussion = null): bool
    {
        if (!$discussion) {
            return false;
        }

        $categoryID = is_array($discussion) ? $discussion["CategoryID"] : $discussion->CategoryID;
        $category = CategoryModel::categories($categoryID);
        return (bool) ($category["QnaFollowUpNotification"] ?? false);
    }

    /**
     * Check if notification has been sent within the provided interval
     *
     * @param array $discussion
     * @param int $interval
     * @return string
     */
    private function followUpNotificationAlreadySent($discussion, $interval)
    {
        $notificationDate = $this->discussionModel->getRecordAttribute($discussion, "notificationDate");

        if (!$notificationDate) {
            return false;
        }

        return $notificationDate;
    }

    /**
     * Check if user has Preferences.Email.QuestionFollowUp enabled
     *
     * @param array $user
     * @return bool
     */
    private function userAllowFollowUpNotifications(array $user): bool
    {
        return ($user["Preferences"]["Email.QuestionFollowUp"] ??
            \Gdn::config()->get("Preferences.Email.QuestionFollowUp")) ==
            1;
    }

    /**
     * Get discussions that are answered.
     *
     * @param int $discussionID
     * @return iterable
     */
    private function getDiscussionsAnswered(int $discussionID = null): iterable
    {
        $timeThreshold = DateTimeFormatter::timeStampToDateTime(
            strtotime("-" . $this->getFollowUpThreshold() . "days")
        );
        // Should respect interval, and not send email too soon.
        $timeSinceThreshold = DateTimeFormatter::timeStampToDateTime(
            strtotime("-" . $this->getFollowUpInterval() . "days")
        );

        if ($discussionID) {
            $discussionsAnswered = $this->discussionModel
                ->getWhere([
                    "DiscussionID" => $discussionID,
                    "statusID" => self::DISCUSSION_STATUS_ANSWERED,
                ])
                ->resultArray();

            yield from $discussionsAnswered;
        } else {
            $offset = 0;
            do {
                $discussionsAnswered = $this->discussionModel
                    ->getWhere(
                        [
                            "statusID" => self::DISCUSSION_STATUS_ANSWERED,
                            "DateLastComment >" => $timeThreshold,
                            "DateLastComment <" => $timeSinceThreshold,
                        ],
                        "",
                        "",
                        static::ANSWERED_LIMIT,
                        $offset
                    )
                    ->resultArray();

                $count = count($discussionsAnswered);
                $offset = $offset + static::ANSWERED_LIMIT;

                yield from $discussionsAnswered;
            } while ($count >= static::ANSWERED_LIMIT);
        }
    }

    /**
     * Sends an email to the user that has asked a question in the discussions.
     *
     * @param array $discussion
     * @param string $userEmail
     * @return bool $isSent If sending email was successful.
     * @throws Exception Email sending failed.
     */
    private function sendNotificationEmails(array $discussion, string $userEmail)
    {
        $email = Gdn::getContainer()->get(Gdn_Email::class);
        $discussionUrl = $discussion["Url"];
        $message = t(
            "QnAFollowUp.Email.Message",
            "<p>We noticed you have at least one answer to your question." .
                " Can you visit the community and see if any of the answers resolve your question?</p>" .
                "<p>If you see an answer you find helpful, please accept one of the answers.</p>"
        );
        $url = externalUrl($discussionUrl);
        $emailTemplate = $email->getEmailTemplate()->setButton($url, t("Check it out"));
        $emailTemplate->setMessage($message, true);
        $email->setEmailTemplate($emailTemplate);
        $email
            ->to($userEmail)
            ->subject(
                sprintf(
                    t('[%1$s] %2$s'),
                    Gdn::config("Garden.Title"),
                    t("QnAFollowUp.Email.Subject", "Has your question been answered?")
                )
            )
            ->message($message);
        $isSent = false;
        try {
            $isSent = $email->send();
            $this->saveDiscussionAttribute($discussion);
        } catch (Exception $e) {
            if (debug()) {
                throw $e;
            }
        }
        return $isSent;
    }

    /**
     * Log the number of notifications being sent.
     *
     * @param int $notificationsSent
     */
    private function logNotificationsSent(int $notificationsSent)
    {
        $this->logger->info("Email answered question", [
            "event" => "question_notifications_post",
            "timestamp" => time(),
            "notifications Sent" => $notificationsSent,
        ]);
    }

    /**
     * Save date to the discussion's attribute.
     *
     * @param array $discussion
     */
    private function saveDiscussionAttribute(array $discussion)
    {
        $currentTime = DateTimeFormatter::getCurrentDateTime();
        $this->discussionModel->saveToSerializedColumn(
            "Attributes",
            $discussion["DiscussionID"],
            "notificationDate",
            $currentTime
        );
    }

    /**
     * Set the followup interval.
     *
     * @param int $interval
     * @return int
     */
    private function setFollowUpInterval(int $interval): int
    {
        return $this->followUpInterval = $interval;
    }

    /**
     * Get the followup interval.
     *
     * @return int
     */
    private function getFollowUpInterval(): int
    {
        return $this->followUpInterval;
    }

    /**
     * Get the followup threshold.
     *
     * @return int
     */
    private function getFollowUpThreshold(): int
    {
        return $this->followUpThreshold;
    }

    /**
     * Return the follow up notifications sent schema
     *
     * @return Schema
     */
    private function qnaFollowUpNotificationInSchema(): Schema
    {
        $schema = Schema::parse(["discussionID:i?"]);

        return $schema;
    }

    /**
     * Return the follow up notifications sent schema
     *
     * @return Schema
     */
    private function qnaFollowUpNotificationOutSchema(): Schema
    {
        $schema = Schema::parse(["notificationsSent:i", "message:s?"]);
        return $schema;
    }

    /**
     * Update question record type.
     *
     * @param int $discussionID
     * @param array $discussion
     * @param string $type
     */
    public function updateRecordType(int $discussionID, array $discussion, string $type): void
    {
        $this->discussionModel->setField($discussionID, "Type", $type);

        // Update the QnA field.  Default to "Unanswered" for questions. Null the field for other types.
        switch ($type) {
            case "Question":
                $this->recalculateDiscussionQnA($discussion);
                break;
            default:
                $this->discussionStatusModel->determineAndUpdateDiscussionStatus($discussionID);
        }
    }

    /**
     * Get the limit used when getting the total number of unanswered questions.
     *
     * @return int
     */
    public function getUnansweredCountLimit(): int
    {
        return $this->unansweredCountLimit;
    }

    /**
     * Set a limited used when querying the total of unanswered questions.
     *
     * @param int $unansweredCountLimit
     */
    public function setUnansweredCountLimit(int $unansweredCountLimit): void
    {
        $this->unansweredCountLimit = $unansweredCountLimit;
    }
}
