<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Addon;

use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Dashboard\Models\ProfileFieldModel;
use Vanilla\Utility\DebugUtils;

abstract class AbstractProfileFieldEventHandler
{
    protected string $actionType;
    protected string $triggerType;

    protected LoggerInterface $log;
    protected AutomationRuleService $automationRuleService;

    protected AutomationRuleModel $automationRuleModel;
    public function __construct(
        AutomationRuleService $automationRuleService,
        AutomationRuleModel $automationRuleModel,
        LoggerInterface $log
    ) {
        $this->automationRuleService = $automationRuleService;
        $this->automationRuleModel = $automationRuleModel;
        $this->log = $log;
        $this->triggerType = ProfileFieldSelectionTrigger::getType();
        $this->actionType = ""; // override the action type in the child class
    }

    /**
     * Check if there are any active automation rules for the trigger and action types.
     *
     * @return bool
     */
    protected function isActionAvailableForExecution(): bool
    {
        if (
            !$this->automationRuleModel->getTotalAutomationRulesByTriggerActionStatus(
                $this->triggerType,
                $this->actionType
            )
        ) {
            if (DebugUtils::isTestMode()) {
                $this->log->debug("No active automation rules found for $this->triggerType and $this->actionType");
            }
            return false;
        }
        return true;
    }

    /**
     * Check if the rule needs to be executed.
     *
     * @param array $revisionData
     * @param array $currentUserProfileFields
     * @param array $updatedUserProfileFields
     * @return bool
     */
    protected function checkIfRuleNeedsExecution(
        array $revisionData,
        array $currentUserProfileFields,
        array $updatedUserProfileFields
    ): bool {
        if (!$this->validateRuleValues($revisionData)) {
            return false;
        }
        $triggerProfileField = $revisionData["triggerValue"]["profileField"];
        $profileFieldKey = key($triggerProfileField);
        // If the user hasn't selected the profile field then we don't need to execute the action
        if (!isset($updatedUserProfileFields[$profileFieldKey])) {
            return false;
        } else {
            $updatedUserProfileFieldValue = $updatedUserProfileFields[$profileFieldKey];
            $currentUserProfileFieldValue = $currentUserProfileFields[$profileFieldKey] ?? null;
            // If the user's selection hasn't changed from previous then we don't need to execute the action
            if ($updatedUserProfileFieldValue === $currentUserProfileFieldValue) {
                return false;
            }
            $triggerProfileFieldValue = $triggerProfileField[$profileFieldKey];
            // If the user's selection doesn't match the trigger value then we don't need to execute the action
            if (is_array($triggerProfileFieldValue)) {
                $userProfileFieldValue = is_array($updatedUserProfileFieldValue)
                    ? $updatedUserProfileFieldValue
                    : [$updatedUserProfileFieldValue];
                if (!array_intersect($triggerProfileFieldValue, $userProfileFieldValue)) {
                    return false;
                }
            } elseif ($updatedUserProfileFieldValue !== $triggerProfileFieldValue) {
                return false;
            }
        }
        return true;
    }

    /**
     * Handler that's triggered when a profileField is created or updated.
     *
     * @param ProfileFieldModel $sender
     * @param int $userID
     * @param array $updatedProfileFields
     * @param array $currentProfileFields
     * @return void
     * @throws \Exception
     */
    public function userProfileMetaUpdate_handler(
        ProfileFieldModel $sender,
        int $userID,
        array $updatedProfileFields,
        array $currentProfileFields
    ): void {
        if (!$this->isActionAvailableForExecution()) {
            return;
        }
        $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
            $this->triggerType,
            $this->actionType
        );
        $revisionKeys = [
            "triggerType" => "",
            "triggerValue" => "",
            "actionType" => "",
            "actionValue" => "",
            "automationRuleID" => "",
        ];

        foreach ($activeAutomationRules as $automationRule) {
            $revisionData = array_intersect_key($automationRule, $revisionKeys);
            if (!$this->checkIfRuleNeedsExecution($revisionData, $currentProfileFields, $updatedProfileFields)) {
                if (DebugUtils::isTestMode()) {
                    $this->log->debug("Automation rule {$automationRule["automationRuleID"]} skipped for user $userID");
                }
            } else {
                $this->executeAction($automationRule["automationRuleID"], $userID);
            }
        }
    }

    /**
     * Execute the action for the automation rule.
     *
     * @param int $automationRuleID
     * @param int $userID
     * @return void
     */
    protected function executeAction(int $automationRuleID, int $userID)
    {
        $action = $this->automationRuleService->getAction($this->actionType);
        if (!$action) {
            return;
        }
        $triggerAction = new $action($automationRuleID);
        $triggerAction->setUserID($userID);
        $triggerAction->execute();
    }
}
