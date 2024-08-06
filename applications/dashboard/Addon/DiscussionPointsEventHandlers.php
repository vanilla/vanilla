<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Gdn;
use LogModel;
use Vanilla\AutomationRules\Triggers\DiscussionReachesScoreTrigger;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Garden\EventHandlersInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Exception\Database\NoResultsException;
use Vanilla\Logger;
use ReactionModel;

/**
 * Event class to trigger actions based on discussion points.
 */
class DiscussionPointsEventHandlers implements EventHandlersInterface
{
    private LoggerInterface $log;
    private AutomationRuleService $automationRuleService;
    private AutomationRuleModel $automationRuleModel;
    private AutomationRuleDispatchesModel $automationRuleDispatchesModel;
    protected array $triggerTypes;

    /**
     * @param AutomationRuleService $automationRuleService
     * @param AutomationRuleModel $automationRuleModel
     * @param AutomationRuleDispatchesModel $automationRuleDispatchesModel
     * @param LoggerInterface $log
     */
    public function __construct(
        AutomationRuleService $automationRuleService,
        AutomationRuleModel $automationRuleModel,
        AutomationRuleDispatchesModel $automationRuleDispatchesModel,
        LoggerInterface $log
    ) {
        $this->automationRuleService = $automationRuleService;
        $this->automationRuleModel = $automationRuleModel;
        $this->automationRuleDispatchesModel = $automationRuleDispatchesModel;
        $this->log = $log;
        $this->triggerTypes = [DiscussionReachesScoreTrigger::getType()];
    }

    /**
     * Fires when a user performs a reaction.
     *
     * @param ReactionModel $sender Current instance of ReactionModel
     * @param array $args Event arguments, passed from ReactionModel, specifically for the event.
     * @throws ContainerException
     * @throws NotFoundException|NoResultsException
     */
    public function reactionModel_reaction_handler(ReactionModel $sender, array $args): void
    {
        $validRecordTypes = array_values(array_filter(array_column(\DiscussionModel::discussionTypes(), "apiType")));

        // Get the record type.
        $recordType = strtolower($args["Record"]["Type"] ?? $args["RecordType"]);
        // If the record type is not a discussion, we don't need to process this event.
        if (!in_array($recordType, $validRecordTypes)) {
            return;
        }

        foreach ($this->triggerTypes as $triggerType) {
            // Check if there is any active rule for this trigger.
            $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules($triggerType);
            if (empty($activeAutomationRules)) {
                continue;
            } else {
                // Process each active rule
                foreach ($activeAutomationRules as $automationRule) {
                    $actionType = $automationRule["actionType"];
                    if (!$this->automationRuleService->isActionRegistered($actionType)) {
                        // Log error and continue as the action is not registered.
                        $this->log->debug("Action $actionType was not processed as it was not registered.", [
                            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                            Logger::FIELD_EVENT => "reactionModel_reaction_handler",
                            Logger::FIELD_TAGS => ["automationRules"],
                            "automationRuleID" => $automationRule["automationRuleID"],
                            "automationRuleRevisionID" => $automationRule["automationRuleRevisionID"],
                        ]);
                        continue;
                    }

                    // If the trigger specified post types, check if the current post type is in the list.
                    // If it's not, we don't need to process this event.
                    $recordType = strtolower($args["Record"]["Type"] ?? $args["RecordType"]);
                    if (
                        count($automationRule["triggerValue"]["postType"]) > 0 &&
                        !in_array($recordType, $automationRule["triggerValue"]["postType"] ?? [])
                    ) {
                        continue;
                    }

                    // If the discussion score is lower than the automation rule trigger value, we don't need to process this event.
                    $points = (int) $args["Record"]["Score"];
                    if ($points < $automationRule["triggerValue"]["score"] ?? null) {
                        continue;
                    }

                    // Check if the automation rule has already been triggered for this record.
                    $logModel = GDN::getContainer()->get(LogModel::class);

                    // Process the action for the rule
                    $actionClass = $this->automationRuleService->getAction($actionType);
                    if (!$actionClass) {
                        return;
                    }

                    // Create new dispatchID
                    $dispatchID = $this->automationRuleDispatchesModel->generateDispatchUUID(
                        [
                            "automationRuleID" => $automationRule["automationRuleID"],
                            "actionType" => $actionType,
                            "recordType" => $recordType,
                            "recordID" => $args["RecordID"],
                        ],
                        false
                    );

                    $action = new $actionClass(
                        $automationRule["automationRuleID"],
                        AutomationRuleDispatchesModel::TYPE_TRIGGERED,
                        $dispatchID
                    );

                    // Check the event logs to see if the automation rule has already been triggered for this record.
                    $eventLogs = $logModel->getWhere([
                        "Operation" => "Automation",
                        "DispatchUUID" => $dispatchID,
                    ]);

                    // If the automation rule has already been triggered for this record, we don't need to process this event.
                    if (!empty($eventLogs)) {
                        continue;
                    }

                    $action->setDiscussionID($args["RecordID"]);
                    if (method_exists($action, "setActionValue")) {
                        $action->setActionValue($automationRule["actionValue"] ?? []);
                    }
                    $action->execute();

                    // Update dispatch status
                    $this->automationRuleDispatchesModel->updateDispatchStatus(
                        $dispatchID,
                        AutomationRuleDispatchesModel::STATUS_SUCCESS,
                        ["affectedRecordCount" => 1]
                    );
                }
            }
        }
    }
}
