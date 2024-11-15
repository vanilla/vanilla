<?php
/**
 * @author Olivier Lamy-Canuel <olamy-canuel@higherlogic.com>
 * @copyright 2009-2024 Higher Logic Inc.
 * @license Proprietary
 */

namespace Vanilla\Dashboard\AutomationRules\Models;

use EscalateGithubIssueAction;
use EscalateToZendeskAction;
use SentimentAnalysis\Triggers\PostSentimentTrigger;
use Vanilla\Jira\Action\EscalateToJiraAction;
use Vanilla\Salesforce\Action\EscalateSalesforceCaseAction;
use Vanilla\Salesforce\Action\EscalateSalesforceLeadAction;

/**
 * Every recipe that can be applied to a comment.
 */
class CommentRuleDataType extends RuleDataType
{
    /**
     * @inheridoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllTriggerClasses(): array
    {
        return [PostSentimentTrigger::class];
    }

    /**
     * @inheridoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllActionsClasses(): array
    {
        return [
            EscalateGithubIssueAction::class,
            EscalateToZendeskAction::class,
            EscalateSalesforceLeadAction::class,
            EscalateSalesforceCaseAction::class,
            EscalateToJiraAction::class,
        ];
    }
}
