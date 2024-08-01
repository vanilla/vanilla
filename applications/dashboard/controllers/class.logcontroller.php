<?php
/**
 * Non-activity action logging.
 *
 * @copyright 2009-2019 Vanilla Forums Inc.
 * @license GPL-2.0-only
 * @package Dashboard
 * @since 2.0
 */

use Garden\Schema\Schema;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;

/**
 * Handles /log endpoint.
 */
class LogController extends DashboardController
{
    /** @var array Objects to prep. */
    public $Uses = ["Form", "LogModel"];

    /** @var Gdn_Form */
    public $Form;

    /** @var LogModel */
    public $LogModel;

    /**
     * Confirmation page.
     *
     * @since 2.0.?
     * @access public
     *
     * @param string $action Type of action.
     * @param array $logIDs Numeric IDs of items to confirm.
     */
    public function confirm()
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission(["Garden.Moderation.Manage"], false);
        $action = Gdn::request()->post("Action", false);
        $logIDs = Gdn::request()->post("IDs", false);

        $this->Form->addHidden("LogIDs", $logIDs);
        $this->Form->IDPrefix = "Confirm_";

        if (trim($logIDs)) {
            $logIDArray = explode(",", $logIDs);
        } else {
            $logIDArray = [];
        }

        // We also want to collect the users from the log.
        $logs = $this->LogModel->getIDs($logIDArray);
        $users = [];
        foreach ($logs as $log) {
            $userID = $log["RecordUserID"];
            if (!$userID) {
                continue;
            }
            $users[$userID] = ["UserID" => $userID];
        }
        Gdn::userModel()->joinUsers($users, ["UserID"]);
        $this->setData("Users", $users);

