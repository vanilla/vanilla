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
use Vanilla\Forum\Models\Totals\CategorySiteTotalProvider;
use Vanilla\Forum\Models\Totals\CommentSiteTotalProvider;
use Vanilla\Forum\Models\Totals\DiscussionSiteTotalProvider;
use Vanilla\Forum\Widgets\DiscussionAnnouncementsWidget;
use Vanilla\Forum\Widgets\DiscussionDiscussionsWidget;
use Vanilla\Layout\LayoutHydrator;
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
            ->addCall("addReactResolver", [DiscussionDiscussionsWidget::class]);

        $container->rule(SiteTotalService::class)
            ->addCall('registerProvider', [new Reference(CategorySiteTotalProvider::class)])
            ->addCall('registerProvider', [new Reference(DiscussionSiteTotalProvider::class)])
            ->addCall('registerProvider', [new Reference(CommentSiteTotalProvider::class)])
        ;
    }
}
