<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Dashboard\AutomationRules\EscalationRuleService;
use Vanilla\Dashboard\Models\ActivityService;
use Vanilla\Models\SiteTotalService;
use Vanilla\QnA\Activity\AnswerAcceptedActivity;
use Vanilla\QnA\Activity\QuestionAnswerActivity;
use Vanilla\QnA\Activity\QuestionFollowUpActivity;
use Vanilla\QnA\AutomationRules\Triggers\UnAnsweredQuestionTrigger;
use Vanilla\QnA\Models\QnaQuickLinksProvider;
use Vanilla\QnA\Models\Totals\AcceptedSiteTotalProvider;
use Vanilla\QnA\Models\Totals\QuestionSiteTotalProvider;
use Vanilla\Layout\LayoutService;
use Vanilla\QnA\Layout\View\LegacyNewQuestionLayoutView;
use Vanilla\QnA\Widgets\DiscussionQuestionsWidget;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\QnA\Layout\View\QuestionThreadLayoutView;

/**
 * Class ForumContainerRules
 */
class QnAContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container->rule(LayoutHydrator::class)->addCall("addReactResolver", [DiscussionQuestionsWidget::class]);
        $container
            ->rule(SiteTotalService::class)
            ->addCall("registerProvider", [new Reference(QuestionSiteTotalProvider::class)])
            ->addCall("registerProvider", [new Reference(AcceptedSiteTotalProvider::class)]);
        $container
            ->rule(LayoutService::class)
            ->addCall("addLayoutView", [new Reference(LegacyNewQuestionLayoutView::class)]);

        $container
            ->rule(QuickLinksVariableProvider::class)
            ->addCall("addQuickLinkProvider", [new Reference(QnaQuickLinksProvider::class)]);

        $container
            ->rule(ActivityService::class)
            ->addCall("registerActivity", [AnswerAcceptedActivity::class])
            ->addCall("registerActivity", [QuestionAnswerActivity::class])
            ->addCall("registerActivity", [QuestionFollowUpActivity::class, false]);

        $container
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "questionThread",
                PATH_ROOT . "/plugins/QnA/Layout/Definitions/questionThread.json",
            ])
            ->rule(LayoutHydrator::class)
            ->addCall("addLayoutView", [new Reference(QuestionThreadLayoutView::class)])
            ->rule(LayoutService::class)
            ->addCall("addLayoutView", [new Reference(QuestionThreadLayoutView::class)]);

        $container
            ->rule(AutomationRuleService::class)
            ->addCall("addAutomationTrigger", [UnAnsweredQuestionTrigger::class]);
        $container
            ->rule(EscalationRuleService::class)
            ->addCall("addEscalationTrigger", [UnAnsweredQuestionTrigger::class]);
    }
}