        $this->setData("Action", $action);
        $this->setData("ActionUrl", url("/log/" . $action));
        $this->setData("ItemCount", count($logIDArray));
        $this->render();
    }

    /**
     * Count log items.
     *
     * @since 2.0.?
     * @access public
     *
     * @param string $operation Comma-separated ist of action types to find.
     */
    public function count($operation)
    {
        // Don't use Gdn_Controller->permission() here because this isn't a "real page".
        if (!Gdn::session()->checkPermission(["Garden.Moderation.Manage"], false)) {
            $this->statusCode(403);
            echo "";
            return;
        }

        if ($operation == "edits") {
            $operation = ["edit", "delete"];
        } else {
            $operation = explode(",", $operation);
        }
        array_map("ucfirst", $operation);

        $count = $this->LogModel->getCountWhere(["Operation" => $operation]);

        if ($count > 0) {
            echo '<span class="Alert">', $count, "</span>";
        }
    }

    /**
     * Delete logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $logIDs Numeric IDs of logs to delete.
     */
    public function delete()
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission(["Garden.Moderation.Manage"], false);
        $logIDs = Gdn::request()->post("LogIDs");
        $this->LogModel->deleteIDs($logIDs);
        $this->render("Blank", "Utility");
    }

    /**
     * Delete spam and optionally delete the users.
     * @param type $logIDs
     */
    public function deleteSpam()
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission(["Garden.Moderation.Manage"], false);
        $logIDs = Gdn::request()->post("LogIDs");
        $logIDs = explode(",", $logIDs);

        // Ban the appropriate users.
        $userIDs = $this->Form->getFormValue("UserID", []);
        if (!is_array($userIDs)) {
            $userIDs = [];
        }

        if (!empty($userIDs)) {
            // Grab the rest of the log entries.
            $otherLogIDs = $this->LogModel->getWhere(["Operation" => "Spam", "RecordUserID" => $userIDs]);
            $otherLogIDs = array_column($otherLogIDs, "LogID");
            $logIDs = array_merge($logIDs, $otherLogIDs);

            foreach ($userIDs as $userID) {
                Gdn::userModel()->ban($userID, ["Reason" => "Spam", "DeleteContent" => true, "Log" => true]);
            }
        }

        // Grab the logs.
        $this->LogModel->deleteIDs($logIDs);
        $this->render("Blank", "Utility");
    }

    /**
     * View list of edits (edit/delete actions).
     *
     * @param string $type
     * @param string $page Page number.
     * @param string|false $op
     */
    public function edits($type = "", $page = "", $op = false)
    {
        $this->permission("Garden.Moderation.Manage");
        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Change Log"));
        $this->setData("_flaggedByTitle", t("Updated By"));

        $operations = ["Edit", "Delete", "Ban", "Spoof"];
        if ($op && in_array(ucfirst($op), $operations)) {
            $operations = ucfirst($op);
        }

        $where = [
            "Operation" => $operations,
        ];

        $allowedTypes = ["Discussion", "Comment", "Activity", "User"];

        if (Gdn::session()->checkPermission("site.manage")) {
            $allowedTypes[] = "Configuration";
        }
        $type = ucfirst(strtolower($type));
        if (in_array($type, ["Configuration", "Spoof"])) {
            $this->permission("site.manage");
            $where["RecordType"] = $type;
        } else {
            if (in_array($type, $allowedTypes)) {
                $where["RecordType"] = $type;
            } else {
                $where["RecordType"] = $allowedTypes;
            }
        }

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        }

        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/edits");
        $this->render();
    }

    /**
     * Access the log history of a specific record
     *
     * @param string $recordType
     * @param int $recordID
     * @throws Gdn_UserException If non-system user accesses recordType configuration.
     */
    public function record($recordType, $recordID, $page = "")
    {
        $this->permission("Garden.Moderation.Manage");

        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Change Log"));

        $recordType = ucfirst($recordType);
        if ($recordType === "Configuration" && Gdn::session()->User->Admin !== 2) {
            throw forbiddenException("@" . t("You do not have permission to access the requested resource."));
        }
        $where = [
            "Operation" => ["Edit", "Delete", "Ban"],
            "RecordType" => $recordType,
            "RecordID" => $recordID,
        ];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        }

        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/edits");
        $this->render();
    }

    /**
     * Searches the logs for edit, delete or ban operations on posts made by the user with the given user ID.
     *
     * @param $recordUserID The user ID to search the logs for.
     * @param string $page The page number.
     * @throws Exception
     */
    public function user($recordUserID, $page = "")
    {
        if (!is_numeric($recordUserID)) {
            throw new Exception("Invalid ID");
        }
        $this->permission("Garden.Moderation.Manage");
        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Change Log by User"));

        $where = [
            "Operation" => ["Edit", "Delete", "Ban"],
            "RecordUserID" => $recordUserID,
        ];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        }

        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/edits");
        $this->render("record");
    }

    /**
     * Convenience method to call model's FormatContent.
     *
     * @since 2.0.?
     * @access protected
     *
     * @param object $log .
     */
    protected function formatContent($log)
    {
        return $this->LogModel->formatContent($log);
    }

    /**
     * Always triggered first. Add Javascript files.
     *
     * @since 2.0.?
     * @access public
     */
    public function initialize()
    {
        parent::initialize();
        Gdn_Theme::section("Moderation");
        $this->addJsFile("log.js");
        $this->addJsFile("jquery.expander.js");
        $this->addJsFile("jquery-ui.min.js");
        $this->addJsFile("jquery.popup.js");
    }

    /**
     * View moderation logs.
     *
     * @param string $page Page number.
     */
    public function moderation($page = "")
    {
        $this->permission(["Garden.Moderation.Manage"], false);

        $where = ["Operation" => ["Moderate", "Pending"]];

        // Filter by category menu
        if ($categoryID = Gdn::request()->getValue("CategoryID")) {
            $this->setData("ModerationCategoryID", $categoryID);
            $where["CategoryID"] = $categoryID;
        }

        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Moderation Queue"));

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        }

        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/moderation");
        $this->render();
    }

    /**
     * Restore logs.
     *
     * @since 2.0.?
     * @access public
     *
     * @param array $logIDs List of log IDs.
     */
    public function restore()
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission(["Garden.Moderation.Manage"], false);
        $logIDs = Gdn::request()->post("LogIDs");

        // Grab the logs.
        $logs = $this->LogModel->getIDs($logIDs);
        foreach ($logs as $log) {
            $this->LogModel->restore($log);
        }
        $this->LogModel->recalculate();
        $this->render("Blank", "Utility");
    }

    public function notSpam()
    {
        if (!Gdn::request()->isAuthenticatedPostBack(true)) {
            throw new Exception("Requires POST", 405);
        }
        $this->permission(["Garden.Moderation.Manage"], false);
        $logIDs = Gdn::request()->post("LogIDs");

        $logs = [];

        // Verify the appropriate users.
        $userIDs = $this->Form->getFormValue("UserID", []);
        if (!is_array($userIDs)) {
            $userIDs = [];
        }

        foreach ($userIDs as $userID) {
            Gdn::userModel()->setField($userID, "Verified", true);
            $logs = array_merge($logs, $this->LogModel->getWhere(["Operation" => "Spam", "RecordUserID" => $userID]));
        }

        // Grab the logs.
        $logs = array_merge($logs, $this->LogModel->getIDs($logIDs));
        $logs = array_column($logs, null, "LogID");

        //      try {
        foreach ($logs as $log) {
            if ($this->checkUserRecord($log) && $log["RecordType"] === "Registration") {
                $this->LogModel->delete(["LogID" => $log["LogID"]]);
                continue;
            }
            $this->LogModel->restore($log);
        }
        //      } catch (Exception $Ex) {
        //         $this->Form->addError($Ex->getMessage());
        //      }
        $this->LogModel->recalculate();

        // Clear LogCount's cache
        $this->LogModel::clearOperationCountCache("spam");

        $this->setData("Complete");
        $this->setData("Count", count($logs));
        $this->render("Blank", "Utility");
    }

    /**
     * Check if a user log record already exists in user table.
     *
     * @param array $log
     * @return bool If a user record already exists.
     */
    private function checkUserRecord(array $log): bool
    {
        $isUserDuplicate = $userEmailExists = false;
        $emailUnique = Gdn::userModel()->isEmailUnique();
        if (isset($log["Data"]["Email"])) {
            $userLogEmail = $log["Data"]["Email"];
            $userEmailExists = Gdn::userModel()->getByEmail($userLogEmail, false, ["dataType" => DATASET_TYPE_ARRAY]);
        }

        if ($userEmailExists && $emailUnique) {
            $isUserDuplicate = true;
        }
        return $isUserDuplicate;
    }

    /**
     * View spam logs.
     *
     * @param string $page Page number.
     */
    public function spam($page = "")
    {
        $this->permission(["Garden.Moderation.Manage"], false);
        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Spam Queue"));

        $where = ["Operation" => ["Spam"]];

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        }

        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/spam");
        $this->render();
    }

    /**
     * This is a general purpose log page that filters the log according to querystring parameters.
     *
     * @param string $page
     */
    public function filter($page = "")
    {
        $this->permission("Garden.Moderation.Manage");
        Gdn_Theme::section("Moderation");
        $this->setHighlightRoute("dashboard/log/edits");
        if ($this->deliveryType() == DELIVERY_TYPE_VIEW) {
            $this->View = "Table";
        } else {
            $this->View = "Edits";
        }

        [$offset, $limit] = offsetLimit($page, 10);
        $this->setData("Title", t("Change Log"));

        $in = Schema::parse([
            "operation:a?" => [
                "style" => "form",
                "items" => [
                    "type" => "string",
                    "enum" => ["edit", "delete", "ban"],
                ],
            ],
            // Keep this required to avoid security issues.
            "recordType:s" => [
                "enum" => ["comment", "discussion", "user"],
            ],
            "recordID:i?",
            "parentRecordID:i?",
        ])->requireOneOf(["operation", "recordID", "parentRecordID"]);

        try {
            $where = $in->validate($this->Request->get());
        } catch (\Garden\Schema\ValidationException $ex) {
            $this->Form->addError($ex);
            $this->render();
            return;
        }

        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }

        $log = $this->LogModel->getWhere($where, "LogID", "Desc", $offset, $limit);
        $this->setData("Log", $log);

        $this->render();
    }

    /**
     * Get list of affected records based on DispatchUUID.
     *
     * @param string $dispatchUUID
     * @param string $page
     * @return void
     * @throws Gdn_UserException
     * @throws \Garden\Container\ContainerException
     * @throws \Garden\Container\NotFoundException
     * @throws \Garden\Web\Exception\NotFoundException
     * @throws \Vanilla\Exception\Database\NoResultsException
     */
    public function automationRules(string $dispatchUUID = "", string $page = ""): void
    {
        if (empty($dispatchUUID)) {
            throw notFoundException("Page");
        }
        $this->permission("Garden.Moderation.Manage");
        [$offset, $limit] = offsetLimit($page, 10);
        $this->setHighlightRoute("dashboard/log/automationRules");
        $automationDispatchModel = Gdn::getContainer()->get(AutomationRuleDispatchesModel::class);
        $dispatchRecord = $automationDispatchModel->getAutomationRuleDispatchByUUID($dispatchUUID);
        if (!$dispatchRecord) {
            throw notFoundException("Page");
        }

        $where = [
            "DispatchUUID" => $dispatchUUID,
            "Operation" => ["Automation"],
        ];
        // Get the total number of records for this dispatch.
        $recordCount = $this->LogModel->getCountWhere($where);
        $this->setData("RecordCount", $recordCount);
        if ($offset >= $recordCount) {
            $offset = $recordCount - $limit;
        }
        $log = $this->LogModel->getAutomationLogsByDispatchID(
            $dispatchUUID,
            $dispatchRecord["attributes"]["affectedRecordType"],
            $limit,
            $offset,
            true
        );

        // Set data for the view
        $this->setData("LogModel", $this->LogModel);
        $this->setData("Title", t("Automation Rule Dispatch " . "#$dispatchUUID"));
        $automationDispatchRecord = [
            "DispatchID" => $dispatchUUID,
            "TriggerName" => $dispatchRecord["trigger"]["triggerName"],
            "ActionName" => $dispatchRecord["action"]["actionName"],
            "DispatchType" => ucfirst($dispatchRecord["dispatchType"]),
            "DispatchStatus" => ucfirst($dispatchRecord["status"]),
            "RecordType" => $dispatchRecord["attributes"]["affectedRecordType"] ?? "",
        ];
        $this->setData("AutomationDispatchRecord", $automationDispatchRecord);
        $this->setData("Log", $log);
        Gdn_Theme::section("Moderation");
        $this->render();
    }
}
