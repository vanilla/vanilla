<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Garden\Web\PageControllerRoute;
use Vanilla\AddonContainerRules;
use Vanilla\Analytics\EventProviderService;
use Vanilla\Analytics\SearchDiscussionEventProvider;
use Vanilla\Forum\Controllers\Pages\DiscussionListPageController;
use Vanilla\Forum\Layout\View\LegacyCategoryListLayoutView;
use Vanilla\Forum\Layout\View\DiscussionListLayoutView;
use Vanilla\Forum\Layout\View\LegacyDiscussionThreadLayoutView;
use Vanilla\Forum\Layout\View\LegacyNewDiscussionLayoutView;
use Vanilla\Forum\Layout\Middleware\CategoryFilterMiddleware;
use Vanilla\Forum\Models\CategoryCollectionProvider;
use Vanilla\Forum\Models\DiscussionCollectionProvider;
use Vanilla\Forum\Models\Totals\CategorySiteTotalProvider;
use Vanilla\Forum\Models\Totals\CommentSiteTotalProvider;
use Vanilla\Forum\Models\Totals\DiscussionSiteTotalProvider;
use Vanilla\Forum\Models\Totals\PostSiteTotalProvider;
use Vanilla\Forum\Widgets\DiscussionAnnouncementsWidget;
use Vanilla\Forum\Widgets\DiscussionDiscussionsWidget;
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
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\View\HomeLayoutView;
use Vanilla\Models\CollectionModel;
use Vanilla\Models\SiteTotalService;
use Vanilla\Utility\ContainerUtils;

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
            ->addCall("addMiddleware", [new Reference(CategoryFilterMiddleware::class)])

            // Modern layout views.
            ->addCall("addLayoutView", [new Reference(DiscussionListLayoutView::class)]);

        $container
            ->rule(\Vanilla\Layout\Providers\FileBasedLayoutProvider::class)
            ->addCall("registerStaticLayout", [
                "discussionList",
                PATH_ROOT . "/applications/vanilla/Layout/Definitions/discussionList.json",
            ]);

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
            ->addCall("addLayoutView", [new Reference(LegacyDiscussionThreadLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(LegacyNewDiscussionLayoutView::class)])
            ->addCall("addLayoutView", [new Reference(HomeLayoutView::class)]);

        PageControllerRoute::configurePageRoutes(
            $container,
            [
                "/discussions" => DiscussionListPageController::class,
            ],
            "customLayout.discussionList"
        );

        // Collections.
        $container
            ->rule(CollectionModel::class)
            ->addCall("addCollectionRecordProvider", [new Reference(DiscussionCollectionProvider::class)])
            ->addCall("addCollectionRecordProvider", [new Reference(CategoryCollectionProvider::class)]);

        ContainerUtils::addCall($container, \Vanilla\Site\SiteSectionModel::class, "registerApplication", [
            "forum",
            ["name" => "Forum"],
        ]);
    }
}
