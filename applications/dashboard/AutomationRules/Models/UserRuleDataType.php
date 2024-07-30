<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\TimeSinceUserRegistrationTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;

/**
 * Define what triggers and action can be applied to users.
 */
class UserRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     */
    protected function getAllTriggerClasses(): array
    {
        return [
            UserEmailDomainTrigger::class,
            ProfileFieldSelectionTrigger::class,
            TimeSinceUserRegistrationTrigger::class,
        ];
    }

    /**
     * @inheridoc
     */
    protected function getAllActionsClasses(): array
    {
        return [UserFollowCategoryAction::class, AddRemoveUserRoleAction::class];
    }
}
