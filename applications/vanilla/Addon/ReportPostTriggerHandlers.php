<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use CategoryModel;
use Vanilla\AutomationRules\Triggers\ReportPostTrigger;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Forum\Models\CommunityManagement\ReportModel;
use Vanilla\Logger;
use Vanilla\Utility\DebugUtils;

/**
 * Event class to trigger actions based on post reports.
 */
class ReportPostTriggerHandler
{
    protected array $actionTypes;
    protected string $triggerType;

    /**
     * Constructor
     *
     * @param AutomationRuleService $automationRuleService
     * @param AutomationRuleModel $automationRuleModel
     * @param ReportModel $reportModel
     * @param CategoryModel $categoryModel
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel
     * @param LoggerInterface $log
     */
    public function __construct(
        private AutomationRuleService $automationRuleService,
        private AutomationRuleModel $automationRuleModel,
        private ReportModel $reportModel,
        private CategoryModel $categoryModel,
        private AutomationRuleDispatchesModel $automationRuleDispatchesModel,
        private LoggerInterface $log
    ) {
        $this->triggerType = ReportPostTrigger::getType();
        $this->actionTypes = ReportPostTrigger::getActions();
    }

    /**
     * Check if there are any active automation rules for the trigger and action types.
     *
     * @param string $actionType
     * @return bool
     */
    protected function isActionAvailableForExecution(string $actionType): bool
    {
        $ruleCount = $this->automationRuleModel->getTotalAutomationRulesByTriggerActionStatus(
            $this->triggerType,
            $actionType
        );

        if (!$ruleCount && DebugUtils::isTestMode()) {
            $this->log->debug("No active automation rules found for $this->triggerType and $actionType");
        }
        return $ruleCount > 0;
    }

    /**
     * Handle a post report action.  Trigger action as system user.
     *
     * @param int $reportID
     * @return void
     */
    public function handleUserEvent(int $reportID): void
    {
        $this->automationRuleService->runWithSystemUser(function () use ($reportID) {
            $this->evaluateReportRules($reportID);
        });
    }

    /**
     * Evaluate and execute automation Rule for report count.
     *
     * @param int $reportID
     * @return void
     */
    public function evaluateReportRules(int $reportID): void
    {
        $report = $this->reportModel->selectVisibleReports(["r.reportID" => $reportID])[0];
        $recordReports = $this->reportModel->selectVisibleReports([
            "r.recordID" => $report["recordID"],
            "r.recordType" => $report["recordType"],
            "r.status" => "new",
        ]);

        // Early exit. If the user is not present in the payload, we don't need to do anything.
        if (empty($recordReports)) {
            return;
        }

        foreach ($this->actionTypes as $action) {
            $actionType = $action::getType();
            if (!$this->isActionAvailableForExecution($actionType)) {
                continue;
            }

            $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
                $this->triggerType,
                $actionType
            );
            foreach ($activeAutomationRules as $automationRule) {
                $reportReason = $automationRule["triggerValue"]["reportReasonID"] ?? [];
                $categoryIDs = $automationRule["triggerValue"]["categoryID"] ?? null;
                if ($categoryIDs !== null) {
                    $categories = [];
                    foreach ($categoryIDs as $categoryID) {
                        $categories = array_merge(
                            $categories,
                            $this->categoryModel->getSearchCategoryIDs(
                                $categoryID,
                                false,
                                $automationRule["triggerValue"]["includeSubcategories"] ?? false
                            )
                        );
                    }
                    $recordReports = array_filter($recordReports, function ($recordReport) use ($categories) {
                        return in_array($recordReport["placeRecordID"], $categories);
                    });
                }
                if ($reportReason !== null) {
                    $recordReports = array_filter($recordReports, function ($recordReport) use ($reportReason) {
                        $reasons = array_filter($recordReport["reasons"], function ($reason) use ($reportReason) {
                            return in_array($reason["reportReasonID"], $reportReason);
                        });

                        return count($reasons) > 0;
                    });
                }

                if (count($recordReports) < $automationRule["triggerValue"]["countReports"]) {
                    continue;
                }

                $this->executeAction($actionType, $automationRule["automationRuleID"], $report);
            }
        }
    }

    /**
     * Execute the action for the automation rule.
     *
     * @param string $actionType
     * @param int $automationRuleID
     * @param array $report
     * @return void
     */
    protected function executeAction(string $actionType, int $automationRuleID, array $report)
    {
        $action = $this->automationRuleService->getAction($actionType);
        if (!$action) {
            return;
        }

        $object = $report;
        // Create new dispatchID
        $dispatchID = $this->automationRuleDispatchesModel->generateDispatchUUID([
            "automationRuleID" => $automationRuleID,
            "actionType" => $actionType,
            "recordID" => $object["recordID"],
            "recordType" => $object["recordType"],
        ]);
        $triggerAction = new $action($automationRuleID, AutomationRuleDispatchesModel::TYPE_TRIGGERED, $dispatchID);
        if ($report["recordType"] == "comment") {
            $object["CommentID"] = $report["recordID"];
        } else {
            $object["DiscussionID"] = $report["recordID"];
        }
        $triggerAction->setPostRecord($object);
        $errorData = [
            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
            Logger::FIELD_TAGS => ["automationRules", $actionType],
            "automationRuleID" => $automationRuleID,
            "recordID" => $object["recordID"],
            "recordType" => $object["recordType"],
            "dispatchID" => $dispatchID,
        ];
        try {
            $triggerAction->execute();
        } catch (\Exception $e) {
            $errorData["error"] = $e->getMessage();
            $this->log->error("Error executing action $actionType for automation rule $automationRuleID:", $errorData);
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $dispatchID,
                AutomationRuleDispatchesModel::STATUS_FAILED,
                [],
                $e->getMessage()
            );
            return;
        } catch (\Throwable $e) {
            $errorData["error"] = $e->getMessage();
            $this->log->error("Error executing action $actionType for automation rule $automationRuleID: ", $errorData);
            $this->automationRuleDispatchesModel->updateDispatchStatus(
                $dispatchID,
                AutomationRuleDispatchesModel::STATUS_FAILED,
                [],
                $e->getMessage()
            );
            return;
        }

        // Update dispatch status
        $this->automationRuleDispatchesModel->updateDispatchStatus(
            $dispatchID,
            AutomationRuleDispatchesModel::STATUS_SUCCESS,
            ["affectedRecordCount" => 1]
        );
    }
}
