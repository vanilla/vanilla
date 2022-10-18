<?php
/**
 * @author Adam Charron <adam.c@vanillaforums.com>
 * @copyright 2009-2021 Vanilla Forums Inc.
 * @license Proprietary
 */

namespace Vanilla\Pockets\Addon;

use Garden\Container\Container;
use Garden\Container\ContainerConfigurationInterface;
use Vanilla\AddonContainerRules;
use Vanilla\Addons\Pockets\PocketsModel;

/**
 * Container rules for the pockets addon.
 */
class PocketsContainerRules extends AddonContainerRules
{
    /**
     * @inheritdoc
     */
    public function configureContainer(ContainerConfigurationInterface $container): void
    {
        // Nothing.
    }

    /**
     * @inheritdoc
     */
    public function configureTestContainer(Container $container): void
    {
        // Pockets test currently fail if these are shared.
        $container
            ->rule(PocketsModel::class)
            ->setShared(false)
            ->rule(\PocketsApiController::class)
            ->setShared(false);
    }
}
