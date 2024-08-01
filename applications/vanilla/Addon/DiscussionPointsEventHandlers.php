<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Community\Events;

use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Garden\EventHandlersInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
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
        $this->triggerTypes = [];
    }

    /**
     * Fires when a user performs a reaction.
     *
     * @param ReactionModel $sender Current instance of ReactionModel
     * @param array $args Event arguments, passed from ReactionModel, specifically for the event.
     */
    public function reactionModel_reaction_handler(ReactionModel $sender, array $args): void
    {
        // If the record type is not a discussion, we don't need to process this event.
        if (strtolower($args["RecordType"]) !== "discussion") {
            return;
        }
        foreach ($this->triggerTypes as $triggerType) {
            // Check if there is any active rule for this trigger.
            $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules($triggerType);
            if (empty($activeAutomationRules)) {
                continue;
            } else {
                // If the rule is active and the plugin is disabled, we don't need to process this event.
                if (!$this->automationRuleService->isTriggerRegistered($triggerType)) {
                    // Log error and continue as the trigger is not registered.
                    $this->log->debug("Trigger $triggerType was not processed as it was not registered.", [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                        Logger::FIELD_EVENT => "reactionModel_reaction_handler",
                        Logger::FIELD_TAGS => ["automationRules"],
                    ]);
                    continue;
                }
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
                    // Get the current score value for the post
                    $points = (int) $args["Record"]["Score"];
                    // If the discussion score doesn't match the score in the automation rule, we don't need to process this event.
                    if ($points != $automationRule["triggerValue"]["score"] ?? null) {
                        continue;
                    }
                    // Create new dispatchID
                    $dispatchID = $this->automationRuleDispatchesModel->generateDispatchUUID(
                        [
                            "automationRuleID" => $automationRule["automationRuleID"],
                            "actionType" => $actionType,
                            "recordID" => $args["RecordID"],
                        ],
                        false
                    );

                    // Process the action for the rule
                    $actionClass = $this->automationRuleService->getAction($actionType);
                    if (!$actionClass) {
                        return;
                    }
                    $action = new $actionClass(
                        $automationRule["automationRuleID"],
                        AutomationRuleDispatchesModel::TYPE_TRIGGERED,
                        $dispatchID
                    );
                    $action->setDiscussionID($args["RecordID"]);
                    $action->execute();
                }
            }
        }
    }
}
