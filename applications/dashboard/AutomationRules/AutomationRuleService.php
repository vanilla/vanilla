<?php

/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules;

use Exception;
use Vanilla\AutomationRules\Actions\AutomationActionInterface;
use Vanilla\AutomationRules\Trigger\AutomationTriggerInterface;
use Vanilla\AutomationRules\Trigger\TimedAutomationTriggerInterface;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\TimeSinceUserRegistrationTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;

class AutomationRuleService
{
    /** @var array<class-string<AutomationTriggerInterface>> */
    private array $automationTriggers = [];

    /** @var array<class-string<AutomationActionInterface>> */
    private array $automationActions = [];

    /**
     * constructor
     */
    public function __construct()
    {
        // Mount the triggers alphabetically.
        $this->addAutomationTrigger(ProfileFieldSelectionTrigger::class);
        $this->addAutomationTrigger(TimeSinceUserRegistrationTrigger::class);
        $this->addAutomationTrigger(UserEmailDomainTrigger::class);

        // Mount the actions alphabetically.
        $this->addAutomationAction(AddRemoveUserRoleAction::class);
    }

    /**
     * Add an automation trigger
     *
     * @param string $automationTrigger
     * @return void
     * @throws Exception
     */
    public function addAutomationTrigger(string $automationTrigger): void
    {
        if (!is_a($automationTrigger, AutomationTriggerInterface::class, true)) {
            throw new Exception(
                sprintf(
                    "%s is an invalid automation trigger. Triggers should implement the AutomationTriggerInterface.",
                    $automationTrigger
                )
            );
        }
        $this->automationTriggers[$automationTrigger::getType()] = $automationTrigger;
    }

    /**
     * Add an automation action
     *
     * @param string $automationAction
     * @return void
     * @throws Exception
     */
    public function addAutomationAction(string $automationAction): void
    {
        if (!is_a($automationAction, AutomationActionInterface::class, true)) {
            throw new Exception(
                sprintf(
                    "%s is an invalid automation action. Actions should implement the AutomationActionInterface.",
                    $automationAction
                )
            );
        }
        $this->automationActions[$automationAction::getType()] = $automationAction;
    }

    /**
     * Get the automation triggers
     *
     * @return array
     */
    public function getAutomationTriggers(): array
    {
        return $this->automationTriggers;
    }

    /**
     * Get the automation trigger class
     *
     * @param string $trigger
     * @return AutomationTriggerInterface
     */
    public function getAutomationTrigger(string $trigger): AutomationTriggerInterface
    {
        $class = $this->automationTriggers[$trigger];
        return new $class();
    }

    /**
     * Get the automation actions
     *
     * @return array
     */
    public function getAutomationActions(): array
    {
        return $this->automationActions;
    }

    /**
     * Check if the action type is available
     *
     * @param string $actionType
     * @return bool
     */
    public function isActionRegistered(string $actionType): bool
    {
        return isset($this->automationActions[$actionType]);
    }

    /**
     * Check if the trigger type is available
     *
     * @param string $triggerType
     * @return bool
     */
    public function isTriggerRegistered(string $triggerType): bool
    {
        return isset($this->automationTriggers[$triggerType]);
    }

    /**
     * Get an action class by the action type
     *
     * @param string $actionType
     * @return string
     */
    public function getAction(string $actionType): ?string
    {
        return $this->automationActions[$actionType] ?? null;
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
            if (is_a($trigger, TimedAutomationTriggerInterface::class, true)) {
                $triggerTypes[] = $type;
            }
        }
        return $triggerTypes;
    }
}
