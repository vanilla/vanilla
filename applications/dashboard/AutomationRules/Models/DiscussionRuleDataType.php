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
use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CloseDiscussionAction;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromCollectionAction;
use Vanilla\AutomationRules\Triggers\DiscussionReachesScoreTrigger;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Jira\Action\EscalateToJiraAction;
use Vanilla\Salesforce\Action\EscalateSalesforceCaseAction;
use Vanilla\Salesforce\Action\EscalateSalesforceLeadAction;

/**
 * Define what triggers and action can be applied to discussions.
 */
class DiscussionRuleDataType extends RuleDataType
{
    /**
     * @inheritdoc
     * @psalm-suppress UndefinedClass
     */
    protected function getAllTriggerClasses(): array
    {
        // Add new ones alphabetically, please.
        return [
            DiscussionReachesScoreTrigger::class,
            LastActiveDiscussionTrigger::class,
            PostSentimentTrigger::class,
            StaleDiscussionTrigger::class,
        ];
    }

    /**
     * @inheritdoc
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
            EscalateSalesforceLeadAction::class,
            EscalateSalesforceCaseAction::class,
            EscalateToJiraAction::class,
        ];
    }
}
