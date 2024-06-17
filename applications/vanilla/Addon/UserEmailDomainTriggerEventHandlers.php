<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Garden\PsrEventHandlersInterface;
use Psr\Log\LoggerInterface;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Dashboard\Events\UserEvent;
use Vanilla\Dashboard\Models\AutomationRuleDispatchesModel;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Logger;
use Vanilla\Utility\DebugUtils;

/**
 * Event class to trigger actions based on user email.
 */
class UserEmailDomainTriggerEventHandlers implements PsrEventHandlersInterface
{
    protected array $actionTypes;
    protected string $triggerType;

    protected LoggerInterface $log;
    protected AutomationRuleService $automationRuleService;
    protected AutomationRuleModel $automationRuleModel;
    protected AutomationRuleDispatchesModel $automationRuleDispatchesModel;

    /**
     * Constructor
     *
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

        $this->triggerType = UserEmailDomainTrigger::getType();
        $this->actionTypes = UserEmailDomainTrigger::getActions();
    }

    /**
     * @inheritdoc
     */
    public static function getPsrEventHandlerMethods(): array
    {
        return ["handleUserEvent"];
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
            $actionType,
            AutomationRuleModel::STATUS_ACTIVE
        );

        if (!$ruleCount && DebugUtils::isTestMode()) {
            $this->log->debug("No active automation rules found for $this->triggerType and $actionType");
        }
        return $ruleCount > 0;
    }

    /**
     * Handle a user event.
     *
     * @param UserEvent $event
     * @return UserEvent
     */
    public function handleUserEvent(UserEvent $event): UserEvent
    {
        $action = $event->getAction();
        $payload = $event->getPayload();
        $user = $payload["user"] ?? [];

        // Early exit. If the user is not present in the payload, we don't need to do anything.
        if (empty($user)) {
            return $event;
        }

        // If the user is not confirmed or if the event action is delete, we don't need to do anything.
        if (!$user["emailConfirmed"] || !in_array($action, [UserEvent::ACTION_INSERT, UserEvent::ACTION_UPDATE])) {
            if (DebugUtils::isTestMode()) {
                $this->log->info(
                    "Skipped processing user record for {$user["email"]}. User is either not confirmed or is not a valid user update.",
                    [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                        Logger::FIELD_TAGS => ["automation rules"],
                        "user" => $user,
                    ]
                );
            }
            return $event;
        }
        if ($action === UserEvent::ACTION_UPDATE) {
            // see if we have a change in their email address and if so, trigger the action
            $previousUserRecord = $payload["existingData"];
            // If the user's email address has not changed, we don't need to do anything.
            if (!empty($previousUserRecord) && $previousUserRecord["email"] === $user["email"]) {
                // If the user email was not confirmed before, and now it is, we need to process the rule.
                if ($previousUserRecord["emailConfirmed"] === true && $user["emailConfirmed"] === true) {
                    $this->log->info(
                        "Skipped processing user record for {$user["email"]}. Email address has not changed.",
                        [
                            Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                            Logger::FIELD_TAGS => ["automation rules"],
                            "user" => $user,
                        ]
                    );
                    return $event;
                }
            }
            if (empty($previousUserRecord)) {
                // This event might have been triggered outside a currently known context, so log a warning.
                $this->log->info(
                    "Skipped processing user record for {$user["email"]}. No previous user record found.",
                    [
                        Logger::FIELD_CHANNEL => Logger::CHANNEL_APPLICATION,
                        Logger::FIELD_TAGS => ["automation rules"],
                        "user" => $user,
                        "sender" => $event->getSender(),
                    ]
                );
                return $event;
            }
        }

        // Process the user email domain trigger if we have a valid email address.
        if ($user["email"] ?? false) {
            $this->processUserEmailDomainTrigger($user["userID"], $user["email"]);
        }

        return $event;
    }

    /**
     * Validate and process email domain trigger
     *
     * @param UserEvent $event
     * @param array $activeAutomationRules
     * @param array $userEmailDomain
     * @return void
     */
    private function processUserEmailDomainTrigger(int $userID, string $email)
    {
        foreach ($this->actionTypes as $actionType) {
            if (!$this->isActionAvailableForExecution($actionType)) {
                continue;
            }

            $activeAutomationRules = $this->automationRuleModel->getActiveAutomationRules(
                $this->triggerType,
                $actionType
            );
            foreach ($activeAutomationRules as $automationRule) {
                $triggerEmailDomains = $automationRule["triggerValue"]["emailDomain"] ?? [];
                $triggerEmailDomains = array_map(fn($v) => trim(strtolower($v)), explode(",", $triggerEmailDomains));
                $userEmailDomain = strtolower(substr(strrchr($email, "@"), 1));
                if (!in_array($userEmailDomain, $triggerEmailDomains)) {
                    continue;
                }
                $this->executeAction($actionType, $automationRule["automationRuleID"], $userID);
            }
        }
    }

    /**
     * Execute the action for the automation rule.
     *
     * @param string $actionType
     * @param int $automationRuleID
     * @param int $userID
     * @return void
     */
    protected function executeAction(string $actionType, int $automationRuleID, int $userID)
    {
        $action = $this->automationRuleService->getAction($actionType);
        if (!$action) {
            return;
        }
        $triggerAction = new $action($automationRuleID);
        $triggerAction->setUserID($userID);
        $triggerAction->execute();
    }
}
