<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use EscalateGithubIssueAction;
use EscalateToZendeskAction;
use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CloseDiscussionAction;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromCollectionAction;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;

/**
 * Define what triggers and action can be applied to discussions.
 */
class DiscussionRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     */
    protected function getAllTriggerClasses(): array
    {
        return [StaleDiscussionTrigger::class, LastActiveDiscussionTrigger::class];
    }

    /**
     * @inheridoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllActionsClasses(): array
    {
        return [
            AddDiscussionToCollectionAction::class,
            AddTagToDiscussionAction::class,
            BumpDiscussionAction::class,
            CloseDiscussionAction::class,
            MoveDiscussionToCategoryAction::class,
            RemoveDiscussionFromCollectionAction::class,
            EscalateGithubIssueAction::class,
            EscalateToZendeskAction::class,
        ];
    }
}
