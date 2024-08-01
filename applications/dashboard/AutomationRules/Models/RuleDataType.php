<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use Garden\Container\ContainerException;
use Garden\Container\NotFoundException;
use Vanilla\AutomationRules\Actions\AutomationAction;
use Vanilla\AutomationRules\Trigger\AutomationTrigger;

/**
 * Specify what triggers and actions are available for a rule based on their shared datatype.
 */
abstract class RuleDataType
{
    /**
     * Return every valid triggers that could be used with this datatype.
     *
     * Filter out any triggers requiring a class that does not exist such as when a plugin isn't enabled.
     *
     * @return array<AutomationTrigger>
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function getTriggers(): array
    {
        $instance = \Gdn::getContainer()->get(static::class);
        $triggers = $instance->getAllTriggerClasses();
        $validTriggers = [];

        foreach ($triggers as $trigger) {
            if (!class_exists($trigger, false)) {
                continue;
            }
            $validTriggers[] = $trigger;
        }
        return $validTriggers;
    }

    /**
     * Return every valid actions that could be used with this datatype.
     *
     * Filter out any actions requiring a class that does not exist such as when a plugin isn't enabled.
     *
     * @return array<AutomationTrigger>
     */
    abstract protected function getAllTriggerClasses(): array;

    /**
     * Return every actions that could be used with this datatype.
     *
     * @return array<AutomationAction>
     * @throws ContainerException
     * @throws NotFoundException
     */
    public static function getActions(): array
    {
        $instance = \Gdn::getContainer()->get(static::class);
        $actions = $instance->getAllActionsClasses();
        $validActions = [];
        foreach ($actions as $action) {
            if (!class_exists($action, false)) {
                continue;
            }
            $validActions[] = $action;
        }

        return $validActions;
    }

    /**
     * Return every actions that could be used with this datatype.
     *
     * @return array<AutomationAction>
     */
    abstract protected function getAllActionsClasses(): array;
}
