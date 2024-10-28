<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\AutomationRule\Models;

use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\Dashboard\AutomationRules\Models\RuleDataType;
use Vanilla\QnA\AutomationRules\Triggers\UnAnsweredQuestionTrigger;
use EscalateToZendeskAction;

class QuestionRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     */
    protected function getAllTriggerClasses(): array
    {
        return [UnAnsweredQuestionTrigger::class];
    }

    /**
     * @inheridoc
     */
    protected function getAllActionsClasses(): array
    {
        $actionClasses = [
            AddDiscussionToCollectionAction::class,
            BumpDiscussionAction::class,
            CreateEscalationAction::class,
        ];
        if (class_exists(EscalateToZendeskAction::class)) {
            $actionClasses[] = EscalateToZendeskAction::class;
        }
        return $actionClasses;
    }
}
