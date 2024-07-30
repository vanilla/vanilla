<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules;

use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;
use Vanilla\FeatureFlagHelper;

class EscalationRuleService
{
    private array $escalationTriggers = [];

    private array $escalationActions = [];

    /**
     * Add an escalation trigger
     *
     * @param string $escalationTrigger
     * @return void
     */
    public function addEscalationTrigger(string $escalationTrigger): void
    {
        if ($escalationTrigger::canAddTrigger()) {
            $this->escalationTriggers[$escalationTrigger::getType()] = $escalationTrigger;
        }
    }

    /**
     * Add an escalation action
     *
     * @param string $escalationAction
     * @return void
     */
    public function addEscalationAction(string $escalationAction): void
    {
        if ($escalationAction::canAddAction()) {
            $this->escalationActions[$escalationAction::getType()] = $escalationAction;
        }
    }

    /**
     * Get the escalation triggers
     *
     * @return array<class-string<AutomationTrigger>>
     */
    public function getEscalationTriggers(): array
    {
        $triggerTypes = [];
        foreach ($this->escalationTriggers as $key => $trigger) {
            $triggerTypes[$key] = $trigger;
        }
        return $triggerTypes;
    }

    /**
     * Get the escalation actions
     *
     * @return array<class-string<AutomationAction>>
     */
    public function getEscalationActions(): array
    {
        $actionTypes = [];
        foreach ($this->escalationActions as $key => $action) {
            $actionTypes[$key] = $action;
        }
        return $actionTypes;
    }

    /**
     * Get an escalation trigger
     *
     * @param string $triggerType
     * @return string|null
     */
    public function getEscalationTrigger(string $triggerType): ?string
    {
        return $this->escalationTriggers[$triggerType] ?? null;
    }

    /**
     * Get an escalation action
     *
     * @param string $actionType
     * @return string|null
     */
    public function getEscalationAction(string $actionType): ?string
    {
        return $this->escalationActions[$actionType] ?? null;
    }
}
