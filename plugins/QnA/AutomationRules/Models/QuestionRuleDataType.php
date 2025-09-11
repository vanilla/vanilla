<?php
/**
 * @author Sooraj Francis <sfrancis@higherlogic.com>
 * @copyright 2009-2024 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\QnA\AutomationRule\Models;

use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\Dashboard\AutomationRules\Models\EscalationRuleDataType;
use Vanilla\Dashboard\AutomationRules\Models\RuleDataType;
use Vanilla\QnA\AutomationRules\Triggers\UnAnsweredQuestionTrigger;
use Vanilla\Salesforce\Action\EscalateSalesforceLeadAction;

class QuestionRuleDataType extends RuleDataType
{
    /**
     * @inheritdoc
     */
    protected function getAllTriggerClasses(): array
    {
        return [UnAnsweredQuestionTrigger::class];
    }

    /**
     * @inheritdoc
     */
    protected function getAllActionsClasses(): array
    {
        $actionClasses = [AddDiscussionToCollectionAction::class, BumpDiscussionAction::class];
        $escalationActions = EscalationRuleDataType::getActions();
        if (
            class_exists(EscalateSalesforceLeadAction::class) &&
            ($index = array_search(EscalateSalesforceLeadAction::class, $escalationActions)) !== false
        ) {
            unset($escalationActions[$index]);
        }
        return array_merge($actionClasses, $escalationActions);
    }
}
