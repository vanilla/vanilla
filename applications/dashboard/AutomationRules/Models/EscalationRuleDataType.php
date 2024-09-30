<?php
/**
 * @author Pavel Goncharov <pgoncharov@higherlogic.com>>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use EscalateGithubIssueAction;
use EscalateToZendeskAction;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\AutomationRules\Triggers\ReportPostTrigger;
use Vanilla\Salesforce\Action\EscalateSalesforceCaseAction;
use Vanilla\Salesforce\Action\EscalateSalesforceLeadAction;

/**
 * Define what triggers and action can be applied to post reports.
 */
class EscalationRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllTriggerClasses(): array
    {
        return [ReportPostTrigger::class];
    }

    /**
     * @inheridoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllActionsClasses(): array
    {
        return [
            CreateEscalationAction::class,
            EscalateGithubIssueAction::class,
            EscalateToZendeskAction::class,
            EscalateSalesforceLeadAction::class,
            EscalateSalesforceCaseAction::class,
        ];
    }
}
