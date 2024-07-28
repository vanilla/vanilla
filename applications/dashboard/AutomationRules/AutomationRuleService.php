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

class AutomationRuleService
{
    /** @var array<class-string<AutomationTrigger>> */
    private array $automationTriggers = [];

    /** @var array<class-string<AutomationAction>> */
    private array $automationActions = [];

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
            if ($trigger::canAddTrigger()) {
                $triggerTypes[$key] = $trigger;
            }
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
        $class = $this->automationTriggers[$trigger];
        $trigger = new $class();
        if ($trigger::canAddTrigger()) {
            return $trigger;
        }
        return null;
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
            if ($action::canAddAction()) {
                $actionTypes[$key] = $action;
            }
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
        $action = $this->automationActions[$actionType];
        if (isset($action) && $action::canAddAction()) {
            return true;
        }
        return false;
    }

    /**
     * Check if the trigger type is available
     *
     * @param string $triggerType
     * @return bool
     */
    public function isTriggerRegistered(string $triggerType): bool
    {
        $trigger = $this->automationTriggers[$triggerType];
        if (isset($trigger) && $trigger::canAddTrigger()) {
            return true;
        }
        return false;
    }

    /**
     * Get an action class by the action type
     *
     * @param string $actionType
     * @return string
     */
    public function getAction(string $actionType): ?string
    {
        $action = $this->automationActions[$actionType] ?? null;
        if (isset($action) && $action::canAddAction()) {
            return $action;
        }
        return null;
    }

    /**
     * Get timed automation trigger Types
     *
     * @return array
     */
    public function getTimedAutomationTriggerTypes(): array
    {
        $triggerTypes = [];
        foreach ($this->automationTriggers as $type => $trigger) {
            if (!$trigger::canAddTrigger()) {
                continue;
            }
            if (is_a($trigger, TimedAutomationTrigger::class, true)) {
                $triggerTypes[] = $type;
            }
        }
        return $triggerTypes;
    }
}
