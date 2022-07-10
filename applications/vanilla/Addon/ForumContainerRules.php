<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\Forum\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Garden\Container\Reference;
use Vanilla\AddonContainerRules;
use Vanilla\Forum\Layout\View\LegacyCategoryListLayoutView;
use Vanilla\Forum\Layout\View\LegacyDiscussionListLayoutView;
use Vanilla\Forum\Layout\View\LegacyDiscussionThreadLayoutView;
use Vanilla\Forum\Layout\View\LegacyNewDiscussionLayoutView;
use Vanilla\Forum\Layout\Middleware\CategoryFilterMiddleware;
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
use Vanilla\Layout\LayoutHydrator;
use Vanilla\Layout\LayoutService;
use Vanilla\Layout\View\HomeLayoutView;
use Vanilla\Models\SiteTotalService;

/**
 * Class ForumContainerRules
 */
class ForumContainerRules extends AddonContainerRules {

    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void {
        $container
            ->rule(LayoutHydrator::class)
            ->addCall("addReactResolver", [DiscussionAnnouncementsWidget::class])
            ->addCall("addReactResolver", [DiscussionDiscussionsWidget::class])
            ->addCall("addReactResolver", [TagWidget::class])
            ->addCall("addReactResolver", [CategoriesWidget::class])
            ->addCall("addReactResolver", [RSSWidget::class])
            ->addCall("addReactResolver", [UserSpotlightWidget::class])
            ->addCall('addMiddleware', [new Reference(CategoryFilterMiddleware::class)]);

        $container->rule(SiteTotalService::class)
            ->addCall('registerProvider', [new Reference(CategorySiteTotalProvider::class)])
            ->addCall('registerProvider', [new Reference(DiscussionSiteTotalProvider::class)])
            ->addCall('registerProvider', [new Reference(CommentSiteTotalProvider::class)])
            ->addCall('registerProvider', [new Reference(PostSiteTotalProvider::class)])
        ;

        $container->rule(LayoutService::class)
            ->addCall('addLayoutView', [new Reference(LegacyCategoryListLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(LegacyDiscussionListLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(LegacyDiscussionThreadLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(LegacyNewDiscussionLayoutView::class)])
            ->addCall('addLayoutView', [new Reference(HomeLayoutView::class)])
        ;
    }
}
