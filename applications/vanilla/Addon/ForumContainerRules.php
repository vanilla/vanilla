<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2023 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use BreadcrumbWidget;
use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Garden\Web\PageControllerRoute;
use Vanilla\AddonContainerRules;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Analytics\SearchDiscussionEventProvider;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromCollectionAction;
use Vanilla\AutomationRules\Triggers\DiscussionReachesScoreTrigger;
use Vanilla\AutomationRules\Actions\CreateEscalationAction;
use Vanilla\AutomationRules\Triggers\ReportPostTrigger;
use Vanilla\Dashboard\AutomationRules\Actions\AddRemoveUserRoleAction;
use Vanilla\Dashboard\AutomationRules\EscalationRuleService;
use Vanilla\Dashboard\AutomationRules\Triggers\ProfileFieldSelectionTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\TimeSinceUserRegistrationTrigger;
use Vanilla\Dashboard\AutomationRules\Triggers\UserEmailDomainTrigger;
use Vanilla\Dashboard\Models\AttachmentService;
use Vanilla\Forum\Controllers\Pages\AdminContentPageController;
use Vanilla\AutomationRules\Actions\AddDiscussionToCollectionAction;
use Vanilla\AutomationRules\Actions\AddTagToDiscussionAction;
use Vanilla\AutomationRules\Actions\BumpDiscussionAction;
use Vanilla\AutomationRules\Actions\CloseDiscussionAction;
use Vanilla\AutomationRules\Actions\MoveDiscussionToCategoryAction;
use Vanilla\AutomationRules\Actions\RemoveDiscussionFromTriggerCollectionAction;
use Vanilla\AutomationRules\Actions\UserFollowCategoryAction;
use Vanilla\AutomationRules\Triggers\LastActiveDiscussionTrigger;
use Vanilla\AutomationRules\Triggers\StaleCollectionTrigger;
use Vanilla\AutomationRules\Triggers\StaleDiscussionTrigger;
use Vanilla\Dashboard\AutomationRules\AutomationRuleService;
use Vanilla\Dashboard\Models\AutomationRuleModel;
use Vanilla\Forum\Controllers\Pages\CategoryListPageController;
use Vanilla\Forum\Controllers\Pages\DiscussionListPageController;
use Vanilla\Forum\Controllers\Pages\DiscussionThreadPageController;
use Vanilla\Forum\Controllers\Pages\PostController;
use Vanilla\Forum\Controllers\Pages\UnsubscribePageController;
use Vanilla\Forum\Controllers\Pages\ConvertHTMLPageController;
use Vanilla\Forum\Layout\View\CategoryListLayoutView;
use Vanilla\Forum\Layout\View\DiscussionCategoryPageLayoutView;
use Vanilla\Forum\Layout\View\LegacyCategoryListLayoutView;
use Vanilla\Forum\Layout\View\DiscussionListLayoutView;
use Vanilla\Forum\Layout\View\DiscussionThreadLayoutView;
use Vanilla\Forum\Layout\View\LegacyNewDiscussionLayoutView;
use Vanilla\Forum\Layout\Middleware\CategoryFilterMiddleware;
use Vanilla\Forum\Layout\View\NestedCategoryListPageLayoutView;
use Vanilla\Forum\Models\AiSuggestionSiteMetaExtra;
use Vanilla\Forum\Models\CategoryCollectionProvider;
use Vanilla\Forum\Models\CategorySiteMetaExtra;
use Vanilla\Forum\Models\DiscussionCollectionProvider;
use Vanilla\Forum\Models\ForumAggregateModel;
use Vanilla\Forum\Models\ForumQuickLinksProvider;
use Vanilla\Forum\Models\PostFieldsExpander;
use Vanilla\Forum\Models\PostingSiteMetaExtra;
use Vanilla\Forum\Models\PostTypeModel;
use Vanilla\Forum\Models\ReactionsQuickLinksProvider;
use Vanilla\Forum\Models\Totals\CategorySiteTotalProvider;
use Vanilla\Forum\Models\Totals\CommentSiteTotalProvider;
use Vanilla\Forum\Models\Totals\DiscussionSiteTotalProvider;
use Vanilla\Forum\Models\Totals\PostSiteTotalProvider;
use Vanilla\Forum\Models\VanillaEscalationAttachmentProvider;
use Vanilla\Forum\Widgets\DiscussionAnnouncementsWidget;
use Vanilla\Forum\Widgets\DiscussionDiscussionsWidget;
use Vanilla\Forum\Widgets\DiscussionTagsAsset;
use Vanilla\Forum\Widgets\SuggestedContentWidget;
use Vanilla\Forum\Widgets\TagWidget;
use Vanilla\Forum\Widgets\CategoriesWidget;
use Vanilla\Forum\Widgets\RSSWidget;
use Vanilla\Forum\Widgets\UserSpotlightWidget;
use Vanilla\Forum\Widgets\SiteTotalsWidget;
use Vanilla\Forum\Widgets\NewPostWidget;
use Vanilla\Forum\Widgets\TabsWidget;
use Vanilla\Forum\Widgets\CallToActionWidget;
use Vanilla\Forum\Widgets\GuestCallToActionWidget;
use Vanilla\Forum\Widgets\FeaturedCollectionsWidget;
use Vanilla\Forum\Widgets\DiscussionListAsset;
use Vanilla\Layout\CategoryLayoutRecordProvider;
use Vanilla\Layout\CommentLayoutRecordProvider;
use Vanilla\Layout\DiscussionLayoutRecordProvider;
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\LayoutViewModel;
use Vanilla\Layout\View\HomeLayoutView;
use Vanilla\Models\CollectionModel;
use Vanilla\Models\SiteMeta;
use Vanilla\Models\SiteTotalService;
use Vanilla\Theme\VariableProviders\QuickLinksVariableProvider;
use Vanilla\Utility\ContainerUtils;
use Vanilla\Utility\DebugUtils;
use Vanilla\Web\APIExpandMiddleware;

