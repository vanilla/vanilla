<?php
/**
 * @author Isis Graziatto <igraziatto@higherlogic.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license GPL-2.0-only
 */

namespace Vanilla\QnA\Addon;

use Garden\Container\ContainerConfigurationInterface;
use Vanilla\AddonContainerRules;
use Vanilla\QnA\Widgets\DiscussionQuestionsWidget;
use Vanilla\Layout\LayoutHydrator;

/**
 * Class ForumContainerRules
 */
class QnAContainerRules extends AddonContainerRules {

    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void {
        $container
            ->rule(LayoutHydrator::class)
            ->addCall("addReactResolver", [DiscussionQuestionsWidget::class]);
    }
}
