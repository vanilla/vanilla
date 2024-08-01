<?php
/**
 * Manages the activity stream.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Web\Exception\ResponseException;

/**
 * Handles /activity endpoint.
 */
class ActivityController extends Gdn_Controller
{
    /**  @var array Models to include. */
    public $Uses = ["Database", "Form", "ActivityModel"];

    /** @var ActivityModel */
    public $ActivityModel;

    /**
     * Create some virtual properties.
     *
     * @param $name
     * @return Gdn_DataSet
     */
    public function __get($name)
    {
        switch ($name) {
            case "CommentData":
                deprecated("ActivityController->CommentData", "ActivityController->data('Activities')");
                $result = new Gdn_DataSet([], DATASET_TYPE_OBJECT);
                return $result;
            case "ActivityData":
                deprecated("ActivityController->ActivityData", "ActivityController->data('Activities')");
                $result = new Gdn_DataSet($this->data("Activities"), DATASET_TYPE_ARRAY);
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
    public function initialize()
    {
        $this->Head = new HeadModule($this);
        $this->addJsFile("jquery.js");
        $this->addJsFile("jquery.form.js");
        $this->addJsFile("jquery.popup.js");
        $this->addJsFile("jquery.gardenhandleajaxform.js");
        $this->addJsFile("global.js");

        $this->addCssFile("style.css");
        $this->addCssFile("vanillicon.css", "static");

        // Add Modules
        $this->addModule("GuestModule");
        $this->addModule("SignedInModule");
        parent::initialize();
        $this->handleNotSuitableActivityRoute();
        Gdn_Theme::section("ActivityList");
        $this->setData("Breadcrumbs", [["Name" => t("Activity"), "Url" => "/activity"]]);
    }

    /**
     * Handle custom activity routing instead of going to index().
     */
    private function handleNotSuitableActivityRoute()
    {
        $args = Gdn::dispatcher()->controllerArguments();
        // Check custom route with activity ID.
        if (isset($args[0]) && is_numeric($args[0])) {
            $id = array_shift($args);
            // Check custom path.
            $path = array_shift($args);
            switch ($path) {
                case "mark-read-and-redirect":
                    Gdn::dispatcher()->EventArguments["ControllerMethod"] = "markReadAnRedirect";
                    Gdn::dispatcher()->ControllerMethod = "markReadAnRedirect";
                    return;
            }
        }
        Gdn::dispatcher()->controllerArguments($args);
    }

    /**
     * Display a single activity item & comments.
     *
     * Email notifications regarding activities link to this method.
     *
     * @param int $activityID Unique ID of activity item to display.
     * @since 2.0.0
     * @access public
     *
     */
    public function item($activityID = 0)
    {
        $this->addJsFile("activity.js");
        $this->title(t("Activity Item"));

        if (!is_numeric($activityID) || $activityID < 0) {
            $activityID = 0;
        }

        $this->ActivityData = $this->ActivityModel->getWhere(["ActivityID" => $activityID]);

        // Check visibility.
        if (!$this->ActivityModel->canView($this->ActivityData->firstRow())) {
            throw permissionException();
        }

        $this->setData("Comments", $this->ActivityModel->getComments([$activityID]));
        $this->setData("Activities", $this->ActivityData);

        $this->render();
    }

    /**
     * Default activity stream.
     *
     * @param int $offset Number of activity items to skip.
     * @since 2.0.0
     * @access public
     *
     */
    public function index($filter = "feed", $page = false)
    {
        $canonicalTemplate = "/activity/" . $filter . "/{Page}";
        switch (strtolower($filter)) {
            case "mods":
                $this->title(t("Recent Moderator Activity"));
                $this->permission("Garden.Moderation.Manage");
                $notifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case "admins":
                $this->title(t("Recent Admin Activity"));
                $this->permission("Garden.Settings.Manage");
                $notifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            case "":
            case "feed": // rss feed
                $filter = "public";
                $this->title(t("Recent Activity"));
                $this->permission("Garden.Activity.View");
                $notifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
            default:
                throw notFoundException();
        }

        // Which page to load
        [$offset, $limit] = offsetLimit($page, c("Garden.Activities.PerPage", 30));
        $offset = is_numeric($offset) ? $offset : 0;
        if ($offset < 0) {
            $offset = 0;
        }
        $totalRecords = $this->ActivityModel->getUserTotal($notifyUserID);
        PagerModule::current()->configure($offset, $limit, $totalRecords, $canonicalTemplate);

        // Page meta.
        $this->addJsFile("activity.js");

        if ($this->Head) {
            $this->Head->addRss(url("/activity/feed.rss", true), $this->Head->title());
        }

        // Comment submission
        $session = Gdn::session();
        $comment = $this->Form->getFormValue("Comment");
        $activities = $this->ActivityModel
            ->getWhere(["NotifyUserID" => $notifyUserID], "", "", $limit, $offset)
            ->resultArray();
        $this->ActivityModel->joinComments($activities);

        $this->setData("Filter", strtolower($filter));
        $this->setData("Activities", $activities);

        $this->addModule("ActivityFilterModule");

        $this->View = "all";
        $this->render();
    }

    public function deleteComment($iD, $tK, $target = "")
    {
        $session = Gdn::session();
        if (!$session->validateTransientKey($tK)) {
            throw permissionException();
        }

        if (!is_numeric($iD)) {
            throw new Gdn_UserException("Invalid ID");
        }

        $comment = $this->ActivityModel->getComment($iD);
        if (!$comment) {
            throw notFoundException("Comment");
        }

        $activity = $this->ActivityModel->getID(val("ActivityID", $comment));
        if (!$activity) {
            throw notFoundException("Activity");
        }

        if (!$this->ActivityModel->canDelete($activity)) {
            throw permissionException();
        }
        $this->ActivityModel->deleteComment($iD);
        if ($this->deliveryType() === DELIVERY_TYPE_ALL) {
            redirectTo($target);
        }

        $this->render("Blank", "Utility", "Dashboard");
    }

    /**
     * Delete an activity item.
     *
     * @param int|string $activityID Unique ID of item to delete.
     * @param string $transientKey Verify intent.
     */
    public function delete($activityID = "", $transientKey = "")
    {
        $session = Gdn::session();
        if (!$session->validateTransientKey($transientKey)) {
            throw permissionException();
        }

        if (!is_numeric($activityID)) {
            throw new Gdn_UserException("Invalid ID");
        }

        if (!$this->ActivityModel->canDelete($this->ActivityModel->getID($activityID))) {
            throw permissionException();
        }

        $this->ActivityModel->deleteID($activityID);

        if ($this->_DeliveryType === DELIVERY_TYPE_ALL) {
            $target = Gdn::request()->get("Target");
            if ($target) {
                // Bail with a redirect if we got one.
                redirectTo($target);
            } else {
                // We got this as a full page somehow, so send them back to /activity.
                $this->setRedirectTo("activity");
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
    public function comment()
    {
        $this->permission("Garden.Profiles.Edit");

        $session = Gdn::session();
        $this->Form->setModel($this->ActivityModel);
        $newActivityID = 0;

        // Form submitted
        if ($this->Form->authenticatedPostBack()) {
            $body = $this->Form->getValue("Body", "");
            $activityID = $this->Form->getValue("ActivityID", "");
            if (is_numeric($activityID) && $activityID > 0) {
                $activity = $this->ActivityModel->getID($activityID);
                if ($activity) {
                    if ($activity["NotifyUserID"] == ActivityModel::NOTIFY_ADMINS) {
                        $this->permission("Garden.Settings.Manage");
                    } elseif ($activity["NotifyUserID"] == ActivityModel::NOTIFY_MODS) {
                        $this->permission("Garden.Moderation.Manage");
                    }
                } else {
                    throw new Exception(t("Invalid activity"));
                }

                $activityComment = [
                    "ActivityID" => $activityID,
                    "Body" => $body,
                    "Format" => $this->Form->getValue("Format", ""),
                ];

                $iD = $this->ActivityModel->comment($activityComment);

                if ($iD == SPAM || $iD == UNAPPROVED) {
                    $this->StatusMessage = t("Your comment will appear after it is approved.");
                    $this->render("Blank", "Utility");
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
            $target = $this->Form->getValue("Return");
            if (!$target) {
                $target = "/activity";
            }
            redirectTo($target);
        } else {
            // Load the newly added comment.
            $this->setData("Comment", $this->ActivityModel->getComment($iD));

            // Set it in the appropriate view.
            $this->View = "comment";
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
    public function post($notify = false, $userID = false)
    {
        if (is_numeric($notify)) {
            $userID = $notify;
            $notify = false;
        }

        if (!$userID) {
            $userID = Gdn::session()->UserID;
        }

        switch ($notify) {
            case "mods":
                $this->permission("Garden.Moderation.Manage");
                $notifyUserID = ActivityModel::NOTIFY_MODS;
                break;
            case "admins":
                $this->permission("Garden.Settings.Manage");
                $notifyUserID = ActivityModel::NOTIFY_ADMINS;
                break;
            default:
                $this->permission("Garden.Profiles.Edit");
                $notifyUserID = ActivityModel::NOTIFY_PUBLIC;
                break;
        }

        $activities = [];

        if ($this->Form->authenticatedPostBack()) {
            $data = $this->Form->formValues();
            $data = $this->ActivityModel->filterForm($data);
            if (!isset($data["Format"]) || strcasecmp($data["Format"], "Raw") == 0) {
                $data["Format"] = c("Garden.InputFormatter");
            }

            if ($userID != Gdn::session()->UserID) {
                // This is a wall post.
                $activity = [
                    "ActivityType" => "WallPost",
                    "ActivityUserID" => $userID,
                    "RegardingUserID" => Gdn::session()->UserID,
                    "HeadlineFormat" => t(
                        "HeadlineFormat.WallPost",
                        "{RegardingUserID,you} &rarr; {ActivityUserID,you}"
                    ),
                    "Story" => $data["Comment"],
                    "Format" => $data["Format"],
                    "Data" => ["Bump" => true],
                ];
            } else {
                // This is a status update.
                $activity = [
                    "ActivityType" => "Status",
                    "HeadlineFormat" => t("HeadlineFormat.Status", "{ActivityUserID,user}"),
                    "Story" => $data["Comment"],
                    "Format" => $data["Format"],
                    "NotifyUserID" => $notifyUserID,
                    "Data" => ["Bump" => true],
                ];
                $this->setJson("StatusMessage", Gdn_Format::plainText($activity["Story"], $activity["Format"]));
            }

            $activity = $this->ActivityModel->save($activity, false, ["CheckSpam" => true]);
            if ($activity == SPAM || $activity == UNAPPROVED) {
                $this->StatusMessage = t("ActivityRequiresApproval", "Your post will appear after it is approved.");
                $this->render("Blank", "Utility");
                return;
            }

            if ($activity) {
                if ($userID == Gdn::session()->UserID && $notifyUserID == ActivityModel::NOTIFY_PUBLIC) {
                    Gdn::userModel()->setField(
                        Gdn::session()->UserID,
                        "About",
                        Gdn_Format::plainText($activity["Story"], $activity["Format"])
                    );
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
            $target = $this->Request->get("Target", "/activity");
            if (isSafeUrl($target)) {
                redirectTo($target);
            } else {
                redirectTo("/activity");
            }
        }

        $this->setData("Activities", $activities);
        $this->render("Activities");
    }

    /**
     * Set notification as read then redirects logged user to the target of the notification.
     *
     * @param int $activityID Unique ID of activity item to display.
     * @param string $transientKey Transient Key.
     * @throws Gdn_UserException Transient key is not valid orr the user is not the activity owner.
     * @throws ResponseException An exception on redirect.
     */
    public function markReadAnRedirect(int $activityID, string $transientKey = "")
    {
        $session = Gdn::session();
        if (!$session->validateTransientKey($transientKey)) {
            throw permissionException();
        }
        $userID = $session->UserID;
        $activity = $this->ActivityModel->getID($activityID, DATASET_TYPE_ARRAY);
        if (!$activity) {
            throw notFoundException("Activity");
        }
        $notifyUserId = $activity["NotifyUserID"];
        if ($userID === $notifyUserId) {
            $notification = $this->ActivityModel->getNotificationWithCount($activityID);
            $notification["count"] > 1
                ? $this->ActivityModel->updateNotificationStatusBatch($activityID)
                : $this->ActivityModel->updateNotificationStatusSingle($activityID);
            redirectTo($activity["Url"]);
        }
        throw permissionException();
    }
}
