<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use Vanilla\AutomationRules\Actions\RemoveDiscussionFromTriggerCollectionAction;
use Vanilla\AutomationRules\Triggers\StaleCollectionTrigger;

/**
 * Define what triggers and action can be applied to collections.
 */
class CollectionRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     */
    protected function getAllTriggerClasses(): array
    {
        return [StaleCollectionTrigger::class];
    }

    /**
     * @inheridoc
     */
    protected function getAllActionsClasses(): array
    {
        return [RemoveDiscussionFromTriggerCollectionAction::class];
    }
}
