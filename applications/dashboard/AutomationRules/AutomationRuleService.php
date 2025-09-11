<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules;

use Exception;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\AutomationRules\Trigger\TimedAutomationTrigger;
use Webmozart\Assert\Assert;

class AutomationRuleService
{
    /** @var array<class-string<AutomationTrigger>> */
    private array $automationTriggers = [];

    /** @var array<class-string<AutomationAction>> */
    private array $automationActions = [];

    private EscalationRuleService $escalationRuleService;

    /**
     * Constructor
     */
    public function __construct(EscalationRuleService $escalationRuleService)
    {
        $this->escalationRuleService = $escalationRuleService;
    }

    /**
     * Add an automation trigger
     *
     * @param <class-string<AutomationTrigger>> $automationTrigger
     * @return void
     * @throws Exception
     */
    public function addAutomationTrigger(string $automationTrigger): void
    {
        $this->automationTriggers[$automationTrigger::getType()] = $automationTrigger;
    }

    /**
     * Add an automation action
     *
     * @param <class-string<AutomationAction> $automationAction
     * @return void
     * @throws Exception
     */
    public function addAutomationAction(string $automationAction): void
    {
        $this->automationActions[$automationAction::getType()] = $automationAction;
    }

    /**
     * Get the automation triggers
     *
     * @return array<class-string<AutomationTrigger>>
     */
    public function getAutomationTriggers(): array
    {
        $triggerTypes = [];
        foreach ($this->automationTriggers as $key => $trigger) {
            $triggerTypes[$key] = $trigger;
        }
        // Add escalation triggers
        $escalationTriggers = $this->escalationRuleService->getEscalationTriggers();
        foreach ($escalationTriggers as $key => $trigger) {
            $triggerTypes[$key] = $trigger;
        }
        return $triggerTypes;
    }

    /**
     * Get the automation trigger class
     *
     * @param string $trigger
     * @return AutomationTrigger|null
     */
    public function getAutomationTrigger(string $trigger): ?AutomationTrigger
    {
        $class = $this->automationTriggers[$trigger] ?? $this->escalationRuleService->getEscalationTrigger($trigger);
        if (!empty($class)) {
            return new $class();
        }
        return $class;
    }

    /**
     * Get the automation actions
     *
     * @return array
     */
    public function getAutomationActions(): array
    {
        $actionTypes = [];
        foreach ($this->automationActions as $key => $action) {
            $actionTypes[$key] = $action;
        }
        // Add escalation actions
        $escalationActions = $this->escalationRuleService->getEscalationActions();
        foreach ($escalationActions as $key => $action) {
            $actionTypes[$key] = $action;
        }
        return $actionTypes;
    }

    /**
     * Check if the action type is available
     *
     * @param string $actionType
     * @return bool
     */
    public function isActionRegistered(string $actionType): bool
    {
        return isset($this->automationActions[$actionType]) ||
            !empty($this->escalationRuleService->getEscalationAction($actionType));
    }

    /**
     * Check if the trigger type is available
     *
     * @param string $triggerType
     * @return bool
     */
    public function isTriggerRegistered(string $triggerType): bool
    {
        return isset($this->automationTriggers[$triggerType]) ||
            !empty($this->escalationRuleService->getEscalationTrigger($triggerType));
    }

    /**
     * Get an action class by the action type
     *
     * @param string $actionType
     * @return string
     */
    public function getAction(string $actionType): ?string
    {
        return $this->automationActions[$actionType] ??
            ($this->escalationRuleService->getEscalationAction($actionType) ?? null);
    }

    /**
     * Get timed automation trigger Types
     *
     * @return array
     */
    public function getTimedAutomationTriggerTypes(): array
    {
        $triggerTypes = [];
        $automationTriggers = $this->getAutomationTriggers();
        $escalationTriggers = $this->escalationRuleService->getEscalationTriggers();
        $triggers = array_merge($automationTriggers, $escalationTriggers);
        foreach ($triggers as $type => $trigger) {
            if (is_a($trigger, TimedAutomationTrigger::class, true)) {
                $triggerTypes[] = $type;
            }
        }
        return $triggerTypes;
    }

    /**
     * Run something with the system user and return the outputted value.
     *
     * @param callable $callback A callback to run.
     *
     * @return mixed The return value of the callback.
     */
    public function runWithSystemUser(callable $callback)
    {
        $session = \Gdn::session();
        $initialUserID = $session->UserID;
        try {
            $systemID = \Gdn::config("Garden.SystemUserID", null);
            Assert::notNull($systemID);
            $session->start(
                $systemID,
                false, // No identity. This is only temporary.
                false // DO NOT set any cookies.
            );
            $r = call_user_func($callback);
            return $r;
        } finally {
            // Restore the old session.
            // We don't want to re-increment any counters or re-issue the cookie though.
            $session->start($initialUserID, false, false);
        }
    }
}