/**
 * Class ForumContainerRules
 */
class ForumContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        $container
            ->rule(ForumAggregateModel::class)
            ->setShared(true)

            ->rule(LayoutHydrator::class)
            ->addCall("addReactResolver", [DiscussionAnnouncementsWidget::class])
            ->addCall("addReactResolver", [DiscussionDiscussionsWidget::class])
            ->addCall("addReactResolver", [TagWidget::class])
            ->addCall("addReactResolver", [TabsWidget::class])
            ->addCall("addReactResolver", [CategoriesWidget::class])
            ->addCall("addReactResolver", [RSSWidget::class])
            ->addCall("addReactResolver", [UserSpotlightWidget::class])
            ->addCall("addReactResolver", [SiteTotalsWidget::class])
            ->addCall("addReactResolver", [NewPostWidget::class])
            ->addCall("addReactResolver", [CallToActionWidget::class])
            ->addCall("addReactResolver", [GuestCallToActionWidget::class])
            ->addCall("addReactResolver", [DiscussionListAsset::class])
            ->addCall("addReactResolver", [FeaturedCollectionsWidget::class])
            ->addCall("addReactResolver", [BreadcrumbWidget::class])
            ->addCall("addReactResolver", [DiscussionTagsAsset::class])
            ->addCall("addReactResolver", [SuggestedContentWidget::class])
            ->addCall("addMiddleware", [new Reference(CategoryFilterMiddleware::class)])

            // Modern layout views.
            ->addCall("addLayoutView", [new Reference(DiscussionListLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(CategoryListLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(NestedCategoryListPageLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(DiscussionCategoryPageLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(DiscussionThreadLayoutView::class)]);

        $container
            ->rule(LayoutViewModel::class)
            ->addCall("addLayoutRecordProvider", [new Reference(CategoryLayoutRecordProvider::class)])
            ->addCall("addLayoutRecordProvider", [new Reference(DiscussionLayoutRecordProvider::class)])
            ->addCall("addLayoutRecordProvider", [new Reference(CommentLayoutRecordProvider::class)]);

        $container
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "discussionList",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/discussionList.json",
            ])
            ->addCall("registerStaticLayout", [
                "discussionThread",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/discussionThread.json",
            ]);

        $container
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "categoryList",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/categoryList.json",
            ])
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "nestedCategoryList",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/nestedCategoryList.json",
            ])
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "discussionCategoryPage",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/discussionCategoryPage.json",
            ]);

        // Quick links
        $container
            ->rule(QuickLinksVariableProvider::class)
            ->addCall("addQuickLinkProvider", [new Reference(ForumQuickLinksProvider::class)]);

        $container
            ->rule(SiteTotalService::class)
            ->addCall("registerProvider", [new Reference(CategorySiteTotalProvider::class)])
            ->addCall("registerProvider", [new Reference(DiscussionSiteTotalProvider::class)])
            ->addCall("registerProvider", [new Reference(CommentSiteTotalProvider::class)])
            ->addCall("registerProvider", [new Reference(PostSiteTotalProvider::class)]);

        // Search Events
        $container
            ->rule(EventProviderService::class)
            ->addCall("registerEventProvider", [new Reference(SearchDiscussionEventProvider::class)]);

        // Legacy Layout views
        $container
            ->rule(LayoutService::class)
            ->addCall("addLayoutView", [new Reference(LegacyCategoryListLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(DiscussionListLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(CategoryListLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(NestedCategoryListPageLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(DiscussionCategoryPageLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(DiscussionThreadLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(LegacyNewDiscussionLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(HomeLayoutView::class)]);

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/discussions" => DiscussionListPageController::class,
            ],
            "customLayout.discussionList"
        );

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/discussion/" => DiscussionThreadPageController::class,
            ],
            "customLayout.discussionThread"
        );

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/categories" => CategoryListPageController::class,
            ],
            "customLayout.categoryList"
        );

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/unsubscribe" => UnsubscribePageController::class,
                "/utility/convert-html" => ConvertHTMLPageController::class,
            ],
            null,
            -1
        );

        PageControllerRoute::configurePageRoutes(
            $container,
            ["/post/" => PostController::class],
            PostTypeModel::FEATURE_POST_TYPES_AND_POST_FIELDS
        );

        PageControllerRoute::configurePageRoutes($container, [
            "/dashboard/content" => AdminContentPageController::class,
        ]);

        $container
            ->rule(SiteMeta::class)
            ->addCall("addExtra", [new Reference(PostingSiteMetaExtra::class)])
            ->addCall("addExtra", [new Reference(CategorySiteMetaExtra::class)])
            ->addCall("addExtra", [new Reference(AiSuggestionSiteMetaExtra::class)]);

        // Collections.
        $container
            ->rule(CollectionModel::class)
            ->addCall("addCollectionRecordProvider", [new Reference(DiscussionCollectionProvider::class)])
            ->addCall("addCollectionRecordProvider", [new Reference(CategoryCollectionProvider::class)]);

        ContainerUtils::addCall($container, \Vanilla\Site\SiteSectionModel::class, "registerApplication", [
            "forum",
            ["name" => "Forum"],
        ]);

        $container
            ->rule(QuickLinksVariableProvider::class)
            ->addCall("addQuickLinkProvider", [new Reference(ReactionsQuickLinksProvider::class)]);

        //Automation Rules (Add new ones alphabetically, please)
        $container
            ->rule(AutomationRuleService::class)
            ->addCall("addAutomationTrigger", [DiscussionReachesScoreTrigger::class])
            ->addCall("addAutomationTrigger", [LastActiveDiscussionTrigger::class])
            ->addCall("addAutomationTrigger", [ProfileFieldSelectionTrigger::class])
            ->addCall("addAutomationTrigger", [ReportPostTrigger::class])
            ->addCall("addAutomationTrigger", [StaleCollectionTrigger::class])
            ->addCall("addAutomationTrigger", [StaleDiscussionTrigger::class])
            ->addCall("addAutomationTrigger", [TimeSinceUserRegistrationTrigger::class])
            ->addCall("addAutomationTrigger", [UserEmailDomainTrigger::class])

            ->addCall("addAutomationAction", [AddDiscussionToCollectionAction::class])
            ->addCall("addAutomationAction", [AddRemoveUserRoleAction::class])
            ->addCall("addAutomationAction", [AddTagToDiscussionAction::class])
            ->addCall("addAutomationAction", [BumpDiscussionAction::class])
            ->addCall("addAutomationAction", [CloseDiscussionAction::class])
            ->addCall("addAutomationAction", [CreateEscalationAction::class])
            ->addCall("addAutomationAction", [MoveDiscussionToCategoryAction::class])
            ->addCall("addAutomationAction", [RemoveDiscussionFromCollectionAction::class])
            ->addCall("addAutomationAction", [RemoveDiscussionFromTriggerCollectionAction::class])
            ->addCall("addAutomationAction", [UserFollowCategoryAction::class]);

        //Escalation Automation Rules
        $container
            ->rule(EscalationRuleService::class)
            ->addCall("addEscalationTrigger", [DiscussionReachesScoreTrigger::class])
            ->addCall("addEscalationTrigger", [ReportPostTrigger::class])
            ->addCall("addEscalationTrigger", [StaleDiscussionTrigger::class])
            ->addCall("addEscalationTrigger", [LastActiveDiscussionTrigger::class])
            ->addCall("addEscalationAction", [CreateEscalationAction::class]);

        $container
            ->rule(AttachmentService::class)
            ->addCall("addProvider", [new Reference(VanillaEscalationAttachmentProvider::class)]);

        $container
            ->rule(AutomationRuleService::class)
            ->setShared(true)
            ->rule(AutomationRuleModel::class)
            ->setShared(true);

        if (!DebugUtils::isTestMode() && !function_exists("writeReactions")) {
            include PATH_APPLICATIONS . "/dashboard/views/reactions/reaction_functions.php";
        }
    }
}
