<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

/**
 * Define what recipe can be applied to posts.
 */
class PostRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     */
    protected function getAllTriggerClasses(): array
    {
        $postType = array_merge(DiscussionRuleDataType::getTriggers(), CommentRuleDataType::getTriggers());
        return array_unique($postType);
    }

    /**
     * @inheridoc
     */
    protected function getAllActionsClasses(): array
    {
        $postType = array_merge(DiscussionRuleDataType::getActions(), EscalationRuleDataType::getActions());
        return array_unique($postType);
    }
}
